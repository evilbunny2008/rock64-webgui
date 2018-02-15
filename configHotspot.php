<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$ssid = $passphrase = "";
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
		if(!isset($wificard))
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

	$channel = 0;
	$channels = array("3" => "2422Mhz",
				"11" => "2462Mhz",
				"40" => "5200Mhz",
				"48" => "5240Mhz",
				"56" => "5280Mhz",
				"64" => "5320Mhz",
				"100" => "5500Mhz",
				"108" => "5540Mhz",
				"116" => "5580Mhz",
				"124" => "5620Mhz",
				"132" => "5660Mhz",
				"140" => "5700Mhz",
				"149" => "5745Mhz",
				"157" => "5775Mhz",
				"165" => "5825Mhz");

	if(isset($_POST['button']))
	{
		$ssid = $_POST['ssid'];
		$channel = intval($_POST['channel']);
		$wificard = escapeshellarg(trim($_POST['int']));
		$wificard2 = substr($wificard, 1, -1);
		$passphrase = str_replace('"', "'", escapeshellarg(trim($_POST['passphrase'])));

		if(file_exists("/etc/network/interfaces.d/$wificard2"))
		{
			$do = `sudo ifdown --force $wificard`;
			$do = `sudo ifconfig $wificard 0.0.0.0 down`;
			$do = `sudo killall -KILL wpa_supplicant`;
		}

		$mode = "g";
		if($channel > 14)
			$mode = "a";

		$hostapd = "# Pine64.org hostapd config for rtl8812au usb device\n\n".
				"ssid=$ssid\ninterface=$wificard2\nhw_mode=$mode\ncountry_code=GB\nchannel=$channel\ndriver=nl80211\n\n".
				"logger_syslog=0\nlogger_syslog_level=0\nwmm_enabled=1\nwpa=2\npreamble=1\n\n".
				"wpa_passphrase=$passphrase\nwpa_key_mgmt=WPA-PSK\nwpa_pairwise=CCMP\nrsn_pairwise=CCMP\nauth_algs=1\nmacaddr_acl=0\n\n".
				"ieee80211n=1\nieee80211d=1\n\n";

		if($channel > 14)
			$hostapd .= "ieee80211ac=1\n\n";

		$hostapd .= "ctrl_interface=/var/run/hostapd\nctrl_interface_group=0\n";

		$cmd = "echo '$hostapd' | sudo tee '/etc/hostapd/hostapd.conf'";
		$do = `$cmd`;

		$cmd = "echo 'auto $wificard2\nallow-hotplug $wificard2\niface $wificard2 inet static\naddress 192.168.99.1\nnetmask 255.255.255.0' | sudo tee '/etc/network/interfaces.d/$wificard2'";
		$do = `$cmd`;

		if(isset($_POST['enableNAT']))
		{
			$cmd = "echo 'post-up iptables -t nat -A POSTROUTING -s 192.168.99.0/24 ! -d 192.168.99.0/24 -j MASQUERADE' | sudo tee -a '/etc/network/interfaces.d/$wificard2'";
			$do = `$cmd`;
		}

		$cmd = "echo 'post-up /usr/sbin/hostapd -e /dev/urandom -B -P '/var/run/${wificard2}.pid' -f /var/log/hostapd.log /etc/hostapd/hostapd.conf' | sudo tee -a '/etc/network/interfaces.d/$wificard2'";
		$do = `$cmd`;

		if(isset($_POST['enableNAT']))
		{
			$cmd = "echo 'pre-down iptables -t nat -D POSTROUTING -s 192.168.99.0/24 ! -d 192.168.99.0/24 -j MASQUERADE' | sudo tee -a '/etc/network/interfaces.d/$wificard2'";
			$do = `$cmd`;
		}

		$cmd = "echo 'pre-down killall hostapd' | sudo tee -a '/etc/network/interfaces.d/$wificard2'";
		$do = `$cmd`;

		if(!file_exists('/etc/dnsmasq.conf'))
		{
			$cmd = "echo 'interface=$wificard2\nno-dhcp-interface=lo\ndhcp-range=192.168.99.100,192.168.99.199,255.255.255.0,1d' | sudo tee '/etc/dnsmasq.conf'";
			$do = `$cmd`;
			$do = `sudo /etc/init.d/dnsmasq restart`;
		}

		$cmd = "sudo sed -i -e 's/^#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/' /etc/sysctl.conf";
		$do = `$cmd`;

		$do = `sudo ifup $wificard`;
	}

	if(isset($_POST['disable']))
	{
		$do = `sudo ifdown --force $wificard`;
		$do = `sudo ifconfig $wificard 0.0.0.0 down`;
		$do = `sudo rm -f "/etc/network/interfaces.d/$wificard2"`;
		$do = `sudo rm -f "/etc/hostapd/hostapd.conf"`;
	}

	if(file_exists("/etc/hostapd/hostapd.conf"))
	{
		$fp = fopen("/etc/hostapd/hostapd.conf", "r");
		while(!feof($fp))
		{
			$line = trim(fgets($fp, 1024));
			if($line === false)
				break;

			if(strpos($line, "interface=") === 0)
			{
				list($crud, $wificard) = explode("=", $line, 2);
				$wificard = escapeshellarg(trim($wificard));
				$wificard2 = substr($wificard, 1, -1);
			}

			if(strpos($line, "channel=") === 0)
				list($crud, $channel) = explode("=", $line, 2);
			if(strpos($line, "ssid=") === 0)
				list($crud, $ssid) = explode("=", $line, 2);
			if(strpos($line, "wpa_passphrase=") === 0)
				list($crud, $passphrase) = explode("=", $line, 2);
		}
		fclose($fp);
	}

	if(isset($_POST['clearlog']) && file_exists('/var/log/hostapd.log'))
		$do = `echo -n | sudo tee '/var/log/hostapd.log'`;

	$enableNAT = 0;
	if(file_exists('/etc/network/interfaces.d/$wificard2'))
	{
		$cmd = "grep iptables '/etc/network/interfaces.d/$wificard2'|wc -l";
		$enableNAT = intval(trim(`$cmd`));
	} else {
		$enableNAT = 2;
	}

	$page = 4;
	$pageTitle = "WiFi Hotspot Settings";
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
                    <div class="col-lg-4 col-md-4">
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#home" data-toggle="tab">Home</a>
                            </li>
                            <li class=""><a href="#logging" data-toggle="tab">Logging</a>
                            </li>

                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade active in" id="home">
				<h4>Home</h4>
				<form method="post" action="<?=$_SERVER['PHP_SELF']?>">
	                        <div style="width:160px;float:left">Interface:</div>
                                <select name="int" class="form-control" style="width:300px;float:left;margin-left:20px;">
