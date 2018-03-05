<?php
        session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$okmsg = $errmsg = "";
	if(isset($_POST['button']) && !isset($_POST['enableBL']))
		$do = `sudo rm -f "/etc/adfree.conf" "/etc/cron.daily/blacklist.sh" "/etc/dnsmasq.d/adfree.conf"`;

	if((isset($_POST['button']) || isset($_POST['dlBlacklist'])) && isset($_POST['enableBL']) && $_POST['enableBL'] == "yes")
	{
		$email = substr(escapeshellarg($_POST['email']), 1, -1);
		$passphrase = substr(escapeshellarg($_POST['passphrase']), 1, -1);

		if($email != "" && !filter_var($email, FILTER_VALIDATE_EMAIL))
			$errmsg = "Invalid email address";

		if($errmsg == "" && $email != "" && $passphrase == "")
		{
			if(file_exists("/etc/adfree.conf"))
			{
				$do = `sudo cat "/etc/adfree.conf"`;
				list($email, $passphrase) = explode("\n", trim($do), 2);
				list($crud, $email) = explode("=", $email, 2);
				list($crud, $passphrase) = explode("=", $passphrase, 2);
			}
		}

		if($errmsg == "" && $email != "" && $passphrase != "")
		{
			$url = "https://adfree-hosts.odiousapps.com/dnsmasq.php";
                	$url .= "?username=".urlencode($email)."&password=".urlencode($passphrase);
			$ret = file_get_contents($url."&checkup=1");
			if($ret != "ok")
				$errmsg = $ret;
			else
				$okmsg = "Email and Passphrase was accepted and saved.";
		}

		if($errmsg == "")
		{
			$do = `echo "email=$email\npassphrase=$passphrase" | sudo tee "/etc/adfree.conf"`;
			$do = `sudo chmod 600 "/etc/adfree.conf"`;
			$do = `sudo echo "#!/bin/sh\n\n/var/www/html/scripts/update-blacklist.php" | sudo tee "/etc/cron.daily/blacklist.sh"`;
			$do = `sudo chmod 755 "/etc/cron.daily/blacklist.sh"`;
		}
	}

	if(isset($_POST['dlBlacklist']) && isset($_POST['enableBL']) && $_POST['enableBL'] == "yes")
	{
		if(!file_exists("/etc/adfree.conf"))
		{
			$do = `sudo touch "/etc/adfree.conf"`;
			$do = `sudo chmod 600 "/etc/adfree.conf"`;
		}

		$do = `sudo "/var/www/html/scripts/update-blacklist.php"`;
		$okmsg = "The system has updated the Blacklist.";
	} else if(isset($_POST['dlBlacklist']) && !isset($_POST['enableBL'])) {
		$errmsg = "Blacklisting isn't enabled.";
	}

	$enableBL = "no";
	if(file_exists("/etc/adfree.conf"))
	{
		$enableBL = "yes";
		$do = `sudo cat "/etc/adfree.conf"`;
		@list($email, $passphrase) = explode("\n", trim($do), 2);
		@list($crud, $email) = explode("=", $email, 2);
		@list($crud, $passphrase) = explode("=", $passphrase, 2);
	}

	$email = urlencode($email);
	$passphrase = urlencode($passphrase);

	$hostname = "";
	if(isset($_REQUEST['hostname']))
		$hostname = trim($_REQUEST['hostname']);

	$page = 8;
	$pageTitle = "Blacklist Settings";
	include_once("header.php");

	$tab = 1;
	if($hostname != "" || isset($_REQUEST['viewAll']))
		$tab = 2;
?>
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row" style="padding-right:15px;padding-left:15px;">
                    <div class="col-md-12">
                        <h2><?=$pageTitle?></h2>
                    </div>
                </div>
                <hr />
		<div class="row" style="padding-right:15px;">
                    <div class="col-lg-4 col-md-4">
                        <ul class="nav nav-tabs">
                            <li class="<?php if($tab == 1) echo "active";?>"><a href="#home" data-toggle="tab">Home</a></li>
                            <li class="<?php if($tab == 2) echo "active";?>"><a href="#logging" data-toggle="tab">Logging</a></li>
                            <li class=""><a href="#hostnames" data-toggle="tab">Blocked Hostnames</a></li>
                            <li class=""><a href="#stats" data-toggle="tab">Statistics</a></li>
                            <li class=""><a href="#graphs" data-toggle="tab">Graphs</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade<?php if($tab == 1) echo " active in";?>" id="home">
				<h4>Home</h4>
				<div class="row" style="padding-right:15px;padding-left:15px;">
