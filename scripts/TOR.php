#!/usr/bin/php -q
<?php
	if(getmyuid() != 0)
	{
		echo "You must run this script as root.\n";
		exit(1);
	}

	if($argc < 4)
	{
		echo "Not enough commandline arguments supplied.\n";
		exit(2);
	}

	$action = trim($argv['1']);
	if($action != "up" && $action != "down")
	{
		echo "Invalid action\n";
		exit(3);
	}

	$iface = trim($argv['2']);
	$interfaces = trim(`nmcli dev status|grep wifi|grep '$iface'`);

	if($interfaces == "")
	{
		echo "Invalid interface ($iface)\n";
		exit(4);
	}

	$IP = trim($argv['3']);
	$long = ip2long($IP);

	if($long == -1 || $long === FALSE)
	{
		echo "Unknown IP\n";
		exit(5);
	}

	$subnet = substr($IP, -2);

	if($action == "up")
	{
		$cmd = `iptables -t nat -A PREROUTING -i $iface -p udp -m udp --dport 123 -j REDIRECT --to-ports 123`;
		$cmd = `iptables -t nat -A PREROUTING -i $iface -p udp -m udp --dport 53 -j REDIRECT --to-ports 53`;
		$cmd = `iptables -t nat -A PREROUTING -s ${subnet}.0/24 -d ${subnet}.0/24 -j RETURN`;
		$cmd = `iptables -t nat -A PREROUTING -i $iface -p tcp -m tcp --tcp-flags FIN,SYN,RST,ACK SYN -j REDIRECT --to-ports 9040`;

		$cmd = `iptables -A INPUT -p icmp -j ACCEPT`;
		$cmd = `iptables -A INPUT -m conntrack --ctstate RELATED,ESTABLISHED -j ACCEPT`;
		$cmd = `iptables -A INPUT -i lo -j ACCEPT`;
		$cmd = `iptables -A INPUT -i $iface -p tcp -m tcp -d $IP --dport 22 -j ACCEPT`;
		$cmd = `iptables -A INPUT -i $iface -p udp -m udp -d $IP --dport 53 -j ACCEPT`;
		$cmd = `iptables -A INPUT -i $iface -p tcp -m tcp -d $IP --dport 53 -j ACCEPT`;
		$cmd = `iptables -A INPUT -i $iface -p tcp -m tcp -d $IP --dport 80 -j ACCEPT`;
		$cmd = `iptables -A INPUT -i $iface -p udp -m udp -d $IP --dport 123 -j ACCEPT`;
		$cmd = `iptables -A INPUT -i $iface -p tcp -m tcp -d $IP --dport 9050 -j ACCEPT`;
		$cmd = `iptables -A INPUT -i $iface -p tcp -m tcp -d $IP --dport 9040 -j ACCEPT`;
		$cmd = `iptables -A INPUT -i $iface -p udp -m udp -d $IP --dport 9053 -j ACCEPT`;

		$cmd = `iptables -A INPUT -i $iface -p udp -m udp -s 0.0.0.0 -d 255.255.255.255 -j ACCEPT`;

		$cmd = `iptables -A INPUT -i $iface -p tcp -j LOG --log-prefix 'IPTables-Dropped1: ' --log-level 4`;
		$cmd = `iptables -A INPUT -i $iface -p tcp -j REJECT --reject-with tcp-reset`;

		$cmd = `iptables -A INPUT -i $iface -p udp -d 224.0.0.251 --dport 5353 -j REJECT --reject-with icmp-port-unreachable`;
		$cmd = `iptables -A INPUT -i $iface -p udp -j LOG --log-prefix 'IPTables-Dropped2: ' --log-level 4`;
		$cmd = `iptables -A INPUT -i $iface -p udp -j REJECT --reject-with icmp-port-unreachable`;
		$cmd = `iptables -A INPUT -i $iface -j LOG --log-prefix 'IPTables-Dropped3: ' --log-level 4`;
		$cmd = `iptables -A INPUT -i $iface -j REJECT --reject-with icmp-proto-unreachable`;
	} else {
		$cmd = `iptables -t nat -D PREROUTING -i $iface -p udp -m udp --dport 123 -j REDIRECT --to-ports 123`;
		$cmd = `iptables -t nat -D PREROUTING -i $iface -p udp -m udp --dport 53 -j REDIRECT --to-ports 53`;
		$cmd = `iptables -t nat -D PREROUTING -s ${subnet}.0/24 -d ${subnet}.0/24 -j RETURN`;
		$cmd = `iptables -t nat -D PREROUTING -i $iface -p tcp -m tcp --tcp-flags FIN,SYN,RST,ACK SYN -j REDIRECT --to-ports 9040`;

		$cmd = `iptables -D INPUT -p icmp -j ACCEPT`;
		$cmd = `iptables -D INPUT -m conntrack --ctstate RELATED,ESTABLISHED -j ACCEPT`;
		$cmd = `iptables -D INPUT -i lo -j ACCEPT`;
		$cmd = `iptables -D INPUT -i $iface -p tcp -m tcp -d $IP --dport 22 -j ACCEPT`;
		$cmd = `iptables -D INPUT -i $iface -p udp -m udp -d $IP --dport 53 -j ACCEPT`;
		$cmd = `iptables -D INPUT -i $iface -p tcp -m tcp -d $IP --dport 53 -j ACCEPT`;
		$cmd = `iptables -D INPUT -i $iface -p tcp -m tcp -d $IP --dport 80 -j ACCEPT`;
		$cmd = `iptables -D INPUT -i $iface -p udp -m udp -d $IP --dport 123 -j ACCEPT`;
		$cmd = `iptables -D INPUT -i $iface -p tcp -m tcp -d $IP --dport 9050 -j ACCEPT`;
		$cmd = `iptables -D INPUT -i $iface -p tcp -m tcp -d $IP --dport 9040 -j ACCEPT`;
		$cmd = `iptables -D INPUT -i $iface -p udp -m udp -d $IP --dport 9053 -j ACCEPT`;

		$cmd = `iptables -D INPUT -i $iface -p udp -m udp -s 0.0.0.0 -d 255.255.255.255 -j ACCEPT`;

		$cmd = `iptables -D INPUT -i $iface -p tcp -j LOG --log-prefix 'IPTables-Dropped1: ' --log-level 4`;
		$cmd = `iptables -D INPUT -i $iface -p tcp -j REJECT --reject-with tcp-reset`;

		$cmd = `iptables -D INPUT -i $iface -p udp -d 224.0.0.251 --dport 5353 -j REJECT --reject-with icmp-port-unreachable`;
		$cmd = `iptables -D INPUT -i $iface -p udp -j LOG --log-prefix 'IPTables-Dropped2: ' --log-level 4`;
		$cmd = `iptables -D INPUT -i $iface -p udp -j REJECT --reject-with icmp-port-unreachable`;
		$cmd = `iptables -D INPUT -i $iface -j LOG --log-prefix 'IPTables-Dropped3: ' --log-level 4`;
		$cmd = `iptables -D INPUT -i $iface -j REJECT --reject-with icmp-proto-unreachable`;
	}