<?php for($i = 1; $i <= count($wifiArr); $i++) { ?>
                                        <option value="<?=$wifiArr[$i]['int']?>"<?php if($wifiArr[$i]['int'] == $wificard2) { ?> selected<?php } ?>><?=$wifiArr[$i]['int']?></option>
<?php } ?>
                                </select><br/>
				<div style="width:160px;float:left">SSID:</div>
				<input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="ssid" value="<?=$ssid?>" placeholder="Enter SSID" /><br style="clear:left;"/>
				<div style="width:160px;float:left">WiFi Channel:</div>
				<select class="form-control" name="channel" style="width:300px;float:left;margin-left:20px;">
<?php
	foreach($channels as $chan => $freq)
	{
?>
				    <option value="<?=$chan?>"<?php if($channel == $chan) { ?> selected <?php } ?>>Channel <?=$chan?> -- <?=$freq?></options>
<?php } ?>
				</select>
				<div style="width:160px;float:left">Pass Phrase:</div>
				<input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="passphrase" value="<?=$passphrase?>" placeholder="Enter Passphrase" /><br style="clear:left;"/>
				<div style="width:160px;float:left">Enable NAT:</div>
				<input type="checkbox" style="width:25px;float:left;margin-left:20px;" class="form-control" name="enableNAT" value="<?=$passphrase?>"<?php if($enableNAT == 2) { echo "checked"; } ?> /><br style="clear:left;"/>
				<input type="submit" class="btn btn-primary" name="button" value="Update and Re-start" />
				<input type="submit" class="btn btn-primary" name="disable" value="Disable" />
				</form>
                            </div>
                            <div class="tab-pane fade" id="logging">
                                <h4>Logging</h4>
                                <p>
<?php
	$log = explode("\n", trim(file_get_contents('/var/log/hostapd.log')));
	$loglines = array();
	$i = count($log) - 15;
	if($i < 0)
		$i = 0;

	for(; $i <= count($log); $i++)

		$loglines[] = $log[$i];

	$log = trim(implode("\n", $loglines));
?>
				    <textarea style="width:900px;height:500px;" id="textarea"><?=$log?></textarea>
				    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
					<input type="submit" class="btn btn-primary" name="clearlog" value="Clear log" />
				    </form>
                                </p>
                            </div>
                        </div>
                    </div>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
