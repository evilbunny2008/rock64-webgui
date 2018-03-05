<?php
        session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$okmsg = $errmsg = $ssid = $passphrase = "";
	$lines = explode("\n", trim(`nmcli dev stat|grep wifi|sort -n`));
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
                $dhcpIP = substr(escapeshellarg(trim($_POST['dhcpIP'])), 1, -1);
                $dhcpstart = substr(escapeshellarg(trim($_POST['dhcpstart'])), 1, -1);
                $dhcpstop = substr(escapeshellarg(trim($_POST['dhcpstop'])), 1, -1);
                $dhcpnm = substr(escapeshellarg(trim($_POST['dhcpnm'])), 1, -1);
                $dhcptime = substr(escapeshellarg(trim($_POST['dhcptime'])), 1, -1);
                $enableAC = isset($_POST['enableAC']);

		if(file_exists("/etc/network/interfaces.d/$wificard2"))
		{
			$do = `sudo ifdown --force "$wificard2"`;
			$do = `sudo ifconfig "$wificard2" 0.0.0.0 down`;
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

		if($channel > 14 && $enableAC == 1)
			$hostapd .= "ieee80211ac=1\n\n";

		$hostapd .= "ctrl_interface=/var/run/hostapd\nctrl_interface_group=0\n";

		$cmd = "echo '$hostapd' | sudo tee '/etc/hostapd/hostapd.conf'";
		$do = `$cmd`;

		$cmd = "echo 'auto $wificard2\nallow-hotplug $wificard2\niface $wificard2 inet static\naddress $dhcpIP\nnetmask $dhcpnm' | sudo tee '/etc/network/interfaces.d/$wificard2'";
		$do = `$cmd`;

		if(isset($_POST['enableNAT']))
		{
			$cmd = "echo 'post-up iptables -t nat -A POSTROUTING -s ".substr($dhcpIP, 0, -1)."0/24 ! -d ".substr($dhcpIP, 0, -1)."0/24 -j MASQUERADE' | sudo tee -a '/etc/network/interfaces.d/$wificard2'";
			$do = `$cmd`;
		}

		if(isset($_POST['enableTOR']))
		{
			$cmd = "echo 'post-up /var/www/html/scripts/TOR.php up $wificard2 $dhcpIP' | sudo tee -a '/etc/network/interfaces.d/$wificard2'";
			$do = `$cmd`;

			$cmd = "echo 'no-resolv\nserver=127.0.0.1#9053' | sudo tee '/etc/dnsmasq.d/dns.conf'";
			$do = `$cmd`;

			$do = `sudo touch '/etc/tor/tor.active'`;
		} else {
			$do = `sudo rm -f '/etc/tor/tor.active'`;

			$wan = trim(`grep 'dns-nameservers' '/etc/network/interfaces.d/eth0'|awk -F \  '{print $2}'`);
			if($wan == "")
			{
				$cmd = "sudo rm -f '/etc/dnsmasq.d/dns.conf'";
				$do = `$cmd`;
			} else {
				$cmd = "echo 'no-resolv\nserver=$wan' | sudo tee '/etc/dnsmasq.d/dns.conf'";
				$do = `$cmd`;
			}
		}

		$cmd = "echo 'post-up /usr/sbin/hostapd -e /dev/urandom -B -P '/var/run/${wificard2}.pid' -f /var/log/hostapd.log /etc/hostapd/hostapd.conf' | sudo tee -a '/etc/network/interfaces.d/$wificard2'";
		$do = `$cmd`;

		if(isset($_POST['enableNAT']))
		{
			$cmd = "echo 'pre-down iptables -t nat -D POSTROUTING -s ".substr($dhcpIP, 0, -1)."0/24 ! -d ".substr($dhcpIP, 0, -1)."0/24 -j MASQUERADE' | sudo tee -a '/etc/network/interfaces.d/$wificard2'";
			$do = `$cmd`;
		}

		if(isset($_POST['enableTOR']))
		{
			$cmd = "echo 'pre-down /var/www/html/scripts/TOR.php down $wificard2 $dhcpIP' | sudo tee -a '/etc/network/interfaces.d/$wificard2'";
			$do = `$cmd`;
		}

		$cmd = "echo 'pre-down killall hostapd' | sudo tee -a '/etc/network/interfaces.d/$wificard2'";
		$do = `$cmd`;

		$cmd = "echo 'interface=$wificard2\nno-dhcp-interface=lo\ndhcp-range=$dhcpstart,$dhcpstop,$dhcpnm,$dhcptime' | sudo tee '/etc/dnsmasq.conf'";
		$do = `$cmd`;
		$cmd = "echo 'log-queries=extra\nlog-facility=/var/log/dnsmasq.log\ndomain-needed\nbogus-priv' | sudo tee '/etc/dnsmasq.d/logging.conf'";
		$do = `$cmd`;
		$do = `sudo /etc/init.d/dnsmasq restart`;

		$cmd = "sudo sed -i -e 's/^#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/' /etc/sysctl.conf";
		$do = `$cmd`;
		$cmd = "sudo sysctl -w net.ipv4.ip_forward=1";
		$do = `$cmd`;

		if(isset($_POST['enableTOR']))
		{
			$do = `echo 'Log notice file /var/log/tor/notices.log' | sudo tee '/etc/tor/torrc'`;
			$do = `echo 'VirtualAddrNetworkIPv4 10.192.0.0/10' | sudo tee -a '/etc/tor/torrc'`;
			$do = `echo 'AutomapHostsOnResolve 1' | sudo tee -a '/etc/tor/torrc'`;
			$do = `echo 'TransPort ${dhcpIP}:9040' | sudo tee -a '/etc/tor/torrc'`;
			$do = `echo 'TransPort 127.0.0.1:9040' | sudo tee -a '/etc/tor/torrc'`;
			$do = `echo 'DNSPort ${dhcpIP}:9053' | sudo tee -a '/etc/tor/torrc'`;
			$do = `echo 'DNSPort 127.0.0.1:9053' | sudo tee -a '/etc/tor/torrc'`;
			$do = `echo 'AutomapHostsSuffixes .onion,.exit' | sudo tee -a '/etc/tor/torrc'`;

			$do = `sudo /etc/init.d/tor restart`;
		}

		$do = `sudo killall -KILL wpa_supplicant`;
		$do = `sudo ifconfig $wificard up`;
		$do = `sudo ifup $wificard`;

		$okmsg = "Configuration changes have been saved and all interfaces have been successfully restarted.";
	}

	if(isset($_POST['disable']))
	{
		$do = `sudo killall -KILL wpa_supplicant`;
		$do = `sudo ifdown --force $wificard`;
		$do = `sudo ifconfig $wificard 0.0.0.0 down`;
		$do = `sudo rm -f "/etc/network/interfaces.d/$wificard2"`;
		$do = `sudo rm -f "/etc/hostapd/hostapd.conf"`;
		$do = `sudo rm -f "/etc/dnsmasq.conf"`;

		$okmsg = "$wificard2 configuration was removed and the interface was brought down.";
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

		$dhcpIP = trim(`grep 'address ' '/etc/network/interfaces.d/$wificard2'`);
		list($crud, $dhcpIP) = explode('address ', $dhcpIP, 2);
	} else {
		$channel = "40";
		$ssid = "Pine64.org";
		$passphrase = "password";
	}

        if(file_exists("/etc/dnsmasq.conf"))
        {
                $fp = fopen("/etc/dnsmasq.conf", "r");
                while(!feof($fp))
                {
                        $line = trim(fgets($fp, 1024));
                        if($line === false)
                                break;

                        if(strpos($line, "dhcp-range=") === 0)
                        {
                                list($crud, $dhcp) = explode("=", $line, 2);
                                list($dhcpstart, $dhcpstop, $dhcpnm, $dhcptime) = explode(",", $dhcp);
                        }
                }
                fclose($fp);
        }

	$enableTOR = "0";
        if(file_exists("/etc/tor/tor.active"))
		$enableTOR = "1";

	if($dhcpIP == "")
	{
		$dhcpIP = "192.168.99.1";
		$dhcpstart = "192.168.99.100";
		$dhcpstop = "192.168.99.199";
		$dhcpnm = "255.255.255.0";
		$dhcptime = "1d";
	}

	if(!isset($enableAC))
		$enableAC = trim(`grep "ieee80211ac=1" "/etc/hostapd/hostapd.conf" | wc -l`);

	$tab = 1;
	if(isset($_POST["clearlog"]) && file_exists("/var/log/hostapd.log"))
	{
		$do = `echo -n | sudo tee "/var/log/hostapd.log"`;
		$tab = 2;
	}

	$enableNAT = 0;
	if(file_exists("/etc/network/interfaces.d/$wificard2"))
	{
		$cmd = "grep MASQUERADE '/etc/network/interfaces.d/$wificard2' | wc -l";
		$enableNAT = intval(trim(`$cmd`));
	} else {
		$enableNAT = 2;
	}

	//$wificard2

                exec("ifconfig $wificard", $return);
                exec("iwconfig $wificard", $return);
                $strWlan0 = implode(" ", $return);
                $strWlan0 = preg_replace('/\s\s+/', ' ', $strWlan0);

                preg_match('/ether ([0-9a-f:]+)/i', $strWlan0, $result) || $result[1] = 'No MAC Address Found';
                $wifistats['strHWAddress'] = $result[1];

                preg_match_all('/inet ([0-9.]+) netmask ([0-9a-f.]+)/i', $strWlan0, $result);
                $wifistats['strIPAddress'] = "";
                if(is_array($result[1]))
                foreach($result[1] as $ip)
                        $wifistats['strIPAddress'] .= $ip." ";

                $wifistats['strNetMask'] = "";
                if(is_array($result[2]))
                foreach($result[2] as $nm)
                        $wifistats['strNetMask'] .= $nm." ";

                $result = array();
                preg_match('/RX packets (\d+) bytes (\d+)/i', $strWlan0, $result);
                $wifistats['strRxPackets'] = $result[1];
                $wifistats['strRxBytes'] = $result[2];

                $result = array();
                preg_match('/TX packets (\d+) bytes (\d+)/', $strWlan0, $result);
                $wifistats['strTxPackets'] = $result[1];
                $wifistats['strTxBytes'] = $result[2];

                preg_match('/ESSID:\"([a-zA-Z0-9\s].+)\" Nickname/i', $strWlan0, $result) || $result[1] = 'Not connected';
                $wifistats['strSSID'] = str_replace( '"','',$result[1] );

                preg_match('/Access Point: ([0-9a-f:]+)/i', $strWlan0, $result) || $result[1] = '';
                $wifistats['strBSSID'] = $result[1];

                preg_match('/Bit Rate:([0-9\.]+ Mb\/s)/i', $strWlan0, $result) || $result[1] = '';
                $wifistats['strBitrate'] = $result[1];

                preg_match('/Link Quality=([0-9]+)/i', $strWlan0, $result) || $result[1] = '';
                $wifistats['strLinkQuality'] = $result[1];

                preg_match('/Signal level=(-?[0-9]+ dBm)/i', $strWlan0, $result) || $result[1] = '';
                $wifistats['strSignalLevel'] = $result[1];

                preg_match('/Frequency:(\d+.\d+ GHz)/i', $strWlan0, $result) || $result[1] = '';
                $wifistats['strFrequency'] = $result[1];


	$page = 5;
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
		<div class="row" style="padding-right:15px;">
