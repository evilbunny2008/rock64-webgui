#!/bin/bash

if [ "$(id -u)" -ne "0" ]
then
	echo "This script must be run as root."
	exit 1
fi

if [[ -e "/etc/openvpn/client/client1.active" ]] && [[ -e "/etc/openvpn/client/client1.ovpn" ]]
then
	if [[ "x$1" == "xup" ]]
	then
		RESULT=`pgrep openvpn`
		if [[ "x$RESULT" == "x" ]]
		then
echo "Here"
			/usr/sbin/openvpn --config "/etc/openvpn/client/client1.ovpn" --daemon
		fi
	else
		killall openvpn > /dev/null 2>&1
	fi
fi
