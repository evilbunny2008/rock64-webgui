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

	if(isset($_REQUEST['doStats']))
		doStats();

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
			$status = "<div title='Forwarded to ${row['DNSIP']}' style='text-decoration:underline;'>OK (forwarded)</div>";
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

	function doStats()
	{
		global $link;

//		header("Content-Type: text/plain");

		$blcount = trim(`cat "/etc/dnsmasq.d/adfree.conf"|wc -l`);

		$query = "select count(`hostname`) as `hostnames` from `dnslog` where `when` >= now() - INTERVAL 30 DAY";
		$res = mysqli_query($link, $query);
		$total = mysqli_fetch_assoc($res)['hostnames'];

		$query = "select count(`hostname`) as `hostnames` from `dnslog` where `status`='config' and `when` >= now() - INTERVAL 30 DAY";
		$res = mysqli_query($link, $query);
		$blocked = mysqli_fetch_assoc($res)['hostnames'];
		$percent = round($blocked / $total * 100, 2);

		$query = "select count(`client`) as `count` from `dnslog` where `when` >= now() - INTERVAL 30 DAY group by `client`";
		$res = mysqli_query($link, $query);
		$clis = mysqli_num_rows($res);
?>
		<div style="width:23%;height:100px;float:left;border:1px solid;padding:5px;margin:5px;">
			<div class="card-block">
				<h3 style="text-align:center;"><?=$total?></h3>
				<p>Total Queries (<?=$clis?> Clients)</p>
			</div>
		</div>
		<div style="width:23%;height:100px;float:left;border:1px solid;padding:5px;margin:5px;">
			<div class="card-block">
				<h3 style="text-align:center;"><?=$blocked?></h3>
				<p>Queries Blocked</p>
			</div>
		</div>
		<div style="width:23%;height:100px;float:left;border:1px solid;padding:5px;margin:5px;">
			<div class="card-block">
				<h3 style="text-align:center;"><?=$percent?>%</h3>
				<p>Percent Blocked</p>
			</div>
		</div>
		<div style="width:23%;height:100px;float:left;border:1px solid;padding:5px;margin:5px;">
			<div class="card-block">
				<h3 style="text-align:center;"><?=$blcount?></h3>
				<p>Hosts in the Blacklist</p>
			</div>
		</div>
		<br style="clear:left">

		<table class="stats" style="width:48%;float:left;">
		<tr><th>Hostname</th><th>Hits</th></tr>
<?php

		$query = "select `hostname`, count(`hostname`) as `count` from `dnslog` where `when` >= now() - INTERVAL 30 DAY group by `hostname` order by count(`hostname`) desc limit 10";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			echo "<tr><td>${row['hostname']}</td><td>${row['count']}</td></tr>\n";
		}
?>
		</table>
		<table class="stats" style="width:48%;float:left;">
		<tr><th>Client IP</th><th>Hits</th></tr>
<?php
		$query = "select `client`, count(`client`) as `count` from `dnslog` where `when` >= now() - INTERVAL 30 DAY group by `client` order by count(`client`) desc limit 10";
		$res = mysqli_query($link, $query);
		while($row = mysqli_fetch_assoc($res))
		{
			echo "<tr><td>${row['client']}</td><td>${row['count']}</td></tr>\n";
		}
		echo "</table>\n";
	}
