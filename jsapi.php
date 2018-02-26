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
		$lines = "";

		echo "<table style='width:100%'>";
		echo "<tr><th>When</th><th>Type</th><th>Hostname</th><th>Client</th><th>Status</th><th>Action</th></tr>";

		$query = "select *,UNIX_TIMESTAMP(`when`) as `when` from `dnslog` where `when` >= now() - INTERVAL 1 DAY order by `when` desc limit 50";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			$status = "OK (forwarded)";
			if($row['status'] == "config")
				$status = "blocked";
			if($row['status'] == "cached")
				$status = "OK (cached)";

			echo "<tr><td>".date("H:i:s", $row['when'])."</td><td>${row['qtype']}</td><td><a target='_blank' href='http:/"."/${row['hostname']}'>${row['hostname']}</a></td><td>${row['client']}</td><td>$status</td><td>";

			if($status == "blocked")
			{
				echo "<a class='btn btn-success' target='_blank' href='https://adfree.odiousapps.com/exceptions.php?hostname=${row['hostname']}&whiteblack=white'>Whitelist</a>";
			} else {
				echo "<a class='btn btn-danger' target='_blank' href='https://adfree.odiousapps.com/exceptions.php?hostname=${row['hostname']}&whiteblack=black'>Blacklist</a>";
			}

			echo "</td></tr>";
		}

		echo "</table>";
	}

	function dnsmasqBlockedHosts()
	{
		global $link;

		echo "<table style='width:100%'>\n";
		echo "<tr><th>Hostname</th><th>DNS Hits</th><th>Action</th></tr>";

		$query = "select `hostname`, count(`hostname`) as `hits`, `status` from `dnslog` where `status`='config' and `when` >= now() - INTERVAL 30 DAY group by `hostname` order by count(`hostname`) desc";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			echo "<tr><td><a target='_blank' href='http:/"."/${row['hostname']}'>${row['hostname']}</a></td><td>".$row['hits']."</td><td>";
			echo "<a class='btn btn-success' target='_blank' href='https://adfree.odiousapps.com/exceptions.php?hostname=${row['hostname']}&whiteblack=white'>Whitelist</a>";
			echo "</td></tr>";
		}

		echo "</table>";
	}

	function HostAPd()
	{
		echo trim(file_get_contents("/var/log/hostapd.log"));
	}
