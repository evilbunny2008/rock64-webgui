#!/usr/bin/php
<?php
	require_once("/var/www/html/mysql.php");

	$debug = false;

	$last = array();
	$lines = explode("\n", trim(`cat "/var/log/dnsmasq.log"`));
	for($i = 0; $i < count($lines); $i++)
	{
		$line = trim($lines[$i]);

		if(strpos($line, " dnsmasq[") === false)
			continue;
		if(strpos($line, " using nameserver ") !== false)
			continue;
		if(strpos($line, " started, version ") !== false)
			continue;
		if(strpos($line, " compile time options: ") !== false)
			continue;
		if(strpos($line, " read /etc/hosts ") !== false)
			continue;
		if(strpos($line, " exiting on receipt of ") !== false)
			continue;
		if(strpos($line, " warning: interface ") !== false)
			continue;
		if(strpos($line, " no servers found in ") !== false)
			continue;
		if(strpos($line, " reading /run/dnsmasq/resolv.conf") !== false)
			continue;

		list($datetime, $rest) = explode(" dnsmasq[", $line, 2);
		list($crud, $rest) = explode("]: ", $rest, 2);
		$bits = explode(" ", $rest, 6);
		if(count($bits) != 6)
			continue;

		list($qid, $crud, $qtype, $host, $dir, $IP) = $bits;

		$datetime = date("Y-m-d H:i:s", strtotime($datetime));
		if($qtype == "query[A]" || $qtype == "query[AAAA]")
			$query = array('datetime' => $datetime, 'qid' => $qid, 'qtype' => $qtype, 'host' => $host, 'dir' => $dir, 'IP' => $IP);
		else
			continue;

		$i++;
		$line = trim($lines[$i]);
		list($datetime, $rest) = explode(" dnsmasq[", $line, 2);
		list($crud, $rest) = explode("]: ", $rest, 2);
		list($qid, $crud, $qtype, $host, $dir, $IP) = explode(" ", $rest, 6);

		$datetime = date("Y-m-d H:i:s", strtotime($datetime));

		if($qtype == "forwarded" || $qtype == "cached" || $qtype == "config")
			$query1 = array('datetime' => $datetime, 'qid' => $qid, 'qtype' => $qtype, 'host' => $host, 'dir' => $dir, 'IP' => $IP);

		if($query['qid'] != $query1['qid'])
			continue;

		$qtype = "A";
		if($query['qtype'] == "query[AAAA]")
			$qtype = "AAAA";

		$query = "insert into `dnslog` set `qid`='${query['qid']}', `when`='$datetime', `qtype`='$qtype', `hostname`='$host', `client`='${query['IP']}', `status`='${query1['qtype']}', `DNSIP`='${query1['IP']}'";
		if(mysqli_query($link, $query) !== false && $debug)
			echo $query."\n";
	}

