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


## Compiling images

Compile the images in resources/cards into a tilesheet and provide the CSS. See 
`CompileImagesCommand` to add or change tilesheet.

```
vagrant ssh
bfh compile-images
```


## Deploying to Studio

```
vagrant ssh
bgawb deploy
```


## Continuous Deployment to Studio

Watches development files and deploys them as they change.

```
vagrant ssh
bgawb watch
```


## Compilation WIP

It's possible extra files aren't allowed in preproduction and production. If not have been working on the 
following command which compiles:

`classpreloader.php compile --config=build-config.csv.php --output=build/out.php --strip_comments=1`


## Current Studio Versions

 - OS: Ubuntu 16.04.1
 - MySQL: 5.7.18-0ubuntu0.16.04.1
 - PHP: 7.0.18-0ubuntu0.16.04.1


## Known Workbench Issues

 - When using the watch command - a changed file during the initial deploy won't redeploy
 - SFTP disconnects after a while - should be intelligent enough to reconnect


## Git Pre-Commit Hook

Available in `etc/pre-commit`. Runs an auto style detection + fixer.