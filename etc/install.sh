#!/bin/bash

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
add-apt-repository -y ppa:ondrej/php
apt-get update -y

# PHP and packages
apt-get install -y php5.6-cli php5.6-mbstring php5.6-dom php5.6-zip php5.6-mysql

# MySQL TODO: Should be version 5.1.73-0ubuntu0.10.04.1 if can
#debconf-set-selections <<< 'mysql-server mysql-server/root_password password battle218pw'
#debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password battle218pw'
#apt-get install -y mysql-server
MYSQL_INSTALLER="$PROJECT_DIR/etc/install_mysql.sh"
chmod 755 $MYSQL_INSTALLER
$MYSQL_INSTALLER
mysql -u root -e "SET PASSWORD FOR 'root'@'localhost' = PASSWORD('battle218pw');"

# Composer
COMPOSER_INSTALLER="$PROJECT_DIR/etc/install_composer.sh"
chmod 755 $COMPOSER_INSTALLER
$COMPOSER_INSTALLER
mv composer.phar /usr/local/bin/composer

# Git
apt-get install -y git

# Install composer deps
composer install -d $PROJECT_DIR
