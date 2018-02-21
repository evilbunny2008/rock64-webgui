<?php
        session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$okmsg = $errmsg = "";
	if(isset($_POST['button']) && !isset($_POST['enableBL']))
	{
		$do = `sudo rm -f "/etc/adfree.conf"`;
		$do = `sudo rm -f "/etc/cron.daily/blacklist.sh"`;
		$do = `sudo rm -f "/etc/dnsmasq.d/adfree.conf"`;
	}

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
		list($email, $passphrase) = @explode("\n", trim($do), 2);
		list($crud, $email) = @explode("=", $email, 2);
		list($crud, $passphrase) = @explode("=", $passphrase, 2);
	}

	$email = urlencode($email);
	$passphrase = urlencode($passphrase);

	$page = 8;
	$pageTitle = "Blacklist Settings";
	include_once("header.php");
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
                            <li class="active"><a href="#home" data-toggle="tab">Home</a>
                            </li>
                            <li class=""><a href="#logging" data-toggle="tab">Logging</a>
                            </li>
                            <li class=""><a href="#hostnames" data-toggle="tab">Hostnames</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade active in" id="home">
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
			<input type="text" style="width:200px;float:left;margin-left:20px;" class="form-control" name="email" value="<?=urldecode($email)?>" placeholder="Adfree email" /><br style="clear:left;"/>
			<div style="width:140px;float:left">Adfree Passphrase:</div>
			<input type="text" style="width:200px;float:left;margin-left:20px;" class="form-control" id="passphrase" name="passphrase" placeholder="Adfree passphrase" /><br style="clear:left;"/>
			<input type="submit" class="btn btn-primary" name="button" value="Save Settings" />
			<input type="submit" class="btn btn-primary" name="dlBlacklist" value="Download Blacklist Now" />
		    </form>
				</div>
			    </div>
			    <div class="tab-pane fade" id="logging">
				<h4>Logging</h4>
				<div class="row" style="padding-right:15px;padding-left:15px;">
				    <div style="width:150px;float:left">Auto refresh every 30s</div><input type="checkbox" style="width:25px;float:left;margin-left:10px;" class="form-control" checked id="autoRefresh1"><br style="clear:left;"/>
				    <textarea cols="60" rows="15" wrap="off" readonly="readonly" id="textarea"></textarea>
				</div>
			    </div>
			    <div class="tab-pane fade" id="hostnames">
				<h4>Blocked Hostnames</h4>
				<div class="row" style="padding-right:15px;padding-left:15px;">
				    <div style="width:150px;float:left">Auto refresh every 30s</div><input type="checkbox" style="width:25px;float:left;margin-left:10px;" class="form-control" checked id="autoRefresh2"><br style="clear:left;"/>
				    <div class="panel panel-primary" style="width:325px;float:left;margin-right:20px;">
			    		<div class="panel-heading">Blocked Hostnames</div>
					    <div class="panel-body" id="panel-body">
					    </div>
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
		if(name == "textarea")
			document.getElementById('textarea').scrollTop = 9999999;
	}

	function updateCliTable(val)
	{
		var element = document.getElementById('panel-body');
		if(!element)
			return;

		element.innerHTML = val;
	}

	function updateLog()
	{
		setTimeout("updateLog();", 30000);

		if(document.getElementById("autoRefresh1").checked)
		{
			try
			{
				http1.open('GET', '/jsapi.php?dnsmasqLog=1&date='+new Date().getTime(), true);
				http1.onreadystatechange = function()
				{
					if(http1.readyState == 4 && http1.status == 200)
						updateDisplay('textarea', http1.responseText);
				}

				http1.send();
			} catch (e) {}
		}
	}

	function updateHosts()
	{
		setTimeout("updateHosts();", 30000);

		if(document.getElementById("autoRefresh2").checked)
		{
			try
			{
				http2.open('GET', '/jsapi.php?dnsmasqHosts=1&date='+new Date().getTime(), true);
				http2.onreadystatechange = function()
				{
					if(http2.readyState == 4 && http2.status == 200)
						updateCliTable(http2.responseText);
				}

				http2.send();
			} catch (e) {}
		}
	}

	setTimeout("switchType();", 5000);
	updateLog();
	updateHosts();
//-->
</script>
<?php include_once("footer.php"); ?>
