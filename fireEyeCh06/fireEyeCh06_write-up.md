###Challenge 3

We are provided with an executable file named "khaki.exe". Let's first try and run it.

```
(Guesses: 1) Pick a number between 1 and 100:50
 Too low, try again
(Guesses: 2) Pick a number between 1 and 100:75
 Too high, try again
(Guesses: 3) Pick a number between 1 and 100:63
 Too low, try again
(Guesses: 4) Pick a number between 1 and 100:
```
seems like a guess game. It quits when we guess correctly. 
By running ```file``` on the executable, we identify the file format.
```
yoshi@yoshi-VirtualBox:~/Downloads/reversing-workshop-master/6$ file khaki.exe
khaki.exe: PE32 executable (console) Intel 80386, for MS Windows
```
Let's futher run ```strings``` command. We should see a lot of ```pyc```. From this we can guess that this executable was built using Python. What we can do now is to first put this executable back into .pyc file. One possible tool available is **unpy2exe** (https://github.com/matiasb/unpy2exe). Following the documentation, we run the file as follows:
```
unpy2exe.py -o OUTPUT_DIR -p Python27 khaki.exe
```
In the OUTPUT_DIR, we should see a file named ```poc.py```. This is the file we want to decompile, but it doesn't work well. To see why, Let's do a bit of Python scripting.
```
>>> import marshal, dis
>>> pocFile = open('poc.py.pyc', 'rb') //create the file object. 
>>> pocFile.read(8) //read the first 8 bytes, since this is unnecessary magic numbers
>>> loaded = marshal.load(pocFile)
>>> dis.dis(loaded)
```
When we run the last line of code, we face a really long assembly. Now, when we examine the assembly we see a lot of NOP and a bunch of unnecessary instructions, namely ROT_THREE, ROT_TWO and LOAD_CONST immediately followed by POP, all of which we need to get rid of. Fortunately, we have bytecode_graph (https://github.com/fireeye/flare-bytecode_graph), which we can use for such task. For example, if we want to eliminate NOP, we can do the following:
```
import bytecode_graph
from dis import opmap

bcg = bytecode_graph.BytecodeGraph(code) //code refers to loaded variable from the previous script
 for current in bcg.nodes():
        if current.opcode == opmap['NOP']:
             bcg.delete_node(current)
```
Now we just need to output this code into a file. We can use ```marshal.dump()```, and then write this object to a file. Do not forget to copy the first 8 bytes into this output file first!

All we have left to do is to decompile this .pyc file. This time use **uncompyle**. 

```
uncompyle6.exe -o output.py .\modifiedASM.pyc
```
The file ```output.py``` should look like this!
```
# uncompyle6 version 2.9.6
# Python bytecode 2.7 (62211)
# Decompiled from: Python 2.7.12 (v2.7.12:d33e0cf91556, Jun 27 2016, 15:19:22) [MSC v.1500 32 bit (Intel)]
# Embedded file name: poc.py
# Compiled at: 2016-12-04 12:18:36
import sys
import random
__version__ = 'Flare-On ultra python obfuscater 2000'
target = random.randint(1, 101)
count = 1
error_input = ''
while True:
    print '(Guesses: %d) Pick a number between 1 and 100:' % count,
    input = sys.stdin.readline()
    try:
        input = int(input, 0)
    except:
        error_input = input
        print 'Invalid input: %s' % error_input
        continue

    if target == input:
        break
    if input < target:
        print 'Too low, try again'
    else:
        print 'Too high, try again'
    count += 1

if target == input:
    win_msg = 'Wahoo, you guessed it with %d guesses\n' % count
    sys.stdout.write(win_msg)
if count == 1:
    print 'Status: super guesser %d' % count
    sys.exit(1)
if count > 25:
    print 'Status: took too long %d' % count
    sys.exit(1)
else:
    print 'Status: %d guesses' % count
if error_input != '':
    tmp = ''.join((chr(ord(x) ^ 66) for x in error_input)).encode('hex')
    if tmp != '312a232f272e27313162322e372548':
        sys.exit(0)
    stuffs = [67, 139, 119, 165, 232, 86, 207, 61, 79, 67, 45, 58, 230, 190, 181, 74, 65, 148, 71, 243, 246, 67, 142, 60, 61, 92, 58, 115, 240, 226, 171]
    import hashlib
    stuffer = hashlib.md5(win_msg + tmp).digest()
    for x in range(len(stuffs)):
        print chr(stuffs[x] ^ ord(stuffer[x % len(stuffer)])),
```
When we look at this code, we can see this wierd snippet that has nothing to do with the guessing game itself. It's not hard to see that for the print at the end of this code, win_msg, which contains the count of the guesses, is the only thing that can be affected by user_input. Since we know that 1 < count <= 25, we can slightly alter this last bit of code to brute-force which count value yields the flag!
```
import hashlib

tmp = '312a232f272e27313162322e372548'
for count in range(2, 26):
    win_msg = 'Wahoo, you guessed it with %d guesses\n' % count
    stuffs = [67, 139, 119, 165, 232, 86, 207, 61, 79, 67, 45, 58, 230, 190, 181, 74, 65, 148, 71, 243, 246, 67, 142, 60, 61, 92, 58, 115, 240, 226, 171]
    stuffer = hashlib.md5(win_msg + tmp).digest()
    string = ''
    for x in range(len(stuffs)):
        string += chr(stuffs[x] ^ ord(stuffer[x % len(stuffer)]))
    print(string)
```
This outputs the flag
```
1mp0rt3d_pygu3ss3r@flare-on.com
```
... together with some gibberish. 