<?php if($errmsg != "") { ?>
                    <p><div class="alert alert-warning alert-dismissable"><?=$errmsg?><button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button></div></p>
<?php } ?>
<?php if($okmsg != "") { ?>
                    <p><div class="alert alert-success alert-dismissable"><?=$okmsg?><button type="button" class="close" data-dismiss="alert" aria-hidden="true">x</button></div></p>
<?php } ?>
                    <div class="col-lg-4 col-md-4">
                        <ul class="nav nav-tabs">
                            <li class="<?php if($tab == 1) { echo "active"; } ?>"><a href="#home" data-toggle="tab">Home</a>
                            </li>
                            <li class="<?php if($tab == 2) { echo "active"; } ?>"><a href="#logging" data-toggle="tab">Logging</a>
                            </li>
                            <li class=""><a href="#clients" data-toggle="tab">Clients</a>
                            </li>
                            <li class=""><a href="#wifi" data-toggle="tab">Wifi Info</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade<?php if($tab == 1) { echo " active in"; } ?>" id="home">
				<h4>Home</h4>
				<form method="post" action="<?=$_SERVER["PHP_SELF"]?>">
	                        <div style="width:140px;float:left">Interface:</div>
                                <select name="int" class="form-control" style="width:200px;float:left">
<?php for($i = 1; $i <= count($wifiArr); $i++) { ?>
                                        <option value="<?=$wifiArr[$i]['int']?>"<?php if($wifiArr[$i]['int'] == $wificard2) { ?> selected<?php } ?>><?=$wifiArr[$i]['int']?></option>
<?php } ?>
                                </select><br style="clear:left;"/>
				<div style="width:140px;float:left">SSID:</div>
				<input type="text" style="width:200px;float:left;" class="form-control" name="ssid" value="<?=$ssid?>" placeholder="Enter SSID" /><br style="clear:left;"/>
				<div style="width:140px;float:left">WiFi Channel:</div>
				<select class="form-control" name="channel" style="width:200px;float:left;">
