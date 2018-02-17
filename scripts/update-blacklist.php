#!/usr/bin/php
<?php
	$username = $password = "";

	if(file_exists("/etc/adfree.txt"))
		$ini = parse_ini_file("/etc/adfree.conf");

	$url = "https://adfree-hosts.odiousapps.com/dnsmasq.php";

	if($username != "" && $password != "")
		$url .= "?username="..urlencode($username)."&password=".urlencode($password);

	$data = gzdecode(file_get_contents($url));
	$fp = fopen("/etc/dnsmasq.d/adblock.conf", "w");
	fputs($fp, $data);
	fclose($fp);
