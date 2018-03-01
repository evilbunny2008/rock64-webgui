## rock64-webgui -- Web GUI for the Rock64

This is community maintained project in my free time. Don't expect everything to be perfect and working. Rather be prepared that there are problems, as always try to fix them and contribute. If you can't fix the issue, please file a bug under issues so we can track the progress and resolution of the problem.

## Contents

 - [Full Image](#full-image)
 - [Screen Shots](#screen-shots)
 - [License](#license)
 - [Credits](#credits)
 - [Manual installation](#manual-install)

## Full Image

For those not able or willing to set this up themselves I have an [image on my website](https://files.evilbunny.org/stretch-router-rock64-0.6.15-175-arm64.img.xz) based on [ayufan's stretch minimal image](https://github.com/ayufan-rock64/linux-build/releases/download/0.6.15/stretch-minimal-rock64-0.6.15-175-arm64.img.xz).

## Screen Shots

![](https://i.imgur.com/q9vq6ZB.jpg)
![](https://i.imgur.com/cNCQCwB.jpg)
![](https://i.imgur.com/unbB4eo.jpg)
![](https://i.imgur.com/cxHO9bv.jpg)
![](https://i.imgur.com/Z4US3TX.jpg)
![](https://i.imgur.com/JbSQOxY.jpg)
![](https://i.imgur.com/F6q5C7s.jpg)
![](https://i.imgur.com/nC0HeUU.jpg)
![](https://i.imgur.com/KVUfGK6.jpg)

## License

These scripts are made available under the GPLv3 license in the hope they might be useful to others. See LICENSE for details.

## Credits

Inspired by the [raspap webgui](https://github.com/billz/raspap-webgui)

## Manual install

Below is the steps to take to use [ayufan's debian stretch minimal image](https://github.com/ayufan-rock64/linux-build/releases/) to configure the rock64 via a web browser.

```
wget https://github.com/ayufan-rock64/linux-build/releases/download/0.6.15/stretch-minimal-rock64-0.6.15-175-arm64.img.xz
xz -d -v stretch-minimal-rock64-0.6.15-175-arm64.img.xz
mv stretch-minimal-rock64-0.6.15-175-arm64.img stretch-router-rock64-0.6.15-175-arm64.img

losetup -Pf stretch-router-rock64-0.6.15-175-arm64.img
mount /dev/loop0p7 /mnt
mount /dev/loop0p6 /mnt/boot/efi

cp -a /usr/local/sbin/resize_rootfs.sh /mnt/usr/local/sbin/resize_rootfs.sh
cp -a /usr/local/sbin/rock64_diagnostics.sh /mnt/usr/local/sbin/rock64_diagnostics.sh
cp /usr/local/sbin/rtl8812au-dkms_5.2.20-1_all.deb /mnt/usr/src/rtl8812au-dkms_5.2.20-1_all.deb

echo -e "#disable eth1 from working in network manager\niface eth1 inet manual" > /mnt/etc/network/interfaces.d/eth1

chroot /mnt
mount -t proc proc /proc

sed -i -e "s/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/" /etc/locale.gen
sed -i -e "s/# en_AU.UTF-8 UTF-8/en_AU.UTF-8 UTF-8/" /etc/locale.gen
echo "LANG="en_US.UTF-8"">/etc/default/locale
dpkg-reconfigure --frontend=noninteractive locales
update-locale LANG=en_US.UTF-8

sed -i -e "s/^# deb /deb /" /etc/apt/sources.list.d/ayufan-rock64.list
sed -i -e "s/^deb-src/# deb-src/" /etc/apt/sources.list
sed -i -e "s/^#kernel.printk = 3 4 1 3/kernel.printk = 3 4 1 3/" /etc/sysctl.conf
sed -i -e "s/^#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/" /etc/sysctl.conf

rm -f /etc/apt/sources.list.save

cp /etc/skel/.profile /root/.profile
cp /etc/skel/.bashrc /root/.bashrc
cp /etc/skel/.bash_logout /root/.bash_logout

apt-get update; apt-get -y install debfoster dnsutils python dkms less hostapd dnsmasq bc rsync \
	gamin lighttpd openvpn php-cgi libpam0g-dev php-cli mtr-tiny telnet tor

dpkg --purge distro-info-data alsa-utils dh-python firmware-brcm80211 gir1.2-glib-2.0 iso-codes \
	gir1.2-packagekitglib-1.0 jq libasound2 libasound2-data libdbus-glib-1-2 libfftw3-single3 \
	libgirepository-1.0-1 libjq1 libmpdec2 libonig4 libpackagekit-glib2-18 libpython3-stdlib \
	libpython3.5-minimal libpython3.5-stdlib libsamplerate0 lsb-release python-apt-common \
	python3 python3-apt python3-dbus python3-gi python3-minimal python3-pycurl python3.5 \
	python3.5-minimal software-properties-common python3-software-properties linux-rock64 \
	avahi-daemon libavahi-common-data libavahi-common3 libavahi-core7 libdaemon0

apt-get -y dist-upgrade; apt-get autoremove; apt-get clean

# This takes 3-4minutes if nothing else is using CPUs
dpkg -i /usr/src/rtl8812au-dkms_5.2.20-1_all.deb

# disable predictive device names to make it easier for scripts to support unknown wifi devices 
sed -i -e "s/swapaccount=1/swapaccount=1 net.ifnames=0 biosdevname=0/" /boot/efi/extlinux/extlinux.conf

rm -rf /var/www/html
git clone https://github.com/evilbunny2008/rock64-webgui.git /var/www/html

cd /var/www/html
gcc -g -lpam -o chkpasswd pam.c
lighty-enable-mod fastcgi-php

cp /var/www/html/mysql.default.php /var/www/html/mysql.php

apt-get -y install php-mysql mysql-server

mysql
CREATE DATABASE webgui;
CREATE USER 'webgui'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON webgui.* TO 'webgui'@'localhost';
FLUSH PRIVILEGES;
USE webgui;

CREATE TABLE `dnslog` (
  `qid` int(10) UNSIGNED NOT NULL,
  `when` datetime NOT NULL,
  `qtype` enum('A','AAAA') NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `client` varchar(255) NOT NULL,
  `status` enum('cached','config','forwarded') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `dnslog`
  ADD PRIMARY KEY (`qid`,`when`);
exit;

ln -sf /var/www/html/scripts/dnsmasq.logrotate /etc/logrotate.d/dnsmasq
echo -e "*  *\t* * *\troot\t/var/www/html/scripts/scanLog.php" >> /etc/crontab

echo "www-data ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers

mkdir -p /etc/hostapd
echo -e "# Pine64.org hostapd config for rtl8812au usb device\n\nssid=Pine64.org\n" > /etc/hostapd/hostapd.conf
echo -e "interface=wlan0\nhw_mode=a\ncountry_code=GB\nchannel=40\ndriver=nl80211\n" >> /etc/hostapd/hostapd.conf
echo -e "logger_syslog=0\nlogger_syslog_level=0\nwmm_enabled=1\nwpa=2\npreamble=1\n" >> /etc/hostapd/hostapd.conf
echo -e "wpa_passphrase=password\nwpa_key_mgmt=WPA-PSK\nwpa_pairwise=CCMP" >> /etc/hostapd/hostapd.conf
echo -e "rsn_pairwise=CCMP\nauth_algs=1\nmacaddr_acl=0\n\nieee80211n=1" >> /etc/hostapd/hostapd.conf
echo -e "ieee80211d=1\nieee80211ac=1\n\nctrl_interface=/var/run/hostapd" >> /etc/hostapd/hostapd.conf
echo -e "ctrl_interface_group=0\n\nctrl_interface=/var/run/hostapd\nctrl_interface_group=0" >> /etc/hostapd/hostapd.conf

mkdir -p /etc/tor
echo "Log notice file /var/log/tor/notices.log" > "/etc/tor/torrc"
echo "VirtualAddrNetworkIPv4 10.192.0.0/10" >> "/etc/tor/torrc"
echo "AutomapHostsOnResolve 1" >> "/etc/tor/torrc"
echo "TransPort 192.168.99.1:9040" >> "/etc/tor/torrc"
echo "TransPort 127.0.0.1:9040" >> "/etc/tor/torrc"
echo "DNSPort 192.168.99.1:9053" >> "/etc/tor/torrc"
echo "DNSPort 127.0.0.1:9053" >> "/etc/tor/torrc"
echo "AutomapHostsSuffixes .onion,.exit" >> "/etc/tor/torrc"

echo -e "interface=wlan0\nno-dhcp-interface=lo\ndhcp-range=192.168.99.100,192.168.99.199,255.255.255.0,1d" >/etc/dnsmasq.conf 

echo -e "auto wlan0\nallow-hotplug wlan0\niface wlan0 inet static\naddress 192.168.99.1" > /etc/network/interfaces.d/wlan0
echo -e "netmask 255.255.255.0" >> /etc/network/interfaces.d/wlan0
echo -e "post-up iptables -t nat -A POSTROUTING -s 192.168.99.0/24 ! -d 192.168.99.0/24 -j MASQUERADE" >> /etc/network/interfaces.d/wlan0
echo -e "post-up /usr/sbin/hostapd -e /dev/urandom -B -P /var/run/wlan0.pid -f /var/log/hostapd.log /etc/hostapd/hostapd.conf" >> /etc/network/interfaces.d/wlan0
echo -e "pre-down iptables -t nat -D POSTROUTING -s 192.168.99.0/24 ! -d 192.168.99.0/24 -j MASQUERADE" >> /etc/network/interfaces.d/wlan0
echo -e "pre-down killall hostapd" >> /etc/network/interfaces.d/wlan0


mkdir -p /etc/dnsmasq.d
echo -e "log-queries=extra\nlog-facility=/var/log/dnsmasq.log\ndomain-needed\nbogus-priv" > /etc/dnsmasq.d/logging.conf

touch /etc/adfree.conf
/var/www/html/scripts/update-blacklist.php

TZ='UTC' date +"%F %T" > /etc/fake-hwclock.data

rm -f /var/lib/apt/lists/*

cd /usr/share/locale
rm -rf aa as bn ca@valencia csb dv es_AR es_MX es_UY fil ga haw hu ja kok lb mai mr ne oc pms ru shn \
	sr@ijekavian szl ti tt uz@cyrillic wo zu ace ast bn_IN ce cv dz es_CL es_NI es_VE fo \
	gd he hy jv ks li mg ms nl om ps rw si sr@ijekavianlatin ta tig tt@iqtelif ve xh af \
	az bo chr cy el es_CO es_PA et fr gez hi ia ka ku ln mhr mt nl_NL or pt sa sk sr@latin \
	ta_LK tk ug vec yi am be br ckb da es_CR es_PE eu fr_CA gl hne id kk ku_IQ lo mi my nn os \
	pt_BR sc sl sr@Latn te tl uk vi yo an be@latin bs cmn de eo es_DO es_PR fa frp gu hr \
	ig km kw mk nah no pa pt_PT sco so st tet tr ur wa zh_CN ar bem byn crh de_CH \
	en@boldquot es es_EC es_SV fa_AF fur gv hsb is kn ky lt ml nb nqo pam qu sd sq \
	sv tg trv ur_PK wae zh_HK ary bg ca cs de_DE es_419 es_ES es_US fi fy ha ht it \
	ko la lv mn nds nso pl ro se sr sw th ts uz wal zh_TW
cd /

rm -rf /usr/share/doc /usr/share/man /usr/local/sbin/install_* /var/cache/apt/*.bin
dd if=/dev/zero of=zero.txt bs=30M
dd if=/dev/zero of=/boot/efi/zero.txt bs=30M
rm -f zero.txt /boot/efi/zero.txt

umount /proc
exit

rm -f /mnt/root/.bash_history /mnt/root/.nano/* /mnt/root/.mysql_history
rmdir /mnt/root/.nano

umount /dev/loop0p6
umount /dev/loop0p7
losetup -d /dev/loop0
```
