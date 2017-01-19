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


```
```
```
```
```
```
```
```
```

