<?php
        session_start();

        if($_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	if(isset($_POST['button']))
	{
		//Array ( [int] => wlx08107a65e3f0 [dhcpstart] => 192.168.99.100 [dhcpstop] => 192.168.99.199 [dhcptime] => 1d [button] => Update and Re-start )
		$wificard = substr(escapeshellarg(trim($_POST['int'])), 1, -1);
		$dhcpstart = substr(escapeshellarg(trim($_POST['dhcpstart'])), 1, -1);
		$dhcpstop = substr(escapeshellarg(trim($_POST['dhcpstop'])), 1, -1);
		$dhcptime = substr(escapeshellarg(trim($_POST['dhcptime'])), 1, -1);

		$cmd = "echo 'interface=$wificard\nno-dhcp-interface=lo\ndhcp-range=$dhcpstart,$dhcpstop,255.255.255.0,$dhcptime' | sudo tee '/etc/dnsmasq.conf'";
		$do = `$cmd`;
		$do = `sudo /etc/init.d/dnsmasq restart`;
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

	if(file_exists("/etc/dnsmasq.conf"))
	{
		$fp = fopen("/etc/dnsmasq.conf", "r");
		while(!feof($fp))
		{
//interface=wlx08107a65e3f0
//no-dhcp-interface=lo
//dhcp-range=192.168.99.100,192.168.99.199,255.255.255.0,12h

			$line = trim(fgets($fp, 1024));
			if($line === false)
				break;

			if(strpos($line, "interface=") === 0)
			{
				list($crud, $wificard) = explode("=", $line, 2);
				$wificard = escapeshellarg(trim($wificard));
				$wificard2 = substr($wificard, 1, -1);
			}

			if(strpos($line, "dhcp-range=") === 0)
			{
				list($crud, $dhcp) = explode("=", $line, 2);
				list($dhcpstart, $dhcpstop, $dhcpnm, $dhcptime) = explode(",", $dhcp);
			}
		}
		fclose($fp);
	}

	$page = 6;
	$pageTitle = "DHCP Settings";
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
                        <ul class="nav nav-tabs" style="width:1000px;">
                            <li class="active"><a href="#home" data-toggle="tab">Home</a></li>
                            <li class=""><a href="#clients" data-toggle="tab">Clients</a></li>
                        </ul>
                        <div class="tab-content">
			    <br/>
                            <div class="tab-pane fade active in" id="home">
				<form method="post" action="<?=$_SERVER['PHP_SELF']?>">
	                        <div style="width:160px;float:left">Interface:</div>
                                <select name="int" class="form-control" style="width:300px;float:left;margin-left:20px;">
<?php for($i = 1; $i <= count($wifiArr); $i++) { ?>
                                        <option value="<?=$wifiArr[$i]['int']?>"<?php if($wifiArr[$i]['int'] == $wificard2) { ?> selected<?php } ?>><?=$wifiArr[$i]['int']?></option>
<?php } ?>
                                </select><br/>
				<div style="width:160px;float:left">Start IP:</div>
				<input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="dhcpstart" value="<?=$dhcpstart?>" placeholder="Enter DHCP Start IP" /><br/>
				<div style="width:160px;float:left">End IP:</div>
				<input type="text" style="width:300px;float:left;margin-left:20px;" class="form-control" name="dhcpstop" value="<?=$dhcpstop?>" placeholder="Enter DHCP Ending IP" /><br/>
				<div style="width:160px;float:left">Lease Time:</div>
				<select name="dhcptime" class="form-control" style="width:300px;float:left;margin-left:20px;">
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
				</select><br/>
				<input type="submit" class="btn btn-primary" name="button" value="Update and Re-start" />
				</form>
                            </div>
                            <div class="tab-pane fade" id="clients">
        <div class="table-responsive" style="width:1000px;">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Expire time</th>
                <th>MAC Address</th>
                <th>IP Address</th>
                <th>Host name</th>
                <th>Client ID</th>
              </tr>
            </thead>
<?php
	if(file_exists("/var/lib/misc/dnsmasq.leases"))
	{
		echo "\t<tbody>\n";
// 1519117391 ac:cf:85:63:a1:19 192.168.99.157 android-1101aeccbf975a1c 01:ac:cf:85:63:a1:19
		$clients = explode("\n", trim(file_get_contents("/var/lib/misc/dnsmasq.leases")));
		foreach($clients as $client)
		{
			list($expire, $mac, $ip, $hostname, $cliid) = explode(" ", $client);
?>
              <tr><td><?=date('Y-m-d H:i:s', $expire)?></td><td><?=$mac?></td><td><?=$ip?></td><td><?=$hostname?></td><td><?=$cliid?></td></tr>
<?php
		}

		echo "\t</tbody>\n";
	}
?>
          </table>
        </div>
                            </div>
                        </div>
                    </div>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
