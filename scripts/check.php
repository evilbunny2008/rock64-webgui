#!/usr/bin/php -q
<?php
	if(getmyuid() != 0)
	{
		echo "You must run this script as root.\n";
		exit(1);
	}

	if(!is_dir("/etc/webgui"))
		$do = `mkdir -p /etc/webgui`;

	$do = `rm -f /etc/webgui/update.txt`;

	$do = trim(`/var/www/html/scripts/check.sh 2>&1`);
	if($do != "")
		$do = `touch /etc/webgui/update.txt`;
