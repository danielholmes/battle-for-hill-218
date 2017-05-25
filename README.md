# The Battle for Hill 218

[BoardGameArena](https://boardgamearena.com/) implementation of the card game 
[The Battle for Hill 218](https://boardgamegeek.com/boardgame/32484/battle-hill-218)


## Development Requirements

 - PHP 5.6+


## Setting up Developer Machine

```
php composer.phar install
```


## Running Tests

```
vendor/bin/phpunit
bin/bgawb validate
```


## Deploying to Production

```
bin/bgawb deploy
```


## Continuous Deployment to Production

Watches development files and deploys them as they change.

```
bin/bgawb watch
```