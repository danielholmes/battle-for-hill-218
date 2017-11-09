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
apt-get update -y

# Image optimisation
apt-get install pngquant

# PHP and packages
apt-get install -y php7.0-cli php7.0-mbstring php7.0-dom php7.0-zip php7.0-mysql php-imagick

# MySQL
debconf-set-selections <<< 'mysql-server mysql-server/root_password password battle218pw'
debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password battle218pw'
apt-get install -y mysql-server

# Node
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
curl -s -o nodejs.deb https://deb.nodesource.com/node_8.x/pool/main/n/nodejs/nodejs_8.4.0-1nodesource1~xenial1_amd64.deb
apt-get update -y
apt-get install -y ./nodejs.deb yarn
rm nodejs.deb

# Deps
mkdir -p /home/ubuntu/node_modules
chown ubuntu:ubuntu /home/ubuntu/node_modules
rm -rf "$PROJECT_DIR/node_modules"
ln -s /home/ubuntu/node_modules "$PROJECT_DIR/node_modules"
cd "$PROJECT_DIR" && yarn install

# Composer
COMPOSER_INSTALLER="$PROJECT_DIR/etc/install_composer.sh"
chmod 755 "$COMPOSER_INSTALLER"
"$COMPOSER_INSTALLER"
mv composer.phar /usr/local/bin/composer

# Install composer deps
composer install -d "$PROJECT_DIR"
