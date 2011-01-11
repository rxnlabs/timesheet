<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Timesheet</title>
</head>
<form action="<?php $_SERVER['PHP_SELF'];?>" method="POST">
<table border="1">
<th>Day</th>
<th>Location</th>
<th>Clock-In</th>
<th>Clock-Out</th>
<th>Submit</th>
<tr>
<td>
<select name="day" style="width:100px">
<option value="Monday">Monday</option>
<option value="Tuesday">Tuesday</option>
<option value="Wednesday">Wednesday</option>
<option value="Thursday">Thursday</option>
<option value="Friday">Friday</option>
<option value="Saturday">Saturday</option>
<option value="Sunday">Sunday</option>
</select>
</td>
<td>
<select name="location" style="width:100px">
<option value="Helpdesk">Help Desk</option>
</select>
</td>
<td>
<input type="text" name="clockin" />
</td>
<td>
<input type="text" name="clockout" />
</td>
<td>
<button type="submit" value="submit" name="submit">Submit</button>
</td>
</tr>
</table>
<?php
$counter = 0;
$checkbox = 0;
if(isset($_POST['submit']))
{
	$day = $_POST['day'];
	$location = $_POST['location'];
	$deprecate = array(":",".");
	$check = strpos($_POST['clockin'],":");
	
	if($check == -1)
	{
		echo "you must include a semicolon in the time";
		die;
	}
	
	$clockin = explode(":",$_POST['clockin']);
	$clockout = explode(":",$_POST['clockout']);
	
	$clockin_hour = $clockin[0];
	$clockin_min = $clockin[1];
	$clockout_hour = $clockout[0];
	$clockout_min = $clockout[1];
	
	$clockin_new = str_replace($deprecate,"",$_POST['clockin']);
	$clockout_new = str_replace($deprecate,"",$_POST['clockout']);
	$cycles = 0;//determines how many times 40 is taken away from the time
	$extratime = 40;
	$myHours = 0;
	$totalHours = 0;
	
	if(is_numeric($clockin_hour.$clockin_min.$clockout_hour.$clockout_min))
	{
		$min = 0;
		$hour = 0;
		
		if($clockout_hour < $clockin_hour)
		{
			$clockout_hour += 12;
		}
		
		if($clockin_min > 59 or $clockout_min > 59)
		{
			echo "You can not have a minute be greater than 59";
			die;
		}
		
		if($clockin_hour> 24 or $clockout_hour > 24)
		{
			echo "You cannot have an hour be over 24:00 hours";
			die;
		}
		
		if($clockin_new > 2400 or $clockout_new > 2400)
		{
			echo "You cannot work over 24:00 hours. If you mean you worked from midnight, enter in 00:00 or 12:00";
			die;
		}
		
		$hour = $clockout_hour - $clockin_hour;
		$min = $clockout_min - $clockin_min;
		
		$hour *= 60;
		$Minutes = $hour + $min;
		
		
		
		/*
		while($clockin_min<$clockout_min)
		{
			$clockin_min++;
		}
		for(;$clockin_hour<$clockout_hour;$clockin_hour++)
		$time = $clockout_new - $clockin_new;
		$holder = $time;//make this value a holder for time
		while($holder > 60)
		{
			$holder -= 60;
			$cycles++;
		}
		
		$extratime = $cycles * $extratime;//for every value of 100, increase this value to take 40 more away
		$Minutes = ($time - $extratime);
		$Hours = $Minutes/60;
		round($Hours,2);
		*/
		
		$Hours = $Minutes/60;
		
		$clockin = $clockin_hour.":".$clockin_min;
		$clockout = $clockout_hour.":".$clockout_min;
		
		$getHours = fopen("hours.txt","a");
		fputs($getHours,"$day $location $clockin $clockout $Minutes $Hours");
		$getHours = fopen("hours.txt","r");
?>
<table border="1">
<th>Day</th>
<th>Location</th>
<th>Clock-in</th>
<th>Clock-out</th>
<th>Minutes</th>
<th>Hours</th>
<?php   
		while(!feof($getHours))
		{
			$info = fgets($getHours);
			$parts = explode(" ",$info);
			$test_one[$counter] = $parts[0];
			$test_two[$counter] = $parts[1];
			$test_three[$counter] = $parts[2];
			$test_four[$counter] = $parts[3];
			$test_five[$counter] = $parts[4];
			$test_six[$counter] = $parts[5];
			echo "<tr><td>$test_one[$counter]</td><td>$test_two[$counter]</td><td>$test_three[$counter]</td><td>$test_four[$counter]</td><td>$test_five[$counter]</td><td>$test_six[$counter]</td></tr>";
			$totalHours += $test_six[$counter];
			echo "Edit?<input type=\"checkbox\" name=\"$counter\" value=\"$counter\"/>";
			$counter++;
			
		}
?>
</table>
<?php
		$getHours = fopen("hours.txt","a");
		fputs($getHours,"\r\n");
		fclose($getHours);
		echo "My total hours are: ", round($totalHours,2);
	}
}
?>
<body>
</body>
</html>
