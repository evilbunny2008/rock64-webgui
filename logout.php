<?php
        session_start();

        if($_SESSION['login'] == true)
		$_SESSION['login'] = false;

	header("location: login.php");
	exit;

