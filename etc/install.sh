#!/bin/bash

# Settings
PROJECT_NAME=$1
DB_NAME=$PROJECT_NAME
PROJECT_DIR=/home/ubuntu/$PROJECT_NAME

# Install essential packages from Apt
add-apt-repository ppa:ondrej/php
apt-get update -y

# PHP and packages
apt-get install -y php5.6 php5.6-mbstring php5.6-dom php5.6-zip

# MySQL TODO: Should be version 5.1.73-0ubuntu0.10.04.1 if can
#debconf-set-selections <<< 'mysql-server mysql-server/root_password password your_password'
#debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password your_password'
export DEBIAN_FRONTEND=noninteractive
apt-get install -y mysql-server
mysqladmin -u root password battle218pw

# Composer
COMPOSER_INSTALLER="$PROJECT_DIR/etc/install_composer.sh"
chmod 755 $COMPOSER_INSTALLER
$COMPOSER_INSTALLER
mv composer.phar /usr/local/bin/composer

# Git
apt-get install -y git

# Install composer deps
composer install -d $PROJECT_DIR