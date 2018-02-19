<?php
	if(!isset($page))
		$page = 0;
?>﻿
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?=$pageTitle?></title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
<?php if(isset($refresh) && $refresh >= 60) { ?>
    <meta http-equiv="refresh" content="<?=$refresh?>">
<?php } ?>
</head>
<body>
    <div id="wrapper">
         <div class="navbar navbar-inverse navbar-fixed-top">
            <div class="adjust-nav">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">
                        <img src="assets/img/logo.png" />
                    </a>
                </div>
                <span class="logout-spn" style="float:left;padding-left:190px;">
<?php
	if(isset($_SESSION['login']) && $_SESSION['login'] == true)
	{
?>
		    <a href="reboot.php" class="btn btn-default" onClick="return confirm('Are you sure you want to do this?');">Reboot</a>
		    <a href="logout.php" class="btn btn-default">Logout</a>
<?php } else { ?>
		    <a href="login.php" class="btn btn-default">Login</a>
<?php } ?>
                </span>
            </div>
        </div>
        <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
                    <?php if($page == 1) { ?><li class="active-link"><?php } else { ?><li><?php } ?>
                        <a href="index.php"><i class="fa fa-desktop"></i>Network Dashboard</a>
                    </li>
		    <?php if($page == 2) { ?><li class="active-link"><?php } else { ?><li><?php } ?>
                        <a href="dashboard.php" ><i class="fa fa-desktop"></i>WiFi Dashboard</a>
                    </li>
                    <?php if($page == 3) { ?><li class="active-link"><?php } else { ?><li><?php } ?>
                        <a href="ethernet.php"><i class="fa fa-signal"></i>Ethernet Settings</a>
                    </li>
                    <?php if($page == 4) { ?><li class="active-link"><?php } else { ?><li><?php } ?>
                        <a href="wifi.php"><i class="fa fa-signal"></i>WiFi Client Settings</a>
                    </li>
                    <?php if($page == 5) { ?><li class="active-link"><?php } else { ?><li><?php } ?>
                        <a href="hotspot.php"><i class="fa fa-dot-circle-o"></i>WiFi Hotspot Settings</a>
                    </li>
                    <?php if($page == 6) { ?><li class="active-link"><?php } else { ?><li><?php } ?>
                        <a href="auth.php"><i class="fa fa-lock"></i>User Settings</a>
                    </li>
                    <?php if($page == 7) { ?><li class="active-link"><?php } else { ?><li><?php } ?>
                        <a href="other.php"><i class="fa fa-table "></i>Server Settings</a>
                    </li>
                    <?php if($page == 8) { ?><li class="active-link"><?php } else { ?><li><?php } ?>
                        <a href="blacklist.php"><i class="fa fa-table "></i>DNS Blacklist</a>
                    </li>
                    <?php if($page == 9) { ?><li class="active-link"><?php } else { ?><li><?php } ?>
                        <a href="openvpnc.php"><i class="fa fa-table "></i>VPN Client</a>
                    </li>
                </ul>
            </div>
        </nav>