<?php
	foreach($channels as $chan => $freq)
	{
?>
				    <option value="<?=$chan?>"<?php if($channel == $chan) { echo " selected"; } ?>>Channel <?=$chan?> -- <?=$freq?></options>
<?php } ?>
				</select><br style="clear:left;"/>

				<div style="width:140px;float:left">Pass Phrase:</div>
				<input type="text" style="width:200px;float:left;" class="form-control" name="passphrase" value="<?=$passphrase?>" placeholder="Enter Passphrase" /><br style="clear:left;"/>
				<div style="width:140px;float:left">Enable 802.11AC:</div>
				<input type="checkbox" style="width:25px;float:left;" class="form-control" name="enableAC"<?php if($enableAC == 1) { echo " checked"; } ?>/>&nbsp;&nbsp;Only for channels 40 and up.<br style="clear:left;"/>
				<div style="width:140px;float:left">Enable NAT:</div>
				<input type="checkbox" style="width:25px;float:left;" class="form-control" name="enableNAT"<?php if($enableNAT == 2) { echo " checked"; } ?>/><br style="clear:left;"/>
				<div style="width:140px;float:left">Enable TOR:</div>
				<input type="checkbox" style="width:25px;float:left;" class="form-control" name="enableTOR"<?php if($enableTOR == 1) { echo " checked"; } ?>/><br style="clear:left;"/>
				<div style="width:140px;float:left">Server IP:</div>
                                <input type="text" style="width:200px;float:left;" class="form-control" name="dhcpIP" value="<?=$dhcpIP?>" placeholder="Enter DHCP Server IP" /><br style="clear:left;"/>
				<div style="width:140px;float:left">DHCP Start IP:</div>
                                <input type="text" style="width:200px;float:left;" class="form-control" name="dhcpstart" value="<?=$dhcpstart?>" placeholder="Enter DHCP Start IP" /><br style="clear:left;"/>
                                <div style="width:140px;float:left">DHCP End IP:</div>
                                <input type="text" style="width:200px;float:left;" class="form-control" name="dhcpstop" value="<?=$dhcpstop?>" placeholder="Enter DHCP Ending IP" /><br style="clear:left;"/>
                                <div style="width:140px;float:left">Netmask:</div>
                                <input type="text" style="width:200px;float:left;" class="form-control" name="dhcpnm" value="<?=$dhcpnm?>" placeholder="Enter DHCP Netmask" /><br style="clear:left;"/>
                                <div style="width:140px;float:left">Lease Time:</div>
                                <select name="dhcptime" class="form-control" style="width:200px;float:left;">
                                        <option value="1h"<?php if($dhcptime == "1h") { echo " selected"; } ?>>1 Hour</option>
                                        <option value="3h"<?php if($dhcptime == "3h") { echo " selected"; } ?>>3 Hours</option>
                                        <option value="5h"<?php if($dhcptime == "5h") { echo " selected"; } ?>>5 Hours</option>
                                        <option value="9h"<?php if($dhcptime == "9h") { echo " selected"; } ?>>9 Hours</option>
                                        <option value="12h"<?php if($dhcptime == "12h") { echo " selected"; } ?>>12 Hours</option>
                                        <option value="18h"<?php if($dhcptime == "18h") { echo " selected"; } ?>>18 Hours</option>
                                        <option value="1d"<?php if($dhcptime == "1d") { echo " selected"; } ?>>1 Day</option>
                                        <option value="2d"<?php if($dhcptime == "2d") { echo " selected"; } ?>>2 Days</option>
                                        <option value="5d"<?php if($dhcptime == "5d") { echo " selected"; } ?>>5 Days</option>
                                        <option value="7d"<?php if($dhcptime == "7d") { echo " selected"; } ?>>7 Days</option>
                                </select><br style="clear:left;"/>

				<input type="submit" class="btn btn-primary" name="button" value="Update and Re-start" />
				<input type="submit" class="btn btn-primary" name="disable" value="Disable" />
				</form>
                            </div>
                            <div class="tab-pane fade<?php if($tab == 2) { echo " active in"; } ?>" id="logging">
                                <h4>Logging</h4>
                                <p>
				    <div style="width:150px;float:left">Auto refresh every 5s</div><input type="checkbox" style="width:25px;float:left;margin-left:10px;" class="form-control" checked id="autoRefresh1"><br style="clear:left;"/>
				    <textarea cols="90" rows="15" wrap="off" readonly="readonly" id="textarea"></textarea>
				    <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
					<input type="submit" class="btn btn-primary" name="clearlog" value="Clear log" />
				    </form>
                                </p>
                            </div>
                            <div class="tab-pane fade" id="clients">
                                <h4>Clients</h4>
