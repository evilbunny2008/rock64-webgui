<?php
        session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$errmsg = $okmsg = "";

	if(!is_dir("/etc/openvpn/client"))
	{
		$do = `sudo mkdir -p "/etc/openvpn/client"`;
	}

	if(isset($_POST['remove']))
	{
		$do = `sudo rm -f "/etc/openvpn/client/client1.key"`;
		$do = `sudo rm -f "/etc/openvpn/client/client1.ovpn"`;
		$do = `sudo rm -f "/etc/openvpn/client/client1.active"`;
	}

	if(isset($_POST['tbUpload']))
	{
		$ovpn = trim(file_get_contents($_FILES['ovpnfile']['tmp_name']));
		$ca = trim(file_get_contents($_FILES['CAfile']['tmp_name']));
		$cert = trim(file_get_contents($_FILES['Certfile']['tmp_name']));
		$key = trim(file_get_contents($_FILES['Key']['tmp_name']));

		list($crud, $cert) = explode("-----BEGIN CERTIFICATE-----", $cert, 2);
		$cert = "-----BEGIN CERTIFICATE-----".$cert;

		list($start, $end) = explode("auth-user-pass", $ovpn, 2);
		$ovpn = $start."auth-user-pass /etc/openvpn/client/client1.key".$end;

		$ovpn .= "\n<ca>\n$ca\n</ca>\n<cert>\n$cert\n</cert>\n<key>\n$key\n</key>";
		$ovpn = escapeshellarg(trim($ovpn));

		$auth = escapeshellarg(trim($_REQUEST['username'])."\n".trim($_REQUEST['passphrase']));

		$do = `echo -n $auth | sudo tee '/etc/openvpn/client/client1.key'`;
		$do = `echo -n $ovpn | sudo tee '/etc/openvpn/client/client1.ovpn'`;

		restartOVPN();
	}

	if(isset($_POST['button']))
	{
		$do = `sudo killall -TERM openvpn`;
		if(isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != "")
		{
			$file = escapeshellarg($_FILES['file']['tmp_name']);
			$do = `sudo mv $file "/etc/openvpn/client/client1.ovpn"`;
			$do = `sudo chown -R root: "/etc/openvpn/"`;

			if(isset($_POST['passphrase']) || strpos($ovpn, "-----BEGIN ENCRYPTED PRIVATE KEY-----") !== false)
			{
				$passphrase = substr(escapeshellarg(trim($_POST['passphrase'])), 1, -1);

				$ovpn = trim(`sudo cat "/etc/openvpn/client/client1.ovpn"`);
				if(strpos($ovpn, "-----BEGIN ENCRYPTED PRIVATE KEY-----") !== false)
				{
					list($start, $rest) = explode("<key>", $ovpn, 2);
					list($key, $rest) = explode("</key>", $rest, 2);

					$start = trim($start);
					$key = trim($key);
					$rest = trim($rest);

					$fn1 = tempnam("/tmp", "ovpn");
					$fn2 = tempnam("/tmp", "ovpn");
					$fp = fopen($fn1, "w");
					fputs($fp, $key);
					fclose($fp);

					$cmd = "openssl rsa -in '$fn1' -out '$fn2' -passin 'pass:$passphrase'";
					$do = `$cmd`;

					$key = trim(`sudo cat '$fn2'`);
					if($key != "")
					{
						$ovpn = $start."\n<key>\n$key\n</key>\n$rest";
						$do = `echo "$ovpn" | sudo tee "/etc/openvpn/client/client1.ovpn"`;
					} else {
						$errmsg = "Invalid passphrase supplied, please check your passphrase and retry uploading your ovpn file.";
						$do = `sudo rm -f "/etc/openvpn/client/client1.ovpn"`;
						$do = `sudo rm -f "/etc/openvpn/client/client1.active"`;
					}

					unlink($fn1);
					unlink($fn2);
				}
			}

			if((!isset($_POST['passphrase']) || $_POST['passphrase'] == "") && strpos($ovpn, "-----BEGIN ENCRYPTED PRIVATE KEY-----") !== false)
			{
				$errmsg = "ovpn file requires a passphrase, but you didn't supply one.";
				$do = `sudo rm -f "/etc/openvpn/client/client1.ovpn"`;
				$do = `sudo rm -f "/etc/openvpn/client/client1.active"`;
			}
		} else {
			$errmsg = "No ovpn file was uploaded, can't continue";
			$do = `sudo rm -f "/etc/openvpn/client/client1.ovpn"`;
			$do = `sudo rm -f "/etc/openvpn/client/client1.active"`;
		}

		if($errmsg == "")
		{
			if(file_exists("/etc/openvpn/client/client1.active"))
			{
				restartOVPN();
				$okmsg = ".ovpn file was successfully imported and openvpn was successfully started";
			} else {
				$okmsg = ".ovpn file was successfully imported";
			}
		} else {
			$do = `sudo rm -f "/etc/openvpn/client/client1.active"`;
			$do = `sudo rm -f "/etc/openvpn/client/client1.ovpn"`;
		}
	}

	if(isset($_POST['update']))
	{
		if(isset($_POST['enableCli']))
		{
			$do = `sudo touch "/etc/openvpn/client/client1.active"`;
			restartOVPN();
			$okmsg = "openvpn was successfully started";
		} else {
			$do = `sudo rm -f "/etc/openvpn/client/client1.active"`;
			$do = `sudo killall openvpn`;

			sleep(4);

			$okmsg = "openvpn was successfully stopped";
		}
	}

	function restartOVPN()
	{
		if(intval(trim(`ps auxww|grep -v grep|grep openvpn|wc -l`)) > 0)
		{
			$do = `sudo killall openvpn`;
			$do = `sudo killall openvpn`;
			$do = `sudo killall openvpn`;
			sleep(1);
		}

		$do = `sudo /usr/sbin/openvpn --verb 6 --config "/etc/openvpn/client/client1.ovpn" --daemon`;
		sleep(5);
	}

	$enableCli = "no";
	if(file_exists("/etc/openvpn/client/client1.active"))
		$enableCli = "yes";

	$tunnel = "Down";
	if(trim(`sudo ifconfig tun0|grep UP 2>&1`) != "")
		$tunnel = "Up";

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
<?php if($errmsg != "") { ?>
                    <p><div class="alert alert-warning alert-dismissable"><?=$errmsg?><button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button></div></p>
<?php } ?>
<?php if($okmsg != "") { ?>
                    <p><div class="alert alert-success alert-dismissable"><?=$okmsg?><button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button></div></p>
<?php } ?>
		    <form enctype="multipart/form-data" action="<?=$_SERVER['PHP_SELF']?>" method="POST">
		    <input type="hidden" name="MAX_FILE_SIZE" value="50000" />
		    <div style="width:120px;float:left">Select .ovpn File:</div>
                    <input type="file" style="width:220px;float:left;margin-left:20px;" class="form-control" name="file" /><br style="clear:left;"/>
		    <div style="width:120px;float:left">.ovpn Passphrase:</div>
                    <input type="password" style="width:220px;float:left;margin-left:20px;" class="form-control" name="passphrase" placeholder="Enter the Passphrase" /><br style="clear:left;"/>
		    <input type="submit" class="btn btn-primary" name="button" value="Upload File" />
