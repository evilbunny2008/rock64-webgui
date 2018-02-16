<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$lines = explode("\n", trim(`nmcli dev stat|grep ethernet`));
	$ethArr = array();
	$i = 0;
	foreach($lines as $line)
	{
		$line = trim($line);

		$i++;
		$ethArr[$i]['int'] = $wifi;
		if(!isset($ethernet))
		{
	                $ethernet = escapeshellarg(trim($ethArr[$i]['int']));
        	        $ethernet2 = substr($ethernet, 1, -1);
		}

		if(isset($_POST['int']) && $_POST['int'] == $ethArr[$i]['int'])
		{
			$ethernet = escapeshellarg(trim($ethArr[$i]['int']));
                        $ethernet2 = substr($ethernet, 1, -1);
		}
	}

	if(isset($_POST['button']))
	{
		$ethernet = escapeshellarg(trim($_POST['int']));
		$ethernet2 = substr($ethernet, 1, -1);
                $IP = substr(escapeshellarg(trim($_POST['IP'])), 1, -1);
                $gw = substr(escapeshellarg(trim($_POST['gw'])), 1, -1);
                $nm = substr(escapeshellarg(trim($_POST['nm'])), 1, -1);

		if(file_exists("/etc/network/interfaces.d/$ethernet2"))
		{
			$do = `sudo ifdown --force "$ethernet2"`;
			$do = `sudo ifconfig "$ethernet2" 0.0.0.0 down`;
			$do = `sudo killall -KILL wpa_supplicant`;
		}


		$cmd = "echo 'auto $ethernet2\nallow-hotplug $ethernet2\niface $ethernet2 inet static\naddress $IP\nnetmask $nm' | sudo tee '/etc/network/interfaces.d/$ethernet2'";
		$do = `$cmd`;

		$do = `sudo ifconfig $ethernet up`;
		$do = `sudo ifup $ethernet`;
	}

	if(isset($_POST['disable']))
	{
		$do = `sudo ifdown --force $ethernet`;
		$do = `sudo ifconfig $ethernet 0.0.0.0 down`;
		$do = `sudo rm -f "/etc/network/interfaces.d/$ethernet2"`;
	}

	if($IP == "")
	{
		$IP = "192.168.100.2";
		$nm = "255.255.255.0";
		$gw = "192.168.100.1";
	}


	$page = 3;
	$pageTitle = "Ethernet Settings";
	include_once("header.php");
?>
        <div id="page-wrapper" >
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2><?=$pageTitle?></h2>
                    </div>
                </div>
                <hr />
		<div class="row" style="padding-left:15px;padding-right:15px;">
			<form method="post" action="<?=$_SERVER['PHP_SELF']?>">
			<div style="width:160px;float:left">Use DHCP:</div>

			<div style="width:200px;float:left;padding-left:50px;">
				<input style="width:25px;float:left" class="form-control" name="group20" type="radio" id="radio120" checked="checked">
				<label for="radio120">Yes</label>
			</div>

			<div style="width:100px;float:left">
				<input style="width:25px;float:left" class="form-control" name="group20" type="radio" id="radio121">
				<label for="radio121">No</label>
			</div>

			<br style="clear:left;"/>

			<div style="width:160px;float:left">Ethernet IP:</div>
                        <input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="IP" value="<?=$IP?>" placeholder="Enter Ethernet IP" /><br style="clear:left;"/>
			<div style="width:160px;float:left">Gateway IP:</div>
                        <input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="gw" value="<?=$gw?>" placeholder="Enter Gateway IP" /><br style="clear:left;"/>
                        <div style="width:160px;float:left">Netmask:</div>
                        <input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="nm" value="<?=$nm?>" placeholder="Enter Ethernet Netmask" /><br style="clear:left;"/>
			<input type="submit" class="btn btn-primary" name="button" value="Update and Re-start" />
			<input type="submit" class="btn btn-primary" name="disable" value="Disable" />
			</form>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