<?php if($errmsg != "") { ?>
                		    <p><div class="alert alert-warning alert-dismissable"><?=$errmsg?><button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button></div></p>
<?php } ?>
<?php if($okmsg != "") { ?>
		                    <p><div class="alert alert-success alert-dismissable"><?=$okmsg?><button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button></div></p>
<?php } ?>
				    <form method="post" autocomplete="off" action="<?=$_SERVER['PHP_SELF']?>">
					<div style="width:140px;float:left">Enable blacklisting?</div>
					<input type="checkbox" style="width:25px;float:left;margin-left:20px;" class="form-control" name="enableBL" value="yes"<?php if($enableBL == "yes") { echo " checked"; } ?> /><br style="clear:left;"/>
					<p><a target="_blank" href="https://adfree.odiousapps.com">Adfree</a> is a crowdsourced list of advertising hostnames which can be used for free, with or without an account, although donations are welcome.</p>
					<div style="width:140px;float:left">Adfree email:</div>
					<input type="text" style="width:200px;float:left;" class="form-control" name="email" value="<?=urldecode($email)?>" placeholder="Adfree email" /><br style="clear:left;"/>
					<div style="width:140px;float:left">Adfree Passphrase:</div>
					<input type="text" style="width:200px;float:left;" class="form-control" id="passphrase" name="passphrase" placeholder="Adfree passphrase" /><br style="clear:left;"/>
					<input type="submit" class="btn btn-primary" name="button" value="Save Settings" />
					<input type="submit" class="btn btn-primary" name="dlBlacklist" value="Download Blacklist Now" />
				    </form>
				</div>
			    </div>
			    <div class="tab-pane fade<?php if($tab == 2) echo " active in";?>" id="logging">
				<h4>Logging</h4>
				<div class="row" style="padding-right:15px;padding-left:15px;width:800px;">
				    <div style="width:150px;float:left">Auto refresh every 60s</div><input type="checkbox" style="width:25px;float:left;margin-left:10px;" class="form-control" checked id="autoRefresh1"><br style="clear:left;"/>
					<?php if($hostname != "") { ?><a href='blacklist.php?viewAll=1'>View All</a><br style="clear:left;"/><?php } ?>
				    <div class="panel panel-primary">
					<div class="panel-heading">DNS Logs</div>
					<div class="panel-body" id="panel-body1">
					</div>
				    </div>
				</div>
			    </div>
			    <div class="tab-pane fade" id="hostnames">
				<h4>Blocked Hostnames</h4>
				<div class="row" style="padding-right:15px;padding-left:15px;width:800px;">
				    <div style="width:150px;float:left;">Auto refresh every 60s</div><input type="checkbox" style="width:25px;float:left;margin-left:10px;" class="form-control" checked id="autoRefresh2"><br style="clear:left;"/>
				    <div class="panel panel-primary">
			    		<div class="panel-heading">Blocked Hostnames</div>
					<div class="panel-body" id="panel-body2">
					</div>
				    </div>
				</div>
			    </div>
			    <div class="tab-pane fade" id="stats">
				<h4>Statistics</h4>
				<div class="row" style="padding-right:15px;padding-left:15px;width:800px;">
				    <div style="width:150px;float:left">Auto refresh every 60s</div><input type="checkbox" style="width:25px;float:left;margin-left:10px;" class="form-control" checked id="autoRefresh3"><br style="clear:left;"/>
				    <div class="panel panel-primary">
			    		<div class="panel-heading">DNS Stats</div>
					<div class="panel-body" id="panel-body3">
					</div>
				    </div>
				</div>
			    </div>
			    <div class="tab-pane fade" id="graphs">
				<h4>Graphs</h4>
				<div class="row" style="padding-right:15px;padding-left:15px;width:800px;">
				    <div class="panel panel-primary">
			    		<div class="panel-heading">DNS Graphs</div>
					<div class="panel-body" id="panel-body4">
					    <iframe style="border:0px;width:750px;height:400px;" src="graph.php"></iframe>
					</div>
				    </div>
				</div>
			    </div>
			</div>
		    </div>
		</div>
	    </div>
        </div>
<script type="text/javascript" charset="utf-8">
<!--//
	var http1 = getHTTPObject();
	var http2 = getHTTPObject();
	var http3 = getHTTPObject();

	function getHTTPObject()
	{
		var request = null;
		if(typeof XMLHttpRequest != 'undefined')
		{
			request = new XMLHttpRequest();
		} else {
			try
			{
				request = new ActiveXObject('Msxml2.XMLHTTP')
			} catch(e) {
				try
				{
					request = new ActiveXObject('Microsoft.XMLHTTP')
				} catch(e) {
					request = null
				}
			}
		}

		return request;
	}

	function switchType()
	{
		document.getElementById('passphrase').type = "password";
	}

	function updateDisplay(name, val)
	{
		var element = document.getElementById(name);
		if(!element)
			return;

		element.innerHTML = val;
	}

	function updateLog()
	{
		setTimeout("updateLog();", 60000);

		if(document.getElementById("autoRefresh1").checked)
		{
			try
			{
				http1.open('GET', '/jsapi.php?dnsmasqLog=1<?php if($hostname != "") echo "&hostname=$hostname"; ?>&date='+new Date().getTime(), true);
				http1.onreadystatechange = function()
				{
					if(http1.readyState == 4 && http1.status == 200)
						updateDisplay('panel-body1', http1.responseText);
				}

				http1.send();
			} catch (e) {}
		}
	}

	function updateHosts()
	{
		setTimeout("updateHosts();", 60000);

		if(document.getElementById("autoRefresh2").checked)
		{
			try
			{
				http2.open('GET', '/jsapi.php?dnsmasqBlockedHosts=1&date='+new Date().getTime(), true);
				http2.onreadystatechange = function()
				{
					if(http2.readyState == 4 && http2.status == 200)
						updateDisplay('panel-body2', http2.responseText);

				}

				http2.send();
			} catch (e) {}
		}
	}

	function doStats()
	{
		setTimeout("doStats();", 60000);

		if(document.getElementById("autoRefresh3").checked)
		{
			try
			{
				http3.open('GET', '/jsapi.php?doStats=1&date='+new Date().getTime(), true);
				http3.onreadystatechange = function()
				{
					if(http3.readyState == 4 && http3.status == 200)
						updateDisplay('panel-body3', http3.responseText);
				}

				http3.send();
			} catch (e) {}
		}
	}

	setTimeout("switchType();", 1000);
	updateLog();
	updateHosts();
	doStats();
//-->
</script>
<?php include_once("footer.php"); ?>
