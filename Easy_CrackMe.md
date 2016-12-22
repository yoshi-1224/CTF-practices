## Easy_CrackMe

We are given a binary file called Easy_CrackMe.exe. When we run it, it asks for a password.

Let's run ```file``` command as usual.
```
yoshi@yoshi-VirtualBox:~/Downloads$ file Easy_CrackMe.exe 
Easy_CrackMe.exe: PE32 executable (GUI) Intel 80386, for MS Windows
```
This is Windows Portable Executable (32bit), so We can use IDA Pro to disassemble it! Let's first check out any interesting strings in this binary.
```
.data:00406030 00000013 C Incorrect Password
.data:00406044 00000012 C Congratulation !! 
.data:00406058 0000000C C EasyCrackMe       
.data:0040606C 0000000A C R3versing         
```
When we check around, we see this function called ```sub_401080(HWND hDlg)```. We can decompile this function, and it looks like this
```
int __cdecl sub_401080(HWND hDlg)
{
  int result; // eax@5
  CHAR String; // [sp+4h] [bp-64h]@1
  char v3; // [sp+5h] [bp-63h]@1
  char v4; // [sp+6h] [bp-62h]@2
  char v5; // [sp+8h] [bp-60h]@3
  __int16 v6; // [sp+65h] [bp-3h]@1
  char v7; // [sp+67h] [bp-1h]@1

  String = 0;
  memset(&v3, 0, 0x60u);
  v6 = 0;
  v7 = 0;
  GetDlgItemTextA(hDlg, 1000, &String, 100);
  if ( v3 == 'a' && !strncmp(&v4, a5y, 2u) && !strcmp(&v5, aR3versing) && String == 'E' )
  {
    MessageBoxA(hDlg, Text, Caption, 64u);
    result = EndDialog(hDlg, 0);
  }
  else
  {
    result = MessageBoxA(hDlg, aIncorrectPassw, Caption, 0x10u);
  }
  return result;
}
```
It first calls ```GetDlgItemText```, then checks to see if the following condition is met
```
if ( v3 == 'a' && !strncmp(&v4, a5y, 2u) && !strcmp(&v5, aR3versing) && String == 'E' )
```
From Googling, we know GetDlgItemText stores user input string to &String. Also, when we double-click on the variable String, 
```
-00000064 String          db ?
-00000063 var_63          db ?
-00000062 var_62          db ?
-00000061                 db ? ; undefined
-00000060 var_60          db ?
```
where var_63 = v3, var_62 = v4, var_60 = v5. Calculating their relative positions, we can deduce the following
- The first character of the user input  == 'E'
- The second character of the usre input == 'a'
- This is followed by '5y' and 'R3versing'
So we simply pass ```Ea5yR3versing```! Yup, it does print out 'Congratulations!'
