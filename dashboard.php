<?php
        session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$lines = explode("\n", trim(`nmcli dev stat|grep wifi|sort -n`));

	$wifiArr = array();
	$i = 0;
	foreach($lines as $line)
	{
		$return = array();
		$line = trim($line);
		list($wifi, $crud) = explode(" ", str_replace("  ", " ", $line), 2);
		if($wifi == "p2p0")
			continue;

		$i++;
		$wifiArr[$i]['int'] = $wifi;
		exec("ifconfig $wifi", $return);
		exec("iwconfig $wifi", $return);
		$strWlan0 = implode(" ", $return);
		$strWlan0 = preg_replace('/\s\s+/', ' ', $strWlan0);

		preg_match('/ether ([0-9a-f:]+)/i', $strWlan0, $result) || $result[1] = 'No MAC Address Found';
		$wifiArr[$i]['strHWAddress'] = $result[1];

                preg_match_all('/inet ([0-9.]+) netmask ([0-9a-f.]+)/i', $strWlan0, $result);
		$wifiArr[$i]['strIPAddress'] = "";
                if(is_array($result[1]))
                foreach($result[1] as $ip)
                        $wifiArr[$i]['strIPAddress'] .= $ip." ";

                $wifiArr[$i]['strNetMask'] = "";
                if(is_array($result[2]))
                foreach($result[2] as $nm)
                        $wifiArr[$i]['strNetMask'] .= $nm." ";

		$result = array();
		preg_match('/RX packets (\d+) bytes (\d+)/i', $strWlan0, $result);
		$wifiArr[$i]['strRxPackets'] = $result[1];
		$wifiArr[$i]['strRxBytes'] = $result[2];

		$result = array();
		preg_match('/TX packets (\d+) bytes (\d+)/', $strWlan0, $result);
		$wifiArr[$i]['strTxPackets'] = $result[1];
		$wifiArr[$i]['strTxBytes'] = $result[2];

		preg_match('/ESSID:\"([a-zA-Z0-9\s].+)\" Nickname/i', $strWlan0, $result) || $result[1] = 'Not connected';
		$wifiArr[$i]['strSSID'] = str_replace( '"','',$result[1] );

		preg_match('/Access Point: ([0-9a-f:]+)/i', $strWlan0, $result) || $result[1] = '';
		$wifiArr[$i]['strBSSID'] = $result[1];

		preg_match('/Bit Rate:([0-9\.]+ Mb\/s)/i', $strWlan0, $result) || $result[1] = '';
		$wifiArr[$i]['strBitrate'] = $result[1];

		preg_match('/Link Quality=([0-9]+)/i', $strWlan0, $result) || $result[1] = '';
		$wifiArr[$i]['strLinkQuality'] = $result[1];

		preg_match('/Signal level=(-?[0-9]+ dBm)/i', $strWlan0, $result) || $result[1] = '';
		$wifiArr[$i]['strSignalLevel'] = $result[1];

		preg_match('/Frequency:(\d+.\d+ GHz)/i', $strWlan0, $result) || $result[1] = '';
		$wifiArr[$i]['strFrequency'] = $result[1];
	}

	$page = 2;
	$pageTitle = "WiFi Dashboard";
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
<?php
	foreach($wifiArr as $wifi)
	{
?>
		<div class="row" style="padding-right:15px;padding-left:15px;">
			<div class="panel panel-primary" style="width:325px;float:left;margin-right:20px;">
                            <div class="panel-heading">
                                Interface: <?=$wifi['int']?>
                            </div>
                            <div class="panel-body">
                                <div style="width:160px;float:left;">IP Address:</div> <?=$wifi['strIPAddress']?><br/>
                                <div style="width:160px;float:left;">Subnet Mask:</div> <?=$wifi['strNetMask']?><br/>
                                <div style="width:160px;float:left;">MAC Address:</div> <?=$wifi['strHWAddress']?><br/>
				<hr/>
                                <div style="width:160px;float:left;">Received Packets:</div> <?=$wifi['strRxPackets']?><br/>
                                <div style="width:160px;float:left;">Received Bytes:</div> <?=$wifi['strRxBytes']?></br>
                                <div style="width:160px;float:left;">Sent Packets:</div> <?=$wifi['strTxPackets']?><br/>
                                <div style="width:160px;float:left;">Sent Bytes:</div>	<?=$wifi['strTxBytes']?></br>
                            </div>
                        </div>
			<div class="panel panel-primary" style="width:325px;float:left;margin-right:20px;">
			    <div class="panel-heading">
				Wireless Information
			    </div>
			    <div class="panel-body">
				<div style="width:160px;float:left;">Connected To:</div> <?=$wifi['strSSID']?><br/>
				<div style="width:160px;float:left;">AP MAC Address:</div> <?=$wifi['strBSSID']?><br/>
				<div style="width:160px;float:left;">Bitrate:</div> <?=$wifi['strBitrate']?><br/>
				<div style="width:160px;float:left;">Signal Level:</div> <?=$wifi['strSignalLevel']?><br/>
				<div style="width:160px;float:left;">Frequency:</div> <?=$wifi['strFrequency']?><br/>
				<div style="width:160px;float:left;">Link Quality:</div>
				    <div class="progress progress-striped" title="Signal: <?=$wifi['strLinkQuality']?>%">
					<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="<?=$wifi['strLinkQuality']?>" aria-valuemin="0" aria-valuemax="100" style="width: <?=$wifi['strLinkQuality']?>%">
					    <span class="sr-only"><?=$wifi['strLinkQuality']?>% Complete (success)</span>
					</div>
				    </div>
				<br/>
			    </div>
			</div>
		</div>
<?php
	}
?>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
