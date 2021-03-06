# The Battle for Hill 218

[![Test](https://github.com/danielholmes/battle-for-hill-218/workflows/Test/badge.svg)](https://github.com/danielholmes/battle-for-hill-218/actions)

[BoardGameArena](https://boardgamearena.com/) implementation of the card game 
[The Battle for Hill 218](https://boardgamegeek.com/boardgame/32484/battle-hill-218). Uses the
[BoardGameArena Workbench](https://github.com/danielholmes/bga-workbench).


## Development Requirements

 - [Vagrant](https://www.vagrantup.com/)


## Setting up Developer Machine

Set up vagrant machine and fill in a bgaproject config with your BGA studio details. You should have gotten these 
details in your BGA welcome email.

```
vagrant up
vagrant ssh
cp bgaproject.yml.dist bgaproject.yml
vi bgaproject.yml
```


## Running Analysis and Tests

```
vagrant ssh
composer test
composer bga-validate
composer fix-styles
yarn lint
```


## Compiling images

Compile the images in resources/cards into a tilesheet and provide the CSS. See `CompileImagesCommand` to add or change 
tilesheet.

```
vagrant ssh
composer compile-images
```


## Deploying to Studio

This deploys to BGA studio only and allows you to test on there. Deployment to live BGA is then done manually through 
the studio interface.

```
vagrant ssh
composer deploy
```


## Continuous Deployment to Studio

Watches development files and deploys them as they change.

```
vagrant ssh
bgawb build --deploy --watch
```


## Git Pre-Commit Hook

Available in `etc/pre-commit`. Runs an auto style detection and prevents commit if any issues.


## TODO

 - work out stalemate
   - See final state of this: https://en.boardgamearena.com/#!gamereview?table=35010375 - what if halo had no air strikes
   - https://imgur.com/a/tWwbh
   - Ruling from designer here: https://boardgamegeek.com/thread/1489552/when-you-cant-play-card#body_article21298205
 - add some statistics specific to the game such as number of cards played by type and number of cards destroyed by type
 - during choose attack, highlight card attacking from
 - when cards animating, should be the correct size and orientation
 - Battle for Sector 219 option
 - base indicator shows on new page load if card placed on base then destroyed
 - delay draw cards animation - currently same time as cards returning