<?php
	if(file_exists("/var/lib/misc/dnsmasq.leases"))
	{
                $clients = explode("\n", trim(`sudo cat "/var/lib/misc/dnsmasq.leases"`));
		if(isset($clients['0']) && $clients['0'] == "")
			unset($clients['0']);
                foreach($clients as $client)
                {
                        list($expire, $mac, $ip, $hostname, $cliid) = explode(" ", $client);
?>
			    	<div class="row" style="padding-left:10px;">
	                    	    <div class="col-md-6" style="width:350px;">
				    	<div class="panel panel-primary">
                            		    <div class="panel-heading">MAC Address: <?=$mac?></div>
                            		    <div class="panel-body">
					        <div style="width:100px;float:left;">IP:</div><?=$ip?><br/>
					        <div style="width:100px;float:left;">Expiry:</div><?=date('Y-m-d H:i:s', $expire)?><br/>
					        <div style="width:100px;float:left;">Hostname:</div><?=$hostname?><br/>
					        <div style="width:100px;float:left;">Cli:</div><?=$cliid?><br/>
                            		    </div>
                        	        </div>
				    </div>
			        </div>
<?php } } ?>
			    </div>
	                    <div class="tab-pane fade" id="wifi">
				<h4>WiFi Information</h4>
				    <div class="panel panel-primary" style="width:325px;float:left;margin-right:20px;">
                            		<div class="panel-heading">Wireless Information</div>
                            		<div class="panel-body">
                                	    <div style="width:160px;float:left;">Connected To:</div> <?=$wifistats['strSSID']?><br/>
	                                    <div style="width:160px;float:left;">AP MAC Address:</div> <?=$wifistats['strBSSID']?><br/>
            		                    <div style="width:160px;float:left;">Bitrate:</div> <?=$wifistats['strBitrate']?><br/>
                        		    <div style="width:160px;float:left;">Signal Level:</div> <?=$wifistats['strSignalLevel']?><br/>
	                                    <div style="width:160px;float:left;">Frequency:</div> <?=$wifistats['strFrequency']?><br/>
            		                    <div style="width:160px;float:left;">Link Quality:</div>
                        	            <div class="progress progress-striped" title="Signal: <?=$wifistats['strLinkQuality']?>%">
                                	        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?=$wifistats['strLinkQuality']?>" aria-valuemin="0" aria-valuemax="100" style="width: <?=$wifistats['strLinkQuality']?>%">
                                        	<span class="sr-only"><?=$wifistats['strLinkQuality']?>% Complete (success)</span>
		                            </div>
                                        </div>
                                    </div>
                                <br/>
                            </div>
                        </div>
		    </div>
		</div>
	    </div>
        </div>
