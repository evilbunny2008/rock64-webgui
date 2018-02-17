<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	if(isset($_POST['button']) && $_POST['button'] == "Connect")
	{
		$wificard = escapeshellarg(trim($_POST['int']));
		$wificard2 = substr($wificard, 1, -1);
		$ssid = escapeshellarg(trim($_POST['ssid']));
		$passphrase = $psk = "";
		if($_POST['isEnc'] == 1)
		{
			$passphrase = substr(escapeshellarg(trim($_POST['passphrase'])), 1, -1);
			$psk = "\nwpa-psk \"$passphrase\"";
		}

		if(file_exists("/etc/network/interfaces.d/$wificard2"))
		{
			$do = `sudo ifdown --force $wificard`;
			$do = `sudo ifconfig $wificard 0.0.0.0 down`;
		}

		// TODO: allow to set static ip
		$cmd = "echo 'auto $wificard2\nallow-hotplug $wificard2\niface $wificard2 inet dhcp\nwpa-ssid $ssid$psk' | sudo tee '/etc/network/interfaces.d/$wificard2'";
		$do = `$cmd`;
		$do = `sudo ifup $wificard`;
	} else if(isset($_POST['button']) && $_POST['button'] == "Remove") {
		$wificard = escapeshellarg(trim($_POST['int']));
		$wificard2 = substr($wificard, 1, -1);
		if(file_exists("/etc/network/interfaces.d/$wificard2"))
		{
			$do = `sudo ifdown --force $wificard`;
			$do = `sudo ifconfig $wificard 0.0.0.0 down`;
			$do = `sudo rm "/etc/network/interfaces.d/$wificard2"`;
		}
	}

	$lines = explode("\n", trim(`nmcli dev stat|grep wifi`));
	$wifiArr = array();
	$i = 0;
	foreach($lines as $line)
	{
		$line = trim($line);
                list($wifi, $crud) = explode(" ", str_replace("  ", " ", $line), 2);
		if($wifi == 'p2p0')
			continue;

		$i++;
		$wifiArr[$i]['int'] = $wifi;
		if(!isset($wificard2))
		{
			$wificard = escapeshellarg(trim($wifiArr[$i]['int']));
			$wificard2 = substr($wificard, 1, -1);
		}

		if(isset($_POST['int']) && $_POST['int'] == $wifiArr[$i]['int'])
		{
			$wificard = escapeshellarg(trim($wifiArr[$i]['int']));
			$wificard2 = substr($wificard, 1, -1);
		}
	}

	if(isset($wificard))
	{
		$wifi = array();
		$do = `sudo ifconfig $wificard up`;
		$APs = trim(`iwlist $wificard scanning`);
		$APs = explode("Cell", $APs);
		unset($APs['0']);

		$i = 0;
		foreach($APs as $AP)
		{
			$i++;
			$lines = explode("\n", trim($AP));

			foreach($lines as $line)
			{
				$line = trim($line);
				if(strpos($line, "ESSID:") !== false)
					$wifi[$i]['SSID'] = substr($line, 7, -1);
				if(strpos($line, "Protocol:") !== false)
					$wifi[$i]['protocol'] = substr($line, 9);
				if(strpos($line, "Frequency:") !== false)
					$wifi[$i]['freq'] = substr($line, 10);
				if(strpos($line, "Encryption key:off") !== false)
					$wifi[$i]['enc'] = 0;
				if(strpos($line, "Encryption key:on") !== false)
					$wifi[$i]['enc'] = 1;
				if(isset($wifi[$i]['enc']) && $wifi[$i]['enc'] && @strpos($line, "IE: IEEE 802.11i/WPA2 Version") !== false)
					$wifi[$i]['wpa'] = "2";
				if(isset($wifi[$i]['enc']) && $wifi[$i]['enc'] && @strpos($line, "Group Cipher : ") !== false)
					$wifi[$i]['cipher'] = substr($line, 15);
				if(strpos($line, "Signal level=") !== false)
					$wifi[$i]['dBm'] = trim(substr($line, strpos($line, "Signal level=") + 13));
				if(strpos($line, "Quality=") !== false)
					$wifi[$i]['quality'] = trim(substr($line, strpos($line, "Quality=") + 8, 7));
			}
		}
	}

	$page = 4;
	$pageTitle = "WiFi Client Settings";
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
		<div class="row" style="padding-left:10px;">
			<form method="post" action="<?=$_SERVER['PHP_SELF']?>">
			<div style="width:75px;float:left">Interface:</div>
				<select name="int" class="form-control" style="width:150px;float:left;">
