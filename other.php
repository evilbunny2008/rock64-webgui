<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$timezones = array(
			"Australia/Adelaide",
			"Australia/Brisbane",
			"Australia/Darwin",
			"Australia/Perth",
			"Australia/Sydney",
			"Europe/Berlin",
			"Europe/London",
			"US/Central",
			"US/Eastern",
			"US/Pacific");

	if(isset($_POST['button']))
	{
		$timezone = escapeshellarg(trim($_POST['timezone']));
		$timezone2 = substr($timezone, 1, -1);

		$do = `echo -n $timezone | sudo tee "/etc/timezone"`;
		$do = `sudo dpkg-reconfigure -f noninteractive tzdata`;
		$do = `sudo ln -sf "/usr/share/zoneinfo/$timezone2" /etc/localtime`;

		$hostname = substr(escapeshellarg(trim($_POST['hostname'])), 1, -1);
		$domain = substr(escapeshellarg(trim($_POST['domain'])), 1, -1);

		$do = `echo -n "$hostname" | sudo tee "/etc/hostname"`;
		$do = `echo -n "$hostname.$domain" | sudo tee "/etc/mailname"`;
		$do = `echo "127.0.0.1 localhost " | sudo tee "/etc/hosts"`;
		$do = `echo "127.0.1.1 $hostname $hostname.$domain\n" | sudo tee -a "/etc/hosts"`;
		$do = `echo "# The following lines are desirable for IPv6 capable hosts" | sudo tee -a "/etc/hosts"`;
		$do = `echo "::1 localhost ip6-localhost ip6-loopback" | sudo tee -a "/etc/hosts"`;
		$do = `echo "fe00::0 ip6-localnet" | sudo tee -a "/etc/hosts"`;
		$do = `echo "ff00::0 ip6-mcastprefix" | sudo tee -a "/etc/hosts"`;
		$do = `echo "ff02::1 ip6-allnodes" | sudo tee -a "/etc/hosts"`;
		$do = `echo "ff02::2 ip6-allrouters" | sudo tee -a "/etc/hosts"`;
	}

	$okmsg = $errmsg = "";
	$pkgStatus = 0;
	$getStatus = trim(`dpkg --list|grep rtl8812au-dkms`);
	if($getStatus == "")
		$pkgStatus = 0;
	if(substr($getStatus, 0, 2) == "iF" || substr($getStatus, 0, 2) == "pF")
		$pkgStatus = 1;
	if(substr($getStatus, 0, 2) == "ii")
		$pkgStatus = 2;

	if(isset($_POST['remove']) && $pkgStatus == 2)
	{
		$do = `sudo dpkg --purge rtl8812au-dkms > /dev/null 2>/dev/null &`;
		$pkgStatus = 1;
	}

	if(isset($_POST['install']) && $pkgStatus == 0)
	{
		$do = `sudo dpkg -i /usr/src/rtl8812au-dkms_5.2.20-1_all.deb > /dev/null 2>/dev/null &`;
		$pkgStatus = 1;
	}

	if(isset($_POST['upgrade']))
	{
		$do = `sudo /var/www/html/scripts/upgrade.sh`;
		$okmsg = "The WebGUI has been updated to the most recent version.";
	}

	$hostname = trim(file_get_contents("/etc/hostname"));
	$domain = trim(file_get_contents("/etc/mailname"));
	list($crud, $domain) = explode(".", $domain, 2);
	$timezone = trim(file_get_contents("/etc/timezone"));

	$page = 7;
	$refresh = 60;
	$pageTitle = "Other Settings";
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
		<div class="row" style="padding-right:15px;padding-left:15px;">
<?php if($errmsg != "") { ?>
                    <p><div class="alert alert-warning alert-dismissable"><?=$errmsg?><button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button></div></p>
<?php } ?>
<?php if($okmsg != "") { ?>
                    <p><div class="alert alert-success alert-dismissable"><?=$okmsg?><button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button></div></p>
<?php } ?>
		    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
		    <div style="width:140px;float:left">System Hostname:</div>
		    <input type="text" style="width:200px;float:left;margin-left:20px;" class="form-control" name="hostname" value="<?=$hostname?>" placeholder="Enter a Hostname" /><br style="clear:left;"/>
		    <div style="width:140px;float:left">System Domain:</div>
		    <input type="text" style="width:200px;float:left;margin-left:20px;" class="form-control" name="domain" value="<?=$domain?>" placeholder="Enter a Domain" /><br style="clear:left;"/>
		    <div style="width:140px;float:left">System TimeZone:</div>
		    <select name="timezone" class="form-control" style="width:200px;float:left;margin-left:20px;">
<?php
	foreach($timezones as $tz)
	{
?>
			<option value="<?=$tz?>"<?php if($timezone == $tz) { ?> selected<?php } ?>><?=$tz?></option>
<?php } ?>
		    </select><br style="clear:left;"/>
		    <input type="submit" class="btn btn-primary" name="button" value="Update Settings" />
		    </form>
		    <br/><br/>
		</div>
		<div class="row" style="padding-right:15px;padding-left:15px;">
		    <h2>RTL8812AU Driver</h2>
		    <hr />
		    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
		    <p>Do you need to install or remove the rtl8812au driver? This is for the 802.11ac usb device sold on the
			<a href="https://www.pine64.org/?product=rock64-usb-3-0-dual-band-1200mbps-wifi-802-11abgnac-rtl8812au-adapter" target="_blank">Pine64.org website</a></p>
<?php if($pkgStatus == 2) { ?>
		    <input type="submit" class="btn btn-primary" name="remove" value="Remove Driver" onClick="return confirm('Are you sure you want to do this?');" />
<?php } elseif($pkgStatus == 0) { ?>
		    <p>Installing the driver takes a few minutes to complete, so please be patient about this.</p>
		    <input type="submit" class="btn btn-primary" name="install" value="Install Driver" />
<?php } else { ?>
		    <p>The driver is currently being removed or installed, this page will update when installation or removal is complete.</p>
<?php } ?>
		    </form>
		</div>
		<div class="row" style="padding-right:15px;padding-left:15px;">
		    <h2>Upgrade Rock64 WebGUI</h2>
		    <hr />
		    <p>This keeps your system up to date with all the featurs and bug fixes.</p>
		    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
		    <input type="submit" class="btn btn-primary" name="upgrade" value="Upgrade WebGUI" onClick="return confirm('Are you sure you want to do this?');" />
		    </form>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
