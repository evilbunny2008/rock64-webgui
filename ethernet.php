<?php
        session_start();

	if(!isset($_SESSION['login']) || $_SESSION['login'] != true)
        {
                header("location: login.php");
                exit;
        }

	$lines = explode("\n", trim(`nmcli dev stat|grep ethernet|sort -n`));
	$ethArr = array();
	$i = 0;
	$enable = "yes";
	$IP = $gw = $nm = $dns = "";

	foreach($lines as $line)
	{
		$line = trim($line);
		list($eth, $crud) = explode(" ", str_replace("  ", " ", $line), 2);

		$i++;
		$ethArr[$i]['int'] = $eth;
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
                $enable = substr(escapeshellarg(trim($_POST['enable'])), 1, -1);
                $IP = substr(escapeshellarg(trim($_POST['IP'])), 1, -1);
                $gw = substr(escapeshellarg(trim($_POST['gw'])), 1, -1);
                $dns = substr(escapeshellarg(trim($_POST['dns'])), 1, -1);
                $nm = substr(escapeshellarg(trim($_POST['nm'])), 1, -1);
                $dhcp = substr(escapeshellarg(trim($_POST['dhcp'])), 1, -1);

		if(file_exists("/etc/network/interfaces.d/$ethernet2"))
		{
			$do = `sudo ifdown --force "$ethernet2"`;
			$do = `sudo ifconfig "$ethernet2" 0.0.0.0 down`;
		}

		if($enable == "yes")
		{
			if($dhcp != "no")
			{
				$cmd = "echo 'auto $ethernet2\nallow-hotplug $ethernet2\niface $ethernet2 inet dhcp' | sudo tee '/etc/network/interfaces.d/$ethernet2'";
				$do = `$cmd`;
				if($IP != "")
				{
					$cmd = "echo 'address $IP' | sudo tee -a '/etc/network/interfaces.d/$ethernet2'";
					$do = `$cmd`;
				}

				if($nm != "")
				{
					$cmd = "echo 'netmask $nm' | sudo tee -a '/etc/network/interfaces.d/$ethernet2'";
					$do = `$cmd`;
				}

				if($gw != "")
				{
					$cmd = "echo 'gateway $gw' | sudo tee -a '/etc/network/interfaces.d/$ethernet2'";
					$do = `$cmd`;
				}

				if($dns != "")
				{
					$cmd = "echo 'dns-nameservers $dns' | sudo tee -a '/etc/network/interfaces.d/$ethernet2'";
					$do = `$cmd`;
					$cmd = "echo 'no-resolv\nserver=$dns' | sudo tee '/etc/dnsmasq.d/dns.conf'";
					$do = `$cmd`;
				} else {
					$cmd = "sudo rm -f '/etc/dnsmasq.d/dns.conf'";
					$do = `$cmd`;
				}

				$cmd = "echo 'post-up /var/www/html/scripts/WAN.sh up\npre-down /var/www/html/scripts/WAN.sh down' | sudo tee -a '/etc/network/interfaces.d/$ethernet2'";
				$do = `$cmd`;
			} else {
				$cmd = "echo 'auto $ethernet2\nallow-hotplug $ethernet2\niface $ethernet2 inet static' | sudo tee '/etc/network/interfaces.d/$ethernet2'";
				$do = `$cmd`;
				if($IP != "")
				{
					$cmd = "echo 'address $IP' | sudo tee -a '/etc/network/interfaces.d/$ethernet2'";
					$do = `$cmd`;
				}

				if($nm != "")
				{
					$cmd = "echo 'netmask $nm' | sudo tee -a '/etc/network/interfaces.d/$ethernet2'";
					$do = `$cmd`;
				}

				if($gw != "")
				{
					$cmd = "echo 'gateway $gw' | sudo tee -a '/etc/network/interfaces.d/$ethernet2'";
					$do = `$cmd`;
				}

				if($dns != "")
				{
					$cmd = "echo 'dns-nameservers $dns' | sudo tee -a '/etc/network/interfaces.d/$ethernet2'";
					$do = `$cmd`;
					$cmd = "echo 'no-resolv\nserver=$dns' | sudo tee '/etc/dnsmasq.d/dns.conf'";
					$do = `$cmd`;
				} else {
					$cmd = "sudo rm -f '/etc/dnsmasq.d/dns.conf'";
					$do = `$cmd`;
				}

				$cmd = "echo 'post-up /var/www/html/scripts/WAN.sh up\npre-down /var/www/html/scripts/WAN.sh down' | sudo tee -a '/etc/network/interfaces.d/$ethernet2'";
				$do = `$cmd`;
			}
		} else {
			$cmd = "echo 'iface $ethernet2 inet manual' | sudo tee '/etc/network/interfaces.d/$ethernet2'";
			$do = `$cmd`;
		}

		$do = `sudo ifconfig $ethernet up`;
		$do = `sudo ifup $ethernet`;

		$cmd = "sudo /etc/init.d/dnsmasq restart";
		$do = `$cmd`;
	}

	if(isset($_POST['disable']))
	{
		$do = `sudo ifdown --force $ethernet`;
		$do = `sudo ifconfig $ethernet 0.0.0.0 down`;
		$do = `sudo rm -f "/etc/network/interfaces.d/$ethernet2"`;
	}

	if(file_exists("/etc/network/interfaces.d/$ethernet2"))
	{
		$fp = fopen("/etc/network/interfaces.d/$ethernet2", "r");
		while(!feof($fp))
		{
			$line = trim(fgets($fp, 1024));
			if($line === false)
				break;

			if(strpos($line, "iface ") === 0)
			{
				$bits = explode(" ", $line, 4);
				if($bits['3'] == "dhcp")
					$dhcp = "yes";
				if($bits['3'] == "static")
					$dhcp = "no";
				if($bits['3'] == "manual")
					$enable = "no";
			}

			if(strpos($line, "address ") === 0)
				list($crud, $IP) = explode(" ", $line, 2);

			if(strpos($line, "netmask ") === 0)
				list($crud, $nm) = explode(" ", $line, 2);

			if(strpos($line, "gateway ") === 0)
				list($crud, $gw) = explode(" ", $line, 2);

			if(strpos($line, "dns-nameservers ") === 0)
				list($crud, $dns) = explode(" ", $line, 2);
		}
	} else {
		$IP = "192.168.100.2";
		$nm = "255.255.255.0";
		$gw = "192.168.100.1";
		$dns = "192.168.100.1";
		$dhcp = "yes";
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
			<div style="width:140px;float:left">Interface:</div>
			<select name="int" class="form-control" style="width:200px;float:left;margin-left:20px;" onClick="this.form.submit()">
<?php for($i = 1; $i <= count($ethArr); $i++) { ?>
				<option value="<?=$ethArr[$i]['int']?>"<?php if($ethArr[$i]['int'] == $ethernet2) { ?> selected<?php } ?>><?=$ethArr[$i]['int']?></option>
<?php } ?>
                        </select><br style="clear:left;"/>

			<div style="width:140px;float:left">Enable Interface:</div>

			<div style="width:100px;float:left;padding-left:25px;">
				<input style="width:20px;float:left" class="form-control" name="enable" value="yes" type="radio" id="ifup"<?php if($enable != "no") { echo " checked='checked'"; } ?>>
				<label style="padding-left:10px;" for="ifup">Yes</label>
			</div>

			<div style="width:100px;float:left">
				<input style="width:20px;float:left" class="form-control" name="enable" value="no" type="radio" id="ifdown"<?php if($enable == "no") { echo " checked='checked'"; } ?>>
				<label style="padding-left:10px;" for="ifdown">No</label>
			</div>

			<br style="clear:left;"/>

			<div style="width:140px;float:left">Use DHCP:</div>

			<div style="width:100px;float:left;padding-left:25px;">
				<input style="width:20px;float:left" class="form-control" name="dhcp" value="yes" type="radio" id="useDHCP"<?php if($dhcp != "no") { echo " checked='checked'"; } ?>>
				<label style="padding-left:10px;" for="useDHCP">Yes</label>
			</div>

			<div style="width:100px;float:left">
				<input style="width:20px;float:left" class="form-control" name="dhcp" value="no" type="radio" id="useStatic"<?php if($dhcp == "no") { echo " checked='checked'"; } ?>>
				<label style="padding-left:10px;" for="useStatic">No</label>
			</div>

			<br style="clear:left;"/>

			<div style="width:140px;float:left">Gateway IP:</div>
                        <input type="text" style="width:200px;float:left;margin-left:20px;" class="form-control" name="gw" value="<?=$gw?>" placeholder="Enter Gateway IP" /><br style="clear:left;"/>
			<div style="width:140px;float:left">Ethernet IP:</div>
                        <input type="text" style="width:200px;float:left;margin-left:20px;" class="form-control" name="IP" value="<?=$IP?>" placeholder="Enter Ethernet IP" /><br style="clear:left;"/>
                        <div style="width:140px;float:left">Netmask:</div>
                        <input type="text" style="width:200px;float:left;margin-left:20px;" class="form-control" name="nm" value="<?=$nm?>" placeholder="Enter Ethernet Netmask" /><br style="clear:left;"/>
			<div style="width:140px;float:left">DNS IP:</div>
                        <input type="text" style="width:200px;float:left;margin-left:20px;" class="form-control" name="dns" value="<?=$dns?>" placeholder="Enter DNS IP" /><br style="clear:left;"/>
			<br style="clear:left;"/>
			<input type="submit" class="btn btn-primary" name="button" value="Update and Reload" />
			<input type="submit" class="btn btn-primary" name="disable" value="Disable" />
			</form>
		</div>
	    </div>
        </div>
<?php include_once("footer.php"); ?>