<?php for($i = 1; $i <= count($wifiArr); $i++) { ?>
					<option value="<?=$wifiArr[$i]['int']?>"<?php if(isset($_POST['int']) && $wifiArr[$i]['int'] == $wificard2) { ?> selected<?php } ?>><?=$wifiArr[$i]['int']?></option>
<?php } ?>
				</select>
				<input class="btn btn-primary" style="width:75px;float:left;margin-left:20px;" type="submit" value="Rescan"/>
			</form>
			<br style="clear:left;"/>
			<br style="clear:left;"/>
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>SSID</th>
                                    <th>Signal Strength</th>
                                    <th>Channel</th>
                                    <th>Security</th>
                                    <th>Pass Phrase</th>
				    <th>Connect</th>
                                </tr>
                            </thead>
                            <tbody>
<?php for($i = 1; $i <= count($wifi); $i++) { ?>
				<form method="post"  action="<?=$_SERVER['PHP_SELF']?>">
				<input type="hidden" name="ssid" value="<?=$wifi[$i]['SSID']?>" />
				<input type="hidden" name="int" value="<?=$wificard2?>" />
                                <tr>
                                    <td><?=$wifi[$i]['SSID']?></td>
				    <td>
<?php
	list($percent, $crud) = explode("/", $wifi[$i]['quality'], 2);
	$percent = intval($percent);
?>
				    <div class="progress progress-striped" title="Signal: <?=$percent?>%">
					<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?=$percent?>" aria-valuemin="0" aria-valuemax="100" style="width: <?=$percent?>%">
					    <span class="sr-only"><?=$percent?>% Complete (success)</span>
					</div>
				    </div>
				    </td>
                                    <td><?=$wifi[$i]['freq']?></td>
                                    <td><?php
	if($wifi[$i]['enc'] == 0)
	{
		echo "Open <input type=\"hidden\" name=\"isEnc\" value=\"0\" />";
	} else {
		if($wifi[$i]['wpa'] == 2)
			echo "WPA2";
		else
			echo "WPA1";
		echo " (".$wifi[$i]['cipher'].") <input type=\"hidden\" name=\"isEnc\" value=\"1\" />";
	}
?>
				</td>
				<td>
<?php if($wifi[$i]['enc'] != 0) { ?>
					<input type="text" class="form-control" name="passphrase" placeholder="Enter router pass pharse" />
<?php } else { ?>
					&nbsp;
<?php } ?>
				</td>
				<td><input type="submit" class="btn btn-primary" name="button" value="Connect" /> <input type="submit" class="btn btn-danger" name="button" value="Remove" />
				</td>
			    </form>
			    </tr>
<?php } ?>
			    <tr>
				<form method="post" action="<?=$_SERVER['PHP_SELF']?>">
				<input type="hidden" name="int" value="<?=$wificard2?>" />
				<td>
				    <input type="text" class="form-control" name="ssid" placeholder="Enter a hidden SSID" style="width:150px;"/>
				</td>
				<td>
				    n/a
				</td>
				<td>
				    n/a
				</td>
				<td>
				    <select name="isEnc" class="form-control" style="width:150px;">
					<option value="1">WPA/WPA2 (CCMP)</option>
					<option value="0">Open</option>
				    </select>
				</td>
				<td>
				    <input type="text" class="form-control" name="passphrase" placeholder="Enter router pass pharse" style="width:150px;"/>
				</td>
				<td>
				    <input type="submit" class="btn btn-primary" name="button" value="Connect"/>
				</td>
				</form>
			    </tr>
			</tbody>
		    </table>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
