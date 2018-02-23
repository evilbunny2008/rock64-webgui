<?php
	session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
	{
		header("location: login.php");
		exit;
	}

	require_once("/var/www/html/mysql.php");

	if(isset($_REQUEST['dnsmasqLog']))
		dnsmasqLog();

	if(isset($_REQUEST['dnsmasqBlockedHosts']))
		dnsmasqBlockedHosts();

	if(isset($_REQUEST['HostAPd']))
		HostAPd();

	exit;

	function dnsmasqLog()
	{
		global $link;
		$query = "select * from `dnslog` where `when` >= now() - INTERVAL 1 DAY order by `when` desc";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
			echo "${row['when']}: ${row['qtype']} (${row['hostname']}) from ${row['client']}\n";
	}

	function dnsmasqBlockedHosts()
	{
		global $link;

		echo "<table style='width:100%'>\n";
		echo "<tr><th>Hostname</th><th>DNS Hits</th></tr>\n";

		$query = "select `hostname`, count(`hostname`) as `hits`, `status` from `dnslog` where `status`='config' and `when` >= now() - INTERVAL 1 DAY group by `hostname` order by count(`hostname`) desc";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
			echo "<tr><td>".$row['hostname']."</td><td>".$row['hits']."</td></tr>\n";

		echo "</table>";
	}

	function HostAPd()
	{
		echo trim(file_get_contents("/var/log/hostapd.log"));
	}
