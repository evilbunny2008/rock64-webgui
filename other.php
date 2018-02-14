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

		$do = `echo $timezone | sudo tee "/etc/timezone"`;
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

	$hostname = trim(file_get_contents("/etc/hostname"));
	$domain = trim(file_get_contents("/etc/mailname"));
	list($crud, $domain) = explode(".", $domain, 2);
	$timezone = trim(file_get_contents("/etc/timezone"));

	$page = 7;
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
		    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
		    <div style="width:160px;float:left">System Hostname:</div>
		    <input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="hostname" value="<?=$hostname?>" placeholder="Enter a Hostname" /><br style="clear:left;"/>
		    <div style="width:160px;float:left">System Domain:</div>
		    <input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="domain" value="<?=$domain?>" placeholder="Enter a Domain" /><br style="clear:left;"/>
		    <div style="width:160px;float:left">System TimeZone:</div>
		    <select name="timezone" class="form-control" style="width:300px;float:left;margin-left:20px;">
<?php
	foreach($timezones as $tz)
	{
?>
			<option value="<?=$tz?>"<?php if($timezone == $tz) { ?> selected<?php } ?>><?=$tz?></option>
<?php } ?>
		    </select><br style="clear:left;"/>
		    <input type="submit" class="btn btn-primary" name="button" value="Update Settings" />
		    </form>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
