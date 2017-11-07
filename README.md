# The Battle for Hill 218

[![Build Status](https://travis-ci.org/danielholmes/battle-for-hill-218.svg?branch=master)](https://travis-ci.org/danielholmes/battle-for-hill-218)

[BoardGameArena](https://boardgamearena.com/) implementation of the card game 
[The Battle for Hill 218](https://boardgamegeek.com/boardgame/32484/battle-hill-218). Uses the
[BoardGameArena Workbench](https://github.com/danielholmes/bga-workbench).


## Development Requirements

 - [Vagrant](https://www.vagrantup.com/)


## Setting up Developer Machine

```
vagrant up
```


## Running Tests

```
vagrant ssh
composer test
bgawb validate
composer fix-styles
```


## Compiling images

Compile the images in resources/cards into a tilesheet and provide the CSS. See `CompileImagesCommand` to add or change 
tilesheet.

```
vagrant ssh
composer compile-images
```


## Deploying to Studio

```
vagrant ssh
composer deploy
```


## Continuous Deployment to Studio

Watches development files and deploys them as they change.

```
vagrant ssh
bgawb build -w -d
```


## Git Pre-Commit Hook

Available in `etc/pre-commit`. Runs an auto style detection and prevents commit if any issues.


## TODO

 - add some statistics specific to the game such as number of cards played by type and number of cards destroyed by type
 - during choose attack, highlight card attacking from
 - js linting/hint
 - when cards animating, should be the correct size
 - base indicator shows on new page load if card placed on base then destroyed
 - delay draw cards animation - currently same time as cards returning
 - options for re-implementations (sector 219 and other)
