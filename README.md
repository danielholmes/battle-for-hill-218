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
composer run-script fix-styles
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

`classpreloader.php compile --config=build-config.php --output=build/battleforhill.game.php --strip_comments=1`

Probably want a single include that can be used for action, game and view.

See [https://github.com/mamuz/PhpDependencyAnalysis](https://github.com/mamuz/PhpDependencyAnalysis) if need a better
class dependency tree extraction.

`phpda analyze -- analysis.yml`

*analysis.yml*
```yaml
mode: 'usage'
source: './src/TheBattleForHill218'
filePattern: '*.php'
formatter: 'PhpDA\Writer\Strategy\Json'
target: 'build/usage.json'
visitor:
  - PhpDA\Parser\Visitor\TagCollector
  - PhpDA\Parser\Visitor\SuperglobalCollector
```


## Known Workbench Issues

 - When using the watch command - a changed file during the initial deploy won't redeploy
 - SFTP disconnects after a while - should be intelligent enough to reconnect


## Git Pre-Commit Hook

Available in `etc/pre-commit`. Runs an auto style detection and prevents commit if any issues.
