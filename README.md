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
vendor/bin/phpunit
bin/bgawb validate
```


## Deploying to Production

```
vagrant ssh
bin/bgawb deploy
```


## Continuous Deployment to Production

Watches development files and deploys them as they change.

```
vagrant ssh
bin/bgawb watch
```


## Known Workbench Issues

 - When using the watch command - a changed file during the initial deploy won't redeploy
 - SFTP diconnects after a while - should be intelligent enough to reconnect
 