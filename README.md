# The Battle for Hill 218

[![Build Status](https://travis-ci.org/danielholmes/battle-for-hill-218.svg?branch=master)](https://travis-ci.org/danielholmes/battle-for-hill-218)

[BoardGameArena](https://boardgamearena.com/) implementation of the card game 
[The Battle for Hill 218](https://boardgamegeek.com/boardgame/32484/battle-hill-218)


## Development Requirements

 - [Vagrant](https://www.vagrantup.com/)


## Setting up Developer Machine

```
vagrant up
```


## Running Tests

```
vagrant ssh
phpunit
bgawb validate
phpcbf --standard=PSR2 --tab-width=4 --ignore=src/BGAWorkbench/Stubs,tests/bootstrap.php -q -p tests src
```


## Deploying to Production

```
vagrant ssh
bgawb deploy
```


## Continuous Deployment to Production

Watches development files and deploys them as they change.

```
vagrant ssh
bgawb watch
```


## Notes on VM Compatibility

 - The BGA Workbench requires PHP 5.6+
 - The BGA production environment uses Ubuntu 10.04, PHP 5.3.2 and MySQL 5.1
 - The VM we're currently using is a newer version of Ubuntu (16.04) with PHP 5.6 and MySQL 5.1 (built from source)
 - I've attempted to use a 10.04 VM which conveniently has the correct production MySQL and PHP versions, but couldn't 
   get a PHP 5.6 installation
 - Would be nice to at least provide a PHP 5.3.2 bin in vm to put all production deployable code through its linter
 - Building this stuff from source takes a long time though


## Known Workbench Issues

 - When using the watch command - a changed file during the initial deploy won't redeploy
 - SFTP disconnects after a while - should be intelligent enough to reconnect
 