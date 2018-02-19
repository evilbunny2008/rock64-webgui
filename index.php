<?php
        session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$ints = trim(`nmcli dev stat|egrep "wifi|ethernet"|grep -v p2p0|sort -n|awk -F \  '{print $1}'`);
	$ints = explode("\n", $ints);

	$page = 1;
	$pageTitle = "Network Dashboard";
	include_once("header.php");
?>
        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row" style="padding-right:15px;padding-right:20px;">
                    <div class="col-md-12">
                        <h2><?=$pageTitle?></h2>
                    </div>
                </div>
                <hr />
		<div class="row" style="padding-right:15px;padding-right:20px;">
<?php
	if(is_array($ints))
	foreach($ints as $int)
	{

		$return = $strWlan0 = "";
		exec("ifconfig '$int'", $return);
		$strWlan0 = implode(" ", $return);
		$strWlan0 = preg_replace('/\s\s+/', ' ', $strWlan0);

		preg_match('/ether ([0-9a-f:]+)/i', $strWlan0, $result) || $result[1] = 'No MAC Address Found';
		$strHWAddress = $result[1];

		preg_match_all('/inet ([0-9.]+) netmask ([0-9a-f.]+)/i', $strWlan0, $result);
	        $strIPAddress = '';
		if(is_array($result[1]))
		foreach($result[1] as $ip)
			$strIPAddress .= $ip." ";

		$strNetMask = "";
	        if(is_array($result[2]))
        	foreach($result[2] as $nm)
			$strNetMask .= $nm." ";

                $result = array();
                preg_match('/RX packets (\d+) bytes (\d+)/i', $strWlan0, $result);
                $strRxPackets = $result[1];
                $strRxBytes = $result[2];

                $result = array();
                preg_match('/TX packets (\d+) bytes (\d+)/', $strWlan0, $result);
                $strTxPackets = $result[1];
                $strTxBytes = $result[2];
?>
		    <div class="col-md-6" style="width:350px;">
                        <div class="panel panel-primary">
			    <div class="panel-heading"><?=$int?></div>
			    <div class="panel-body">
				<div style="width:160px;float:left;">IP Address:</div><?=$strIPAddress?><br/>
				<div style="width:160px;float:left;">Subnet Mask:</div><?=$strNetMask?><br/>
                                <div style="width:160px;float:left;">MAC Address:</div><?=$strHWAddress?><br/>
				<hr/>
                                <div style="width:160px;float:left;">Received Packets:</div> <?=$strRxPackets?><br/>
                                <div style="width:160px;float:left;">Received Bytes:</div> <?=$strRxBytes?></br>
                                <div style="width:160px;float:left;">Sent Packets:</div> <?=$strTxPackets?><br/>
                                <div style="width:160px;float:left;">Sent Bytes:</div>  <?=$strTxBytes?></br>
			    </div>
                        </div>
		    </div>
<?php } ?>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
