<?php
	session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
	{
		header("location: login.php");
		exit;
	}

	require_once("/var/www/html/mysql.php");

	$hostname = "";
	if(isset($_REQUEST['hostname']))
		$hostname = trim(mysqli_real_escape_string($link, $_REQUEST['hostname']));

	if(isset($_REQUEST['dnsmasqLog']))
		dnsmasqLog($hostname);

	if(isset($_REQUEST['dnsmasqBlockedHosts']))
		dnsmasqBlockedHosts();

	if(isset($_REQUEST['HostAPd']))
		HostAPd();

	exit;

	function dnsmasqLog($hostname = "", $maxlen = 30)
	{
		global $link;
		$lines = "";

		echo "<table style='width:100%'>";
		echo "<tr><th>When</th><th>Type</th><th style='width:250px;'>Hostname</th><th>Client</th><th>Status</th><th>Action</th></tr>";

		$query = "select *,UNIX_TIMESTAMP(`when`) as `when` from `dnslog` where `when` >= now() - INTERVAL 30 DAY order by `when` desc limit 50";
		if($hostname != "")
			$query = "select *,UNIX_TIMESTAMP(`when`) as `when` from `dnslog` where `hostname`='$hostname' and `when` >= now() - INTERVAL 30 DAY order by `when` desc limit 50";

		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			$status = "OK (forwarded)";
			if($row['status'] == "config")
				$status = "blocked";
			if($row['status'] == "cached")
				$status = "OK (cached)";

			$len = strlen($row['hostname']);
			echo "<tr><td>".date("H:i:s", $row['when'])."</td><td>${row['qtype']}</td><td><a href='blacklist.php?hostname=${row['hostname']}' title='http:/"."/".$row['hostname']."'>";
			echo @substr($row['hostname'], 0, $maxlen);
			if($len > $maxlen)
				echo "...";
			echo "</a></td><td>${row['client']}</td><td>$status</td><td>";

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

	function dnsmasqBlockedHosts($maxlen = 60)
	{
		global $link;

		echo "<table style='width:100%'>\n";
		echo "<tr><th style='width:550px;'>Hostname</th><th>DNS Hits</th><th>Action</th></tr>";

		$query = "select `hostname`, count(`hostname`) as `hits`, `status` from `dnslog` where `status`='config' and `when` >= now() - INTERVAL 30 DAY group by `hostname` order by count(`hostname`) desc";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			$len = strlen($row['hostname']);
			echo "<tr><td><a href='blacklist.php?hostname=${row['hostname']}' title='${row['hostname']}'>";
			echo @substr($row['hostname'], 0, $maxlen);
			if($len > $maxlen)
				echo "...";
			echo "</a></td><td>".$row['hits']."</td><td>";
			echo "<a class='btn btn-success' target='_blank' href='https://adfree.odiousapps.com/exceptions.php?hostname=${row['hostname']}&whiteblack=white'>Whitelist</a>";
			echo "</td></tr>";
		}

		echo "</table>";
	}

	function HostAPd()
	{
		echo trim(file_get_contents("/var/log/hostapd.log"));
	}
