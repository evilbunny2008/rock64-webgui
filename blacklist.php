<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	if(isset($_POST['enableBL']) && $_POST['enableBL'] == "no")
	{
		$do = `sudo rm -f /etc/adfree.conf`;
	}

	if(isset($_POST['button']) && isset($_POST['enableBL']) && $_POST['enableBL'] == "yes")
	{
		$username = substr(escapeshellarg($_POST['username']), 1, -1);
		$passphrase = substr(escapeshellarg($_POST['passphrase']), 1, -1);

		$do = `echo "username=$username\npassphrase=$passphrase" | sudo tee "/etc/adfree.conf"`;
		$do = `sudo chmod 600 "/etc/adfree.conf"`;
	}

	if(isset($_POST['dlblacklist']))
	{
		$do = `sudo "/var/www/html/scripts/update-blacklist.php" > /dev/null 2>/dev/null &`;
		$okmsg = "The system is now updating the Blacklist.";
	}

	$enableBL = "no";
	if(file_exists("/etc/adfree.conf"))
	{
		$enableBL = "yes";
		$do = `sudo cat "/etc/adfree.conf"`;
		list($username, $passphrase) = explode("\n", trim($do), 2);
		list($crud, $username) = explode("=", $username, 2);
		list($crud, $passphrase) = explode("=", $passphrase, 2);
	}

	$username = urlencode($username);
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
		<div class="row" style="padding-right:15px;padding-left:15px;">
<?php if($okmsg != "") { ?>
                    <p><div class="alert alert-success alert-dismissable"><?=$okmsg?><button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button></div></p>
<?php } ?>
		    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
			<div style="width:140px;float:left">Enable blacklisting?</div>
			<input type="checkbox" style="width:25px;float:left;" class="form-control" name="enableBL" value="yes"<?php if($enableBL == "yes") { echo " checked"; } ?> /><br style="clear:left;"/>
			<p>If you wish to use custom whitelisting and blacklist in conjunction with <a target="_blank" href="https://adfree.odiousapps.com">Adfree</a>, you can set your account details below, if you don't plan to have custom lists you can leave the below fields blank.</p>
			<div style="width:140px;float:left">Adfree Username:</div>
			<input type="text" style="width:200px;float:left;margin-left:20px;" class="form-control" name="username" value="<?=$username?>" placeholder="Adfree Username" /><br style="clear:left;"/>
			<div style="width:140px;float:left">Adfree Passphrase:</div>
			<input type="text" style="width:200px;float:left;margin-left:20px;" class="form-control" name="passphrase" value="<?=$passphrase?>" placeholder="Adfree passphrase" /><br style="clear:left;"/>
			<input type="submit" class="btn btn-primary" name="button" value="Save Settings" />
			<input type="submit" class="btn btn-primary" name="dlblacklist" value="Download Blacklist" />
		    </form>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
