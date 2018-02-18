#!/usr/bin/php
<?php
	$username = $passphrase = "";

        $enableBL = "no";
        if(file_exists("/etc/adfree.conf"))
        {
                $enableBL = "yes";
                $do = `cat "/etc/adfree.conf"`;
                list($username, $passphrase) = explode("\n", trim($do), 2);
                list($crud, $username) = explode("=", $username, 2);
                list($crud, $passphrase) = explode("=", $passphrase, 2);
        }

	$url = "https://adfree-hosts.odiousapps.com/dnsmasq.php";
	if($enableBL == "yes" && $username != "" && $passphrase != "")
		$url .= "?username=".urlencode($username)."&password=".urlencode($passphrase);

	$data = gzdecode(file_get_contents($url));
	$fp = fopen("/etc/dnsmasq.d/adfree.conf", "w");
	fputs($fp, $data);
	fclose($fp);

	$do = `/etc/init.d/dnsmasq restart`;
