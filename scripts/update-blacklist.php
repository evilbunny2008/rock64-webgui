#!/usr/bin/php
<?php
	$email = $passphrase = "";

        if(!file_exists("/etc/adfree.conf"))
		exit;

	$do = trim(file_get_contents("/etc/adfree.conf"));
	list($email, $passphrase) = @explode("\n", trim($do), 2);
	list($crud, $email) = @explode("=", $email, 2);
	list($crud, $passphrase) = @explode("=", $passphrase, 2);

	$url = "https://adfree-hosts.odiousapps.com/dnsmasq.php";
	if($email != "" && $passphrase != "")
		$url .= "?username=".urlencode($email)."&password=".urlencode($passphrase);

	$data = gzdecode(file_get_contents($url));
	$fp = fopen("/etc/dnsmasq.d/adfree.conf", "w");
	fputs($fp, $data);
	fclose($fp);

	$do = `/etc/init.d/dnsmasq restart`;
