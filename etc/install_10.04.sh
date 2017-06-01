#!/bin/bash
# NOTE: WIP and not working. Need to get a PHP 5.6 installation
# Needs modification to Vagrantfile
# config.vm.box = "f500/ubuntu-lucid64"
# # Disable usb2
#    v.customize ["modifyvm", :id, "--usb", "on"]
#    v.customize ["modifyvm", :id, "--usbehci", "off"]

# Settings
PROJECT_NAME=$1
HOME_DIR=/home/ubuntu
PROJECT_DIR=$HOME_DIR/$PROJECT_NAME

# Make sure start in project directory
echo "cd $PROJECT_DIR" >> "$HOME_DIR/.bashrc"
echo "PATH=\$PATH:$PROJECT_DIR/vendor/bin" >> "$HOME_DIR/.bashrc"
# Not needed once separate out bga workbench project
echo "PATH=\$PATH:$PROJECT_DIR/bin" >> "$HOME_DIR/.bashrc"

# Install essential packages from Apt
apt-get install python-software-properties
add-apt-repository ppa:ondrej/php
sed -i.bak -r 's/(archive|security).ubuntu.com/old-releases.ubuntu.com/g' /etc/apt/sources.list
sed -i.bak -r 's/nl.old-releases.ubuntu.com/old-releases.ubuntu.com/g' /etc/apt/sources.list
apt-get update -y

# PHP and packages
apt-get install -y php5.6-cli php5.6-mbstring php5.6-dom php5.6-zip php5.6-mysql

# MySQL
debconf-set-selections <<< 'mysql-server mysql-server/root_password password battle218pw'
debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password battle218pw'
apt-get install -y mysql-server-5.1

# Composer
COMPOSER_INSTALLER="$PROJECT_DIR/etc/install_composer.sh"
chmod 755 $COMPOSER_INSTALLER
$COMPOSER_INSTALLER
mv composer.phar /usr/local/bin/composer

# Git - not available on 10.04
#apt-get install -y git

# Install composer deps
composer install -d $PROJECT_DIR