<?php if(file_exists("/etc/openvpn/client/client1.ovpn")) { ?>
		    <input type="submit" class="btn btn-primary" name="remove" value="Remove Files" />
<?php } ?>
		    </form>
		    <hr />
		    <form enctype="multipart/form-data" action="<?=$_SERVER['PHP_SELF']?>" method="POST">
		    <input type="hidden" name="MAX_FILE_SIZE" value="50000" />
		    <div style="width:120px;float:left">Select .ovpn File:</div>
		    <input type="file" style="width:220px;float:left;margin-left:20px;" class="form-control" name="ovpnfile" /><br style="clear:left;"/>
		    <div style="width:120px;float:left">Select CA File:</div>
		    <input type="file" style="width:220px;float:left;margin-left:20px;" class="form-control" name="CAfile" /><br style="clear:left;"/>
		    <div style="width:120px;float:left">Select Cert File:</div>
		    <input type="file" style="width:220px;float:left;margin-left:20px;" class="form-control" name="Certfile" /><br style="clear:left;"/>
		    <div style="width:120px;float:left">Select Private File:</div>
		    <input type="file" style="width:220px;float:left;margin-left:20px;" class="form-control" name="Key" /><br style="clear:left;"/>
		    <div style="width:120px;float:left">VPN Username:</div>
                    <input type="text" style="width:220px;float:left;margin-left:20px;" class="form-control" name="username" placeholder="Enter the Username" /><br style="clear:left;"/>
		    <div style="width:120px;float:left">VPN Passphrase:</div>
                    <input type="text" style="width:220px;float:left;margin-left:20px;" class="form-control" name="passphrase" placeholder="Enter the Passphrase" /><br style="clear:left;"/>
		    <input type="submit" class="btn btn-primary" name="tbUpload" value="Upload Files" />
<?php if(file_exists("/etc/openvpn/client/client1.ovpn")) { ?>
		    <input type="submit" class="btn btn-primary" name="remove" value="Remove Files" />
<?php } ?>
		    </form>
		    <hr/>
		    <form action="<?=$_SERVER['PHP_SELF']?>" method="POST">
		    <div style="width:120px;float:left">Tunnel is:</div><?=$tunnel?><br style="clear:left;"/>
		    <div style="width:120px;float:left">Enable:</div>
                    <input type="checkbox" style="width:25px;float:left;" class="form-control" name="enableCli" value="yes"<?php if($enableCli == "yes") { echo " checked"; } ?>/><br style="clear:left;"/>
		    <input type="submit" class="btn btn-primary" name="update" value="Update" />
		    </form>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
