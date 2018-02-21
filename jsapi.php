<?php
	if(isset($_REQUEST['dnsmasqLog']))
		dnsmasqLog();

	function dnsmasqLog()
	{
		$lines = explode("\n", trim(`sudo grep ": query" /var/log/dnsmasq.log`));
		foreach($lines as $row => $line)
		{
			$line = trim($line);
			list($datetime, $rest) = explode(" dnsmasq[", $line, 2);
			list($crud, $rest) = explode("]: ", $rest, 2);
			$lines[$row] = $datetime.": ".$rest;
		}

		echo trim(implode("\n", $lines));
	}
