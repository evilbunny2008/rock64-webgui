#!/usr/bin/php
<?php
	if(getmyuid() != 0)
	{
		echo "You must run this script as root.\n";
		exit(1);
	}

	require_once("/var/www/html/mysql.php");

	$start = 0;

	if(isset($argv['1']))
	{
		$row['when'] = strtotime("2018-03-04 00:00:00");
	} else {
		$query = "select unix_timestamp(`when`) as `when` from `dnsStats` order by `when` DESC limit 1";
		$res = mysqli_query($link, $query);
		$rows = mysqli_num_rows($res);
		$row = mysqli_fetch_assoc($res);
	}

	if($rows == 0)
	{
		$query = "select unix_timestamp(`when`) as `when` from `dnslog` order by `when` ASC limit 1";
		$res = mysqli_query($link, $query);
		$row = mysqli_fetch_assoc($res);
	}

	if(!isset($row['when']) || $row['when'] == 0)
	{
		echo "No data found in dnslog.\n";
		exit(1);
	}

	if(isset($row['when']))
		$start = $row['when'];

	$start = $start - ($start % 300);

	$now = time() - 300;

	for($i = $start; $i <= $now; $i += 300)
	{
		$j = date("Y-m-d H:i:s", $i + 300);
		$query = "select `when` from `dnsStats` where `when`='$j'";
		$res = mysqli_query($link, $query);
		if(mysqli_num_rows($res) > 0)
			continue;

		$query = "select `when` from `dnslog` where `when` >= '".date("Y-m-d H:i:s", $i)."' and `when` <= '".date("Y-m-d H:i:s", $i + 299)."' and `status`='config'";
		$res = mysqli_query($link, $query);
		$config = mysqli_num_rows($res);

		$query = "select `when` from `dnslog` where `when` >= '".date("Y-m-d H:i:s", $i)."' and `when` <= '".date("Y-m-d H:i:s", $i + 299)."' and `status`='forwarded'";
		$res = mysqli_query($link, $query);
		$forwarded = mysqli_num_rows($res);

		$query = "select `when` from `dnslog` where `when` >= '".date("Y-m-d H:i:s", $i)."' and `when` <= '".date("Y-m-d H:i:s", $i + 299)."' and `status`='cached'";
		$res = mysqli_query($link, $query);
		$cached = mysqli_num_rows($res);

		$dt = date("Y-m-d H:i:s", $i + 300);

		$query = "insert into `dnsStats` set `when`='$dt', `cached`='$cached', `forwarded`='$forwarded', `config`='$config'";
		mysqli_query($link, $query);
	}
