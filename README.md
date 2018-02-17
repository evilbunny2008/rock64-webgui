## rock64-webgui -- Web GUI for the Rock64

This is community maintained project in my free time. Don't expect everything to be perfect and working. Rather be prepared that there are problems, as always try to fix them and contribute. If you can't fix the issue, please file a bug under issues so we can track the progress and resolution of the problem.

## Contents

 - [Manual installation](#manual-install)
 - [Screen Shots](#screen-shots)
 - [License](#license)
 - [Full Image](#full-image)
 - [Credits](#credits)

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

sed -i -e 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen
sed -i -e 's/# en_AU.UTF-8 UTF-8/en_AU.UTF-8 UTF-8/' /etc/locale.gen
echo 'LANG="en_US.UTF-8"'>/etc/default/locale
dpkg-reconfigure --frontend=noninteractive locales
update-locale LANG=en_US.UTF-8

sed -i -e "s/^# deb /deb /" /etc/apt/sources.list.d/ayufan-rock64.list
sed -i -e "s/^deb-src/# deb-src/" /etc/apt/sources.list
sed -i -e "s/^#kernel.printk = 3 4 1 3/kernel.printk = 3 4 1 3/" /etc/sysctl.conf

rm -f /etc/apt/sources.list.save

apt-get update; apt-get -y install debfoster dnsutils python dkms less hostapd dnsmasq bc rsync \
	gamin lighttpd openvpn php-cgi libpam0g-dev

dpkg --purge distro-info-data alsa-utils dh-python firmware-brcm80211 gir1.2-glib-2.0 iso-codes \
	gir1.2-packagekitglib-1.0 jq libasound2 libasound2-data libdbus-glib-1-2 libfftw3-single3 \
	libgirepository-1.0-1 libjq1 libmpdec2 libonig4 libpackagekit-glib2-18 libpython3-stdlib \
	libpython3.5-minimal libpython3.5-stdlib libsamplerate0 lsb-release python-apt-common \
	python3 python3-apt python3-dbus python3-gi python3-minimal python3-pycurl python3.5 \
	python3.5-minimal software-properties-common python3-software-properties

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

echo "www-data ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers
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

rm -f /mnt/root/.bash_history /mnt/root/.nano/*
rmdir /mnt/root/.nano

umount /dev/loop0p6
umount /dev/loop0p7
losetup -d /dev/loop0
```

## Screen Shots

![](https://i.imgur.com/q9vq6ZB.jpg)
![](https://i.imgur.com/cNCQCwB.jpg)
![](https://i.imgur.com/unbB4eo.jpg)
![](https://i.imgur.com/cxHO9bv.jpg)
![](https://i.imgur.com/Z4US3TX.jpg)
![](https://i.imgur.com/JbSQOxY.jpg)
![](https://i.imgur.com/MGfCNOk.jpg)

## License

These scripts are made available under the GPLv3 license in the hope they might be useful to others. See LICENSE for details.

## Full Image

For those not able or willing to set this up themselves I have an [image on my website](https://files.evilbunny.org/stretch-router-rock64-0.6.15-175-arm64.img.xz) based on [ayufan's stretch minimal image](https://github.com/ayufan-rock64/linux-build/releases/download/0.6.15/stretch-minimal-rock64-0.6.15-175-arm64.img.xz).

## Credits

Inspired by the [raspap webgui](https://github.com/billz/raspap-webgui)
