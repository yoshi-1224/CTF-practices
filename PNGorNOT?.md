##PNG or NOT?

This challenge comes from PicoCTF 2014. 

We are given a PNG file, with a hint to study the file format of PNG. I referred to the following website http://www.libpng.org/pub/png/spec/1.2/PNG-Structure.html

So PNG can be divided into multiple "chunks", with the condition that there are always IHDR (header chunk) and IEND (end chunk). 
Let's take open the PNG file in a text editor:
```
IEND�B`�7z��'�d��P�kiF"�ƮwF�#m�]�����8W�v> 
                                                                               #]
       
�flag.txt

```
After the IEND chunk, somehow there is a lot of garbage that follows. A closer look at this extra reveals there is ```flag.txt``` file hidden in this PNG. But how do we extract it?

It turns out that if we take even a closer look, we see the string ```7z```, which tells us that the compression system used here is 7z. So now we just run the command
```
yoshi@yoshi-VirtualBox:~/Downloads$ 7z x image.png
``` 
and retrieve the flag
```
EKSi7MktjOpvwesurw0v
```
