<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$_SESSION['login'] = false;
	header("location: login.php");
	$do = `sudo /var/www/html/reboot.sh > /dev/null 2>/dev/null &`;
	echo $do;