<script type="text/javascript" language="JavaScript">
<!--//
        var http1 = getHTTPObject();

        function getHTTPObject()
        {
                var request = null;
                if(typeof XMLHttpRequest != 'undefined')
                {
                        request = new XMLHttpRequest();
                } else {
                        try
                        {
                                request = new ActiveXObject('Msxml2.XMLHTTP')
                        } catch(e) {
                                try
                                {
                                        request = new ActiveXObject('Microsoft.XMLHTTP')
                                } catch(e) {
                                        request = null
                                }
                        }
                }

                return request;
        }

        function updateDisplay(name, val)
        {
                var element = document.getElementById(name);
                if(!element)
                        return;

                element.innerHTML = val;
                if(name == "textarea")
                        document.getElementById('textarea').scrollTop = 9999999;
        }

        function updateLog()
        {
                setTimeout("updateLog();", 5000);

                if(document.getElementById("autoRefresh1").checked)
                {
                        try
                        {
                                http1.open('GET', '/jsapi.php?HostAPd=1&date='+new Date().getTime(), true);
                                http1.onreadystatechange = function()
                                {
                                        if(http1.readyState == 4 && http1.status == 200)
                                                updateDisplay('textarea', http1.responseText);
                                }

                                http1.send();
                        } catch (e) {}
                }
        }

        updateLog();
//-->
</script>
<?php include_once("footer.php"); ?>
