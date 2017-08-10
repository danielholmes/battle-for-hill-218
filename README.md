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

Compile the images in resources/cards into a tilesheet and provide the CSS. See `CompileImagesCommand` to add or change 
tilesheet.

```
vagrant ssh
bfh compile-images
```

Use [https://www.youidraw.com/apps/drawing/](https://www.youidraw.com/apps/drawing/) for creating interaction borders.
Radius: 5, Dashes: 18, 10, Cap Mode: Left - not overstepping


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

It's possible extra files aren't allowed in preproduction and production. If not have been working on the following 
command which compiles:

`classpreloader.php compile --config=build-config.csv.php --output=build/out.php --strip_comments=1`


## Known Workbench Issues

 - When using the watch command - a changed file during the initial deploy won't redeploy
 - SFTP disconnects after a while - should be intelligent enough to reconnect


## Git Pre-Commit Hook

Available in `etc/pre-commit`. Runs an auto style detection and prevents commit if any issues.


## TODO

 - tooltips on cards -> I think you should always display the tooltips when hovering over the card (not just for cards 
   in hand when it's your turn to play)
 
 - when destroying a card, it would be nice to have the explosion symbol briefly displayed over it :)
  
 - It would be easier if the battlefield was always shown from the same side, no matter which color you are playing. By 
   this I mean that if I'm red, I play my cards from the bottom, if I'm blue, I still play my cards from the bottom. You 
   would have to do some magic with the view.php and the .tpl file

 - make sure the cards are inside the "map_scrollable" div. Currently you cannot move the map around when you click on a 
   card because they are in the "map_scrollable_oversurface".

 - I think it would be very useful to have a zoom functionality on the play zone (you can get a readonly copy of Gaia 
   project from the project page to check out how to do that)
 
 - at the start of the game having 'player 1 base' & 'player 2 base' displayed like in the rulebook would help beginners
