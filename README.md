# The Battle for Hill 218

[![Build Status](https://travis-ci.org/danielholmes/battle-for-hill-218.svg?branch=master)](https://travis-ci.org/danielholmes/battle-for-hill-218)

[BoardGameArena](https://boardgamearena.com/) implementation of the card game 
[The Battle for Hill 218](https://boardgamegeek.com/boardgame/32484/battle-hill-218)


## Development Requirements

 - [Vagrant](https://www.vagrantup.com/)


## Setting up Developer Machine

```
Vagrant up
```


## Running Tests

```
Vagrant ssh
cd battle-for-hill-218
vendor/bin/phpunit
bin/bgawb validate
```


## Deploying to Production

```
Vagrant ssh
cd battle-for-hill-218
bin/bgawb deploy
```


## Continuous Deployment to Production

Watches development files and deploys them as they change.

```
Vagrant ssh
cd battle-for-hill-218
bin/bgawb watch
```