<?php
	session_start();

	if($_SESSION['login'] == true)
	{
		header("location: index.php");
		exit;
	}

	$username = $passphrase = "";
	$errmsg = "";
	if(isset($_POST['button']))
	{
		$username = escapeshellarg(trim($_POST['username']));
		$passphrase = escapeshellarg(trim($_POST['passphrase']));
		exec("sudo /var/www/html/chkpasswd $username $passphrase", $output, $ret);

		if($ret != 0)
		{
			$errmsg = "Invalid username or password";
		} else {
			$_SESSION['login'] = true;
			header("location: index.php");
			exit;
		}
	}

	$pageTitle = "System Login";
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
		    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
		    <div style="width:160px;float:left">Username:</div>
		    <input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="username" placeholder="Enter your Username" /><br style="clear:left;"/>
		    <div style="width:160px;float:left">Passphrase:</div>
		    <input type="password" style="width:300px;float:left;margin-left:20px;" class="form-control" name="passphrase" placeholder="Enter your Passphrase" /><br style="clear:left;"/>
		    <input type="submit" class="btn btn-primary" name="button" value="Login" />
		    </form>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
