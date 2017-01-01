####Challenge 1

We are given and executable file called challenge1.exe. 
Let's first run ```file``` command on this executable. 
```
yoshi@yoshi-VirtualBox:~/Downloads/reversing-workshop-master/1$ file challenge1.exe 
challenge1.exe: PE32 executable (console) Intel 80386, for MS Windows
```

So we know that this file is Windows PE32 Intel x86 architecture. We can use IDA Pro (32bit version) to disassemble this executable. 
As usual, use **strings** (shift + f12) to identify the function(s) that is/are of our interest. When we do that, we can see some interesting strings: 

```
.rdata:0040D1AC 0000000B C Correct!\r\n
Address         Length   Type String                                                                                           
-------         ------   ---- ------                                                                                           
.rdata:0040D160 00000035 C    x2dtJEOmyjacxDemx2eczT5cVS9fVUGvWTuZWjuexjRqy24rV29q                                             
.rdata:0040D198 00000012 C    Enter password:\r\n                                                                              
.rdata:0040D1AC 0000000B C    Correct!\r\n                                                                                     
.rdata:0040D1B8 00000011 C    Wrong password\r\n                                                                               
```
We can double-click on the strings and go to their locations in the assembly. 
```
.text:004014BF loc_4014BF:                             ; CODE XREF: sub_401420+84j
.text:004014BF                 push    0               ; lpOverlapped
.text:004014C1                 lea     edx, [ebp+NumberOfBytesWritten]
.text:004014C4                 push    edx             ; lpNumberOfBytesWritten
.text:004014C5                 push    11h             ; nNumberOfBytesToWrite
.text:004014C7                 push    offset aWrongPassword ; "Wrong password\r\n"
.text:004014CC                 mov     eax, [ebp+hFile]
.text:004014CF                 push    eax             ; hFile
.text:004014D0                 call    ds:WriteFile
```
Now decompile the function ```sub_401420()```. The following should show up.
```
int sub_401420()
{
  char Buffer; // [sp+0h] [bp-94h]@1
  int v2; // [sp+80h] [bp-14h]@1
  const char *v3; // [sp+84h] [bp-10h]@1
  HANDLE v4; // [sp+88h] [bp-Ch]@1
  HANDLE hFile; // [sp+8Ch] [bp-8h]@1
  DWORD NumberOfBytesWritten; // [sp+90h] [bp-4h]@1

  hFile = GetStdHandle(0xFFFFFFF5);
  v4 = GetStdHandle(0xFFFFFFF6);
  v3 = "x2dtJEOmyjacxDemx2eczT5cVS9fVUGvWTuZWjuexjRqy24rV29q";
  WriteFile(hFile, "Enter password:\r\n", 0x12u, &NumberOfBytesWritten, 0);
  ReadFile(v4, &Buffer, 0x80u, &NumberOfBytesWritten, 0);
  v2 = sub_401260(&Buffer, NumberOfBytesWritten - 2);
  if ( sub_402C30(v2, v3) )
    WriteFile(hFile, "Wrong password\r\n", 0x11u, &NumberOfBytesWritten, 0);
  else
    WriteFile(hFile, "Correct!\r\n", 0xBu, &NumberOfBytesWritten, 0);
  return 0;
}
```
Ok, so there is a function ```sub_402C30(v2, v3)``` which takes in 2 arguments that affects the branching.
From the above, we can easily tell that v3 is just a string constant 
```
v3 = "x2dtJEOmyjacxDemx2eczT5cVS9fVUGvWTuZWjuexjRqy24rV29q";
```
Then what is v2? v2 assigned the value of ```sub_401260(&Buffer, NumberOfBytesWritten - 2)```. Doing further checking, we find out that v2 is the string we pass to this executable (similar to argv[1]) in C. 

Now, looking into ```sub_402C30(v2, v3)```, we notice that this is implementing base64 encoding, and checking if the user input encodes to v3. Does this mean that if we decode v3 we can get the flag?
```
>>> import base64
>>> flag = base64.b64decode
>>> flag = base64.b64decode("x2dtJEOmyjacxDemx2eczT5cVS9fVUGvWTuZWjuexjRqy24rV29q")
>>> flag
b'\xc7gm$C\xa6\xca6\x9c\xc47\xa6\xc7g\x9c\xcd>\\U/_UA\xafY;\x99Z;\x9e\xc64j\xcbn+Woj'
``` 
Doesn't look like it. 
If we look back into the strings, there is actually another interesting one
```
"ZYXABCDEFGHIJKLMNOPQRSTUVWzyxabcdefghijklmnopqrstuvw0123456789+/"
```
Normally, a base64 encoding is done using the index string
```
"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/"
```
So to get the flag, we need to translate the string and then decode it. Here comes the flag
```
>>> flag = "x2dtJEOmyjacxDemx2eczT5cVS9fVUGvWTuZWjuexjRqy24rV29q".translate(string.maketrans("ZYXABCDEFGHIJKLMNOPQRSTUVWzyxabcdefghijklmnopqrstuvw0123456789+/","ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/" ))
>>> flag = base64.b64decode(flag)
>>> flag
'sh00ting_phish_in_a_barrel@flare-on.com'
```

