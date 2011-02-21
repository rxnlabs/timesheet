<html>
<head>
<title>Connecting to the MySqL Server Using PHP</title>
</head>
<body>

<?php

//Address error handling
ini_set('display errors',1);
error_reporting(E_ALL & ~E_NOTICE);

//Attempt to connect
if($connection = @mysql_connect('mysql8.000webhost.com','a2824373_dw19412','inuyasha1'))
{
print '<p>Successfully connected to MySQL.</p>';

if(@mysql_select_db("a2824373_dw19412",$connection))
{
	print'<p>The dw19412 database has been selected.</p>';
}
else
{
	die('<p>Could not select the dw19412 database beacuse:<b>'.mysql_error().'>/b></p>');
}
mysql_close();//Close the connection
}
else
{
	die('<p>Could not connect to MySQL because:<b>'.mysql_error().'</b>M/p>');
}

?>
</body>
</html>