#!/bin/bash
# Run as root
# See https://gist.github.com/shichao-an/f5639ecd551496ac2d70

set -e

apt-get update
apt-get install -y build-essential
apt-get install -y libncurses5-dev
useradd mysql

cd
wget http://dev.mysql.com/get/Downloads/MySQL-5.1/mysql-5.1.73.tar.gz
tar xzvf mysql-5.1.73.tar.gz
cd mysql-5.1.73

./configure \
'--prefix=/usr' \
'--exec-prefix=/usr' \
'--libexecdir=/usr/sbin' \
'--datadir=/usr/share' \
'--localstatedir=/var/lib/mysql' \
'--includedir=/usr/include' \
'--infodir=/usr/share/info' \
'--mandir=/usr/share/man' \
'--with-system-type=debian-linux-gnu' \
'--enable-shared' \
'--enable-static' \
'--enable-thread-safe-client' \
'--enable-assembler' \
'--enable-local-infile' \
'--with-fast-mutexes' \
'--with-big-tables' \
'--with-unix-socket-path=/var/run/mysqld/mysqld.sock' \
'--with-mysqld-user=mysql' \
'--with-libwrap' \
'--with-readline' \
'--with-ssl' \
'--without-docs' \
'--with-extra-charsets=all' \
'--with-plugins=max' \
'--with-embedded-server' \
'--with-embedded-privilege-control'
make
make install

mkdir -p /etc/mysql
mkdir -p /var/lib/mysql
mkdir -p /etc/mysql/conf.d
echo -e '[mysqld_safe]\nsyslog' > /etc/mysql/conf.d/mysqld_safe_syslog.cnf
cp /usr/share/mysql/my-medium.cnf /etc/mysql/my.cnf
sed -i 's#.*datadir.*#datadir = /var/lib/mysql#g' /etc/mysql/my.cnf
chown mysql:mysql -R /var/lib/mysql

mysql_install_db --user=mysql --ldata=/var/lib/mysql/
mysqld_safe -user=mysql &
# Something creates a loop in vagrant, possibly this...
#/usr/bin/mysql_secure_installation

cp /usr/share/mysql/mysql.server /etc/init.d/mysql
chmod +x /etc/init.d/mysql
update-rc.d mysql defaults
