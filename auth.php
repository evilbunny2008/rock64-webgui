<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$username = $username1 = $passphrase = $passphrase1 = $passphrase2 = "";
	$errmsg = $okmsg = "";
	if(isset($_POST['button']))
	{
		$username = escapeshellarg(trim($_POST['username']));
		$username1 = escapeshellarg(trim($_POST['username1']));
		$passphrase = escapeshellarg(trim($_POST['passphrase']));
		$passphrase1 = escapeshellarg(trim($_POST['passphrase1']));
		$passphrase2 = escapeshellarg(trim($_POST['passphrase2']));
		exec("sudo /var/www/html/bs/chkpasswd $username $passphrase", $output, $ret);

		if($ret != 0)
		{
			$errmsg = "Invalid username or password";
		} else {
			if($passphrase1 != $passphrase2 || strlen($passphrase1) < 10)
			{
				$errmsg = "New passphrases must be identical and at least 8 characters long.";
			} else {
				if($username != $username1 && strlen($username1) > 2)
				{
					$do = `sudo userdel -f -r $username`;
					$do = `sudo useradd -m -G "adm,sudo,audio,video,plugdev,input,ssh" -s "/bin/bash" $username1`;
					$username = $username1;
				}

				$cmd = "echo '".substr($username, 1, -1).":".substr($passphrase1, 1, -1)."' | sudo chpasswd";
				$do = `$cmd`;
				$okmsg = "Username and/or passphrases have been updated.";
			}
		}
	}

	$userinfo = posix_getpwuid(1000);
	$page = 5;
	$pageTitle = "User Settings";
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
		    <div style="width:160px;float:left">Existing Username:</div>
		    <input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="username" value="<?=$userinfo['name']?>" placeholder="Existing Username" /><br style="clear:left;"/>
		    <div style="width:160px;float:left">New Username:</div>
		    <input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="username1" value="<?=substr($username1, 1, -1)?>" placeholder="Enter New Username" /><br style="clear:left;"/>
		    <div style="width:160px;float:left">Existing Passphrase:</div>
		    <input type="password" style="width:300px;float:left;margin-left:20px;" class="form-control" name="passphrase" placeholder="Enter the current Passphrase" /><br style="clear:left;"/>
		    <div style="width:160px;float:left">New Passphrase:</div>
		    <input type="password" style="width:300px;float:left;margin-left:20px;" class="form-control" name="passphrase1" placeholder="Enter a New Passphrase" /><br style="clear:left;"/>
		    <div style="width:160px;float:left">Re-enter Passphrase:</div>
		    <input type="password" style="width:300px;float:left;margin-left:20px;" class="form-control" name="passphrase2" placeholder="Re-enter your Passphrase" /><br style="clear:left;"/>
		    <input type="submit" class="btn btn-primary" name="button" value="Update User" />
		    </form>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
