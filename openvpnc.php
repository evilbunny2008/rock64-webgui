<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	if(!is_dir("/etc/openvpn/client"))
	{
		$do = `sudo mkdir -p "/etc/openvpn/client"`;
	}

	if(isset($_POST['remove']))
	{
		$do = `sudo rm -f "/etc/openvpn/client/client1.ovpn"`;
		$do = `sudo rm -f "/etc/openvpn/client/client1.active"`;
	}

	if(isset($_POST['button']))
	{
		$file = escapeshellarg($_FILES['file']['tmp_name']);
		$do = `sudo mv $file "/etc/openvpn/client/client1.ovpn"`;
	}

	if(isset($_POST['update']))
	{
		if(isset($_POST['enableCli']))
		{
			$do = `sudo touch "/etc/openvpn/client/client1.active"`;
			$do = `sudo /usr/sbin/openvpn --config "/etc/openvpn/client/client1.ovpn"`;
		} else {
			$do = `sudo rm -f "/etc/openvpn/client/client1.active"`;
			$do = `sudo killall -TERM openvpn`;
		}
	}

	$enableCli = "no";
	if(file_exists("/etc/openvpn/client/client1.active"))
	{
		$enableCli = "yes";
	}

	$page = 9;
	$pageTitle = "OpenVPN Settings";
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
		    <form enctype="multipart/form-data" action="<?=$_SERVER['PHP_SELF']?>" method="POST">
		    <input type="hidden" name="MAX_FILE_SIZE" value="50000" />
		    <div style="width:110px;float:left">Select .ovpn File:</div>
                    <input type="file" style="width:220px;float:left;margin-left:20px;" class="form-control" name="file" /><br style="clear:left;"/>
		    <input type="submit" class="btn btn-primary" name="button" value="Upload File" />
<?php if(file_exists("/etc/openvpn/client/client1.ovpn")) { ?>
		    <input type="submit" class="btn btn-primary" name="remove" value="Remove File" />
<?php } ?>
		    </form>
		    <hr/>
		    <form action="<?=$_SERVER['PHP_SELF']?>" method="POST">
		    <div style="width:110px;float:left">Enable Client:</div>
                    <input type="checkbox" style="width:25px;float:left;" class="form-control" name="enableCli" value="yes"<?php if($enableCli == "yes") { echo " checked"; } ?>/><br style="clear:left;"/>
		    <input type="submit" class="btn btn-primary" name="update" value="Update" />
		    </form>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
