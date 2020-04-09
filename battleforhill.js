'use strict';

define(
  [
    'dojo',
    'dojo/_base/declare',
    'dojo/_base/lang',
    'dojo/dom',
    'dojo/query',
    'dojo/_base/array',
    'dojo/dom-construct',
    'dojo/dom-class',
    'dojo/dom-geometry',
    'dojo/fx',
    'dojo/NodeList-data',
    'dojo/NodeList-traverse',
    'dojo/NodeList-html',
    'ebg/core/gamegui',
    'ebg/counter',
    'ebg/scrollmap'
  ],
  function(dojo, declare, lang, dom, query, array, domConstruct, domClass, domGeom, fx) {
    // Should be the same dimensions as battlefield-card in css
    var CARD_WIDTH = 160;
    var CARD_HEIGHT = 230;
    var SLIDE_ANIMATION_DURATION = 700;
    var MAX_ZOOM = 10;
    var MIN_ZOOM = 2;

    return declare('bgagame.battleforhill', ebg.core.gamegui, {
      constructor: function() {
        this.battlefieldMap = new ebg.scrollmap();
        this.zoomLevel = 6;
      },

      /**
       * Sets up the game user interface according to current game situation specified
       * in parameters. Method is called each time the game interface is displayed to a player, ie:
       *  - when the game starts
       *  - when a player refreshes the game page (F5)
       */
      setup: function(datas) {
        this.setupLayout();
        this.setupPlayerCards(datas.players);
        this.setupBattlefield(datas.battlefield);
        this.setupNotifications();
      },

      setupPlayerCards: function(players) {
        this.playerData = players;

        // Base indicators
        for (var i in this.playerData) {
          if (this.playerData.hasOwnProperty(i)) {
            var basePlayer = this.playerData[i];
            var position = null;
            if (basePlayer.isDownwardPlayer) {
              position = this.getOrCreatePlacementPosition(0, -1);
            } else {
              position = this.getOrCreatePlacementPosition(0, 1);
            }
            domConstruct.place(this.createBaseIndicator(basePlayer.name), position);
          }
        }

        // Icons and hand cards
        for (var id in players) {
          if (players.hasOwnProperty(id)) {
            var player = players[id];
            domConstruct.place(
              this.format_block('jstpl_counter_icons', {playerId: id}),
              this.getPlayerBoardNode(id)
            );
            this.addTooltip('deck-count-' + id, _('Number of cards left in deck'), '');
            this.addTooltip('hand-count-' + id, _('Number of battlefield cards in hand'), '');
            this.addTooltip('air-strike-count-' + id, _('Number of air strike cards in hand'), '');
            this.addTooltip('units-in-play-count-' + id, _('Number of units in play'), '');
            this.addTooltip('units-destroyed-count-' + id, _('Number of units destroyed'), '');
            this.updatePlayerNumber(id, player.number);
            this.updateDeckCount(id, player.deckSize);
            this.updateHandCount(id, player.handSize);
            this.updateUnitsDestroyedCount(id, player.numDefeatedCards);
            this.updateAirStrikeCount(id, player.numAirStrikes);
            this.updateUnitsInPlayCount(id, player.numUnitsInPlay);
            if (id.toString() === this.player_id.toString()) {
              this.setupCurrentPlayerCards(player);
            }
          }
        }
      },

      setupCurrentPlayerCards: function(data) {
        var cardNodes = array.map(
          data.cards,
          lang.hitch(this, function(card) {
            return this.createCurrentPlayerHandCard(card, data.color);
          })
        );
        array.forEach(
          cardNodes,
          lang.hitch(this, function(cardNode) {
            if (query(cardNode).attr('data-type').pop() === 'air-strike') {
              this.placeInCurrentPlayerAirStrikes(cardNode);
            } else {
              this.placeInCurrentPlayerHand(cardNode);
            }
          })
        );
        array.forEach(
          cardNodes,
          lang.hitch(this, function(cardNode) {
            this.updatePlayableCardTooltip(cardNode);
          })
        );
      },

      setupBattlefield: function(data) {
        array.forEach(data, lang.hitch(this, this.placeBattlefieldCard));
      },

      setupLayout: function() {
        this.updateUi();
        dojo.connect(this, 'onGameUiWidthChange', this, lang.hitch(this, this.updateUi));

        this.battlefieldMap.create(
          query('#map_container').pop(),
          query('#map_scrollable').pop(),
          query('#map_surface').pop(),
          query('#map_scrollable_oversurface').pop()
        );
        query('#movetop').on('click', lang.hitch(this, this.onMoveTop));
        query('#moveleft').on('click', lang.hitch(this, this.onMoveLeft));
        query('#moveright').on('click', lang.hitch(this, this.onMoveRight));
        query('#movedown').on('click', lang.hitch(this, this.onMoveDown));
        query('#zoom_in').on('click', lang.hitch(this, this.onZoomIn));
        query('#zoom_out').on('click', lang.hitch(this, this.onZoomOut));
        query('#reset_map').on('click', lang.hitch(this, this.onResetMap));
        this.applyBattlefieldZoom();
        this.addTooltip('zoom_in', _('Zoom In'), '');
        this.addTooltip('zoom_out', _('Zoom Out'), '');
        this.addTooltip('reset_map', _('Reset Map'), '');
      },

      /************************************
        Game & client states
       */
      onEnteringState: function(stateName, event) {
        switch (stateName) {
        case 'returnToDeck':
          this.onEnterReturnToDeck();
          break;
        case 'playCard':
          if (this.isCurrentPlayerActive()) {
            this.onEnterPlayCard(event.args._private);
          }
          break;
        case 'chooseAttack':
          if (this.isCurrentPlayerActive()) {
            this.onEnterChooseAttack(event.args._private);
          }
          break;
        }
      },

      onEnterReturnToDeck: function() {
        this.enableHandCardsClick(this.onHandCardReturnClick, _('Return this card'), _('Do not return this card'));
      },

      onEnterPlayCard: function(possiblePlacementsByCardId) {
        this.possiblePlacementsByCardId = possiblePlacementsByCardId;

        this.enablePlayCards();

        var that = this;
        this.placeButtonClickSignal = query(this.getBattlefieldInteractionNode()).on(
          '.battlefield-button.clickable:click',
          function() {
            lang.hitch(that, that.onPlacePositionClick)({target: this});
          }
        );
      },

      onEnterChooseAttack: function(possiblePlacements) {
        query(this.getBattlefieldInteractionNode()).addClass('state-choose-attack');
        array.forEach(
          possiblePlacements,
          lang.hitch(this, function(possiblePlacement) {
            this.activatePossiblePlacementPosition(possiblePlacement, _('Attack this card'));
          })
        );

        var that = this;
        this.attackPositionClickSignal = query(this.getBattlefieldInteractionNode()).on(
          '.battlefield-button.clickable:click',
          function() {
            lang.hitch(that, that.onAttackPositionClick)({target: this});
          }
        );
      },

      /************************************
        onLeavingState: this method is called each time we are leaving a game state.
       */
      onLeavingState: function(stateName) {
        switch (stateName) {
        case 'playCard':
          if (this.isCurrentPlayerActive()) {
            this.onLeavePlayCard();
          }
          break;
        case 'chooseAttack':
          if (this.isCurrentPlayerActive()) {
            this.onLeaveChooseAttack();
          }
          break;
        }
      },

      onLeavePlayCard: function() {
        if (this.placeButtonClickSignal) {
          this.placeButtonClickSignal.remove();
          this.placeButtonClickSignal = null;
        }
        if (this.placeAirStrikeClickSignal) {
          this.placeAirStrikeClickSignal.remove();
          this.placeAirStrikeClickSignal = null;
        }
        this.deactivateAllPlacementPositions();
        this.disablePlayableCardsClick();
      },

      onLeaveChooseAttack: function() {
        if (this.attackPositionClickSignal) {
          this.attackPositionClickSignal.remove();
          this.attackPositionClickSignal = null;
        }
        query(this.getBattlefieldInteractionNode()).removeClass('state-choose-attack');
        this.deactivateAllPlacementPositions();
      },

      // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
      //            action status bar (ie: the HTML links in the status bar).
      //
      onUpdateActionButtons: function(stateName) {
        if (this.isCurrentPlayerActive()) {
          switch (stateName) {
          case 'returnToDeck':
            this.addActionButton('button_1_id', _('Return the selected cards'), 'onSubmitReturnCards');
            break;
          }
        }
      },

      /************************************
        DOM Node Utility methods
       */
      getBattlefieldNode: function() {
        return query('#map_scrollable').query('.zoomable').pop();
      },

      getBattlefieldInteractionNode: function() {
        return query('#map_scrollable_oversurface').query('.zoomable').pop();
      },

      getPlayerBoardNode: function(playerId) {
        return query('#player_board_' + playerId).pop();
      },

      getCurrentPlayerAirStrikesNodeList: function() {
        return query(this.getCurrentPlayerAirStrikesContainerNode()).query('.playable-card');
      },

      getCurrentPlayerCardsNode: function() {
        return dom.byId('players-panel');
      },

      getCurrentPlayerHandCardsNodeList: function() {
        // Note: .hand-card not needed since have .hand-cards container?
        return query(this.getCurrentPlayerCardsNode()).query('.hand-card');
      },

      getCurrentPlayerAirStrikesContainerNode: function() {
        return query(this.getCurrentPlayerCardsNode()).query('.player-cards').query('.air-strike-cards').pop();
      },

      getCurrentPlayerHandCardsNode: function() {
        return query(this.getCurrentPlayerCardsNode()).query('.player-cards').query('.hand-cards').pop();
      },

      getCurrentPlayerHandCardNodeByCardId: function(cardId) {
        return query(this.getCurrentPlayerHandCardsNodeList()).filter('[data-id=' + cardId + ']').pop();
      },

      getPlayerDeckNode: function(playerId) {
        return query('#overall_player_board_' + playerId).query('.deck-count').pop();
      },

      getPlayerHandCardsIconNode: function(playerId) {
        return query('#overall_player_board_' + playerId).query('.hand-count').pop();
      },

      getCurrentPlayerDeckNode: function() {
        return this.getPlayerDeckNode(this.player_id);
      },

      placeInCurrentPlayerAirStrikes: function(card) {
        domConstruct.place(card, this.getCurrentPlayerAirStrikesContainerNode());
      },

      placeInCurrentPlayerHand: function(card) {
        domConstruct.place(this.recoverFromAnimation(card), this.getCurrentPlayerHandCardsNode());
      },

      getCurrentPlayerPlayableCardNodeList: function() {
        return query(this.getCurrentPlayerCardsNode()).query('.playable-card');
      },

      getCurrentPlayerSelectedCardIds: function() {
        return this.getCurrentPlayerPlayableCardNodeList().filter('.selected').attr('data-id');
      },

      createHiddenPlayerAirStrikeCard: function(color) {
        var card = {type: 'air-strike', color: color};
        return domConstruct.toDom(this.format_block('jstpl_opponent_air_strike_card', card));
      },

      createCurrentPlayerHandCard: function(card, color) {
        var coloredCard = lang.mixin({}, card, {color: color});
        return domConstruct.toDom(this.format_block('jstpl_hand_card', coloredCard));
      },

      createExplosion: function() {
        return domConstruct.toDom(this.format_block('jstpl_explosion'));
      },

      createZoomedSlidingCard: function(card, color) {
        return domConstruct.toDom(this.format_block(
          'jstpl_zoomed_sliding_card',
          {
            zoom: this.zoomLevel,
            card: this.createBattlefieldCardHtml(card, color)
          }
        ));
      },

      createBattlefieldCard: function(card, color) {
        return domConstruct.toDom(this.createBattlefieldCardHtml(card, color));
      },

      createBattlefieldCardHtml: function(card, color) {
        var coloredCard = lang.mixin({}, card, {color: color});
        return this.format_block('jstpl_battlefield_card', coloredCard);
      },

      createBaseIndicator: function(name) {
        var nameOwnership = name;
        if (nameOwnership.lastIndexOf('s') === nameOwnership.length - 1) {
          nameOwnership += '\'';
        } else {
          nameOwnership += '\'s';
        }
        return domConstruct.toDom(
          this.format_block(
            'jstpl_base_indicator',
            {
              baseName: dojo.string.substitute(_('${nameOwnership} Base'), {nameOwnership: nameOwnership})
            }
          )
        );
      },

      placeBattlefieldCard: function(card) {
        var position = this.getOrCreatePlacementPosition(card.x, card.y);

        var existing = query(position).query('.battlefield-card');
        if (existing.length > 0) {
          dojo.destroy(existing.pop());
        }
        domConstruct.place(this.createBattlefieldCard(card, card.playerColor || ''), position);
      },

      updatePlayerNumber: function(playerId, number) {
        var text = number;
        if (number === 1) {
          text += 'st';
        } else {
          text += 'nd';
        }
        this.updateCounter('.player-number', playerId, text);
      },

      updateDeckCount: function(playerId, count) {
        this.updateCounter('.deck-count', playerId, count);
      },

      updateHandCount: function(playerId, count) {
        this.updateCounter('.hand-count', playerId, count);
      },

      updateUnitsDestroyedCount: function(playerId, count) {
        this.updateCounter('.units-destroyed', playerId, count);
      },

      updateUnitsInPlayCount: function(playerId, count) {
        this.updateCounter('.units-in-play', playerId, count);
      },

      updateAirStrikeCount: function(playerId, count) {
        this.updateCounter('.air-strike-count', playerId, count);
      },

      updateCounter: function(counterSelector, playerId, count) {
        this.getCounter(counterSelector, playerId)
          .query('.counter-text')
          .pop().innerHTML = count;
      },

      getAirStrikeCounter: function(playerId) {
        return this.getCounter('.air-strike-count', playerId);
      },

      getCounter: function(counterSelector, playerId) {
        return query(this.getPlayerBoardNode(playerId)).query(counterSelector);
      },

      updatePlayableCardTooltip: function(cardNode, message) {
        if (!message) {
          message = '';
        }
        this.addTooltipHtml(
          cardNode.id,
          this.format_block(
            'jstpl_card_tooltip',
            {
              message: message,
              color: query(cardNode).attr('data-color').pop(),
              type: query(cardNode).attr('data-type').pop()
            }
          )
        );
      },

      enablePlayCards: function() {
        this.enableCardsClick(
          this.onCardPlayClick,
          _('Play this card on the battlefield'),
          _('Deselect this card')
        );
      },

      updateUi: function() {
        var screenHeight = window.innerHeight ||
          document.documentElement.clientHeight ||
          document.body.clientHeight;

        var containerPos = dojo.position('battlefield-panel');

        screenHeight /= this.gameinterface_zoomFactor;

        var mapHeight = Math.max(500, screenHeight - containerPos.y - 30);
        dojo.style('battlefield-panel', 'height', mapHeight + 'px');
      },

      /************************************
        Animation Utility methods
       */
      callBackend: function(name, params) {
        params.lock = true;
        this.ajaxcall(
          '/battleforhill/battleforhill/' + name + '.html',
          params,
          function() {
            // success callback. Careful trying to use this - wont come through for replays + zombie turns
          },
          function() {
            // error callback, see above
          }
        );
      },

      prepareForAnimation: function(node) {
        if (!node) {
          throw new Error('Must provide a node');
        }
        return query(node)
          .style('zIndex', 100)
          .style('position', 'absolute')
          .pop();
      },

      recoverFromAnimation: function(node) {
        if (!node) {
          throw new Error('Must provide a node');
        }
        return query(node)
          .style('zIndex', null)
          .style('position', null)
          .style('left', null)
          .style('top', null)
          .pop();
      },

      getCentredPosition: function(from, target) {
        var fromBox = domGeom.position(from);
        var targetBox = domGeom.position(target);
        return {
          x: targetBox.x + (targetBox.w / 2) - (fromBox.w / 2),
          y: targetBox.y + (targetBox.h / 2) - (fromBox.h / 2)
        };
      },

      slideToDeckAndDestroy: function(card, deck) {
        this.slideToObjectAndDestroy(this.prepareForAnimation(card), deck, SLIDE_ANIMATION_DURATION);
      },

      slideNewElementTo: function(from, newElement, target, offset) {
        if (!from) {
          throw new Error('slideNewElementTo: from element is null');
        }
        if (!newElement) {
          throw new Error('slideNewElementTo: newElement is null');
        }
        if (!target) {
          throw new Error('slideNewElementTo: target element is null');
        }

        if (!offset) {
          offset = {x: 0, y: 0};
        }
        this.prepareForAnimation(newElement);
        domConstruct.place(newElement, query('body').pop());
        this.placeOnObject(newElement, from);
        var targetPosition = this.getCentredPosition(newElement, target);
        return fx.slideTo({
          node: newElement,
          left: targetPosition.x + offset.x,
          top: targetPosition.y + offset.y,
          units: 'px',
          duration: SLIDE_ANIMATION_DURATION
        }).play();
      },

      /************************************
        Interaction utility methods
       */
      enableCardsClick: function(handler, tooltip, selectedTooltip) {
        this.enableCardsClickByContainer(
          query(this.getCurrentPlayerCardsNode()),
          handler,
          tooltip,
          selectedTooltip
        );
      },

      enableHandCardsClick: function(handler, tooltip, selectedTooltip) {
        this.enableCardsClickByContainer(
          query(this.getCurrentPlayerHandCardsNode()),
          handler,
          tooltip,
          selectedTooltip
        );
      },

      enableCardsClickByContainer: function(container, handler, tooltip, selectedTooltip) {
        if (container === null) {
          return;
        }

        handler = lang.hitch(this, handler);
        if (!selectedTooltip) {
          selectedTooltip = tooltip;
        }

        this.disablePlayableCardsClick();

        container.addClass('clickable');
        container.query('.playable-card')
          .forEach(lang.hitch(this, function(cardNode) {
            if (!cardNode.id) {
              throw new Error('Trying to add tooltip to node without id', cardNode);
            }
            this.updatePlayableCardTooltip(cardNode, tooltip);
          }));

        var that = this;
        this.currentPlayerCardsClickSignal = container.on(
          '.playable-card:click',
          function() {
            var cardNode = this;
            lang.hitch(that, function() {
              handler({target: cardNode});
              if (domClass.contains(cardNode, 'selected')) {
                this.updatePlayableCardTooltip(cardNode, selectedTooltip);
              } else {
                this.updatePlayableCardTooltip(cardNode, tooltip);
              }
            })();
          }
        );
      },

      removeClickableOnNodeAndChildren: function(node) {
        var nodeList = query(node);
        nodeList.removeClass('clickable');
        array.forEach(
          nodeList.children(),
          lang.hitch(this, this.removeClickableOnNodeAndChildren)
        );
      },

      disablePlayableCardsClick: function() {
        var cardsContainer = this.getCurrentPlayerCardsNode();
        if (cardsContainer !== null) {
          query(cardsContainer).query('.playable-card')
            .forEach(lang.hitch(this, function(cardNode) {
              this.updatePlayableCardTooltip(cardNode);
            }));
          this.removeClickableOnNodeAndChildren(cardsContainer);
          query(cardsContainer).query('.selected').removeClass('selected');
        }
        if (this.currentPlayerCardsClickSignal) {
          this.currentPlayerCardsClickSignal.remove();
          this.currentPlayerCardsClickSignal = null;
        }
      },

      /************************************
        Battlefield utility methods
       */
      deactivateAllPlacementPositions: function() {
        var clickableButtons = query(this.getBattlefieldInteractionNode()).query('.clickable');
        clickableButtons.removeClass('clickable');
        clickableButtons.forEach(lang.hitch(this, function(positionNode) {
          this.removeTooltip(positionNode.id);
        }));
      },

      activatePossiblePlacementPosition: function(position, tooltip) {
        var buttonNode = this.getOrCreatePlacementButton(position.x, position.y);
        query(buttonNode).addClass('clickable');
        this.addTooltip(buttonNode.id, '', tooltip);
      },

      getOrCreatePlacementButton: function(x, y) {
        return this.getOrCreatePlacement(this.getBattlefieldInteractionNode(), 'jstpl_battlefield_button', x, y);
      },

      getOrCreatePlacementPosition: function(x, y) {
        return this.getOrCreatePlacement(this.getBattlefieldNode(), 'jstpl_battlefield_position', x, y);
      },

      getOrCreatePlacement: function(container, templateName, x, y) {
        var placement = query(container).query('[data-x=' + x + '][data-y=' + y + ']').pop();
        if (placement) {
          return placement;
        }

        var left = (-CARD_WIDTH / 2) + (x * CARD_WIDTH);
        var top = (-CARD_HEIGHT / 2) - (y * CARD_HEIGHT);
        if (this.isViewingAsUpwardsPlayer()) {
          top *= -1;
          top -= CARD_HEIGHT;
        }
        placement = domConstruct.toDom(this.format_block(templateName, {x: x, y: y, top: top, left: left}));
        domConstruct.place(placement, container);
        return placement;
      },

      isViewingAsUpwardsPlayer: function() {
        return document.getElementById('game-container').className.indexOf('viewing-as-upwards-player') >= 0;
      },

      explodeCard: function(position) {
        var explosion = this.createExplosion();
        domConstruct.place(explosion, position);
        this.fadeOutAndDestroy(explosion);
        this.fadeOutAndDestroy(query(position).query('.battlefield-card').pop());
      },

      /************************************
        Player's action
       */
      onHandCardReturnClick: function(e) {
        query(e.target).toggleClass('selected');
      },

      onSubmitReturnCards: function() {
        if (!this.checkAction('returnToDeck')) {
          return;
        }

        var selectedIds = this.getCurrentPlayerSelectedCardIds();
        // Where should this business logic go?
        if (selectedIds.length !== 2) {
          this.showMessage(_('You must select exactly 2 cards to return'), 'error');
          return;
        }

        this.disablePlayableCardsClick();
        this.callBackend('returnToDeck', {ids: selectedIds.join(',')});
      },

      onCardPlayClick: function(e) {
        this.playCard(query(e.target).attr('data-id')[0]);
      },

      playCard: function(cardId) {
        this.getCurrentPlayerPlayableCardNodeList().forEach(function(card) {
          if (query(card).attr('data-id')[0] === cardId) {
            query(card).toggleClass('selected');
          } else {
            query(card).removeClass('selected');
          }
        });

        this.deactivateAllPlacementPositions();
        var selectedIds = this.getCurrentPlayerSelectedCardIds();
        if (selectedIds.length === 0) {
          return;
        }

        var selectedId = selectedIds.pop();
        var possiblePlacements = this.possiblePlacementsByCardId[selectedId];
        array.forEach(
          possiblePlacements,
          lang.hitch(this, function(possiblePlacement) {
            this.activatePossiblePlacementPosition(possiblePlacement, _('Place card here'));
          })
        );
      },

      onPlacePositionClick: function(e) {
        if (!this.checkAction('playCard')) {
          return;
        }

        var id = this.getCurrentPlayerSelectedCardIds()[0];
        var position = query(e.target);
        var x = position.attr('data-x').pop();
        var y = position.attr('data-y').pop();
        this.callBackend('playCard', {id: id, x: x, y: y});
      },

      onAttackPositionClick: function(e) {
        if (!this.checkAction('chooseAttack')) {
          return;
        }

        var position = query(e.target);
        var x = position.attr('data-x').pop();
        var y = position.attr('data-y').pop();
        this.callBackend('chooseAttack', {x: x, y: y});
      },

      onMoveTop: function(e) {
        e.preventDefault();
        this.scrollBattlefield(0, 1);
      },

      onMoveLeft: function(e) {
        e.preventDefault();
        this.scrollBattlefield(1, 0);
      },

      onMoveRight: function(e) {
        e.preventDefault();
        this.scrollBattlefield(-1, 0);
      },

      onMoveDown: function(e) {
        e.preventDefault();
        this.scrollBattlefield(0, -1);
      },

      scrollBattlefield: function(xDirection, yDirection) {
        var zoomRatio = this.zoomLevel / 10.0;
        this.battlefieldMap.scroll(xDirection * zoomRatio * CARD_WIDTH, yDirection * zoomRatio * CARD_HEIGHT);
      },

      zoomBattlefield: function(offset) {
        var newZoomLevel = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, this.zoomLevel + offset));
        if (newZoomLevel === this.zoomLevel) {
          return;
        }

        query('.zoomable').removeClass('zoom' + this.zoomLevel);
        this.zoomLevel = newZoomLevel;
        this.applyBattlefieldZoom();
      },

      applyBattlefieldZoom: function() {
        query('.zoomable').addClass('zoom' + this.zoomLevel);

        query('#zoom_in').removeAttr('disabled');
        query('#zoom_out').removeAttr('disabled');
        if (this.zoomLevel === MIN_ZOOM) {
          query('#zoom_out').attr('disabled', 'disabled');
        }
        if (this.zoomLevel === MAX_ZOOM) {
          query('#zoom_in').attr('disabled', 'disabled');
        }
      },

      onZoomIn: function(e) {
        e.preventDefault();
        this.zoomBattlefield(1);
      },

      onZoomOut: function(e) {
        e.preventDefault();
        this.zoomBattlefield(-1);
      },

      onResetMap: function(e) {
        e.preventDefault();
        this.battlefieldMap.scrollto(0, 0);
      },

      /************************************
        Reaction to cometD notifications
       */
      setupNotifications: function() {
        dojo.subscribe('iReturnedToDeck', lang.hitch(this, this.onNotifIReturnedToDeck));
        dojo.subscribe('returnedToDeck', lang.hitch(this, this.onNotifReturnedToDeck));
        dojo.subscribe('cardsDrawn', lang.hitch(this, this.onNotifCardsDrawn));
        dojo.subscribe('myCardsDrawn', lang.hitch(this, this.onNotifMyCardsDrawn));
        dojo.subscribe('iPlacedCard', lang.hitch(this, this.onNotifIPlacedCard));
        dojo.subscribe('placedCard', lang.hitch(this, this.onNotifPlacedCard));
        dojo.subscribe('iPlayedAirStrike', lang.hitch(this, this.onNotifIPlayedAirStrike));
        dojo.subscribe('playedAirStrike', lang.hitch(this, this.onNotifPlayedAirStrike));
        dojo.subscribe('cardAttacked', lang.hitch(this, this.onNotifCardAttacked));
        dojo.subscribe('newScores', lang.hitch(this, this.onNotifNewScores));
        dojo.subscribe('endOfGame', this, function() {
          // placeholder to allow delay below
        });
        // Delay end of game for interface stock stability before switching to game result
        this.notifqueue.setSynchronous('endOfGame', 2000);
      },

      onNotifCardAttacked: function(notification) {
        var x = notification.args.x;
        var y = notification.args.y;
        var opponentPlayerId = notification.args.opponentPlayerId;
        var opponentUnitsInPlay = notification.args.opponentUnitsInPlay;
        var playerId = notification.args.playerId;
        var numDefeatedCards = notification.args.numDefeatedCards;
        var position = this.getOrCreatePlacementPosition(x, y);
        this.explodeCard(position);
        this.updateUnitsInPlayCount(opponentPlayerId, opponentUnitsInPlay);
        this.updateUnitsDestroyedCount(playerId, numDefeatedCards);
      },

      onNotifPlacedCard: function(notification) {
        var playerId = notification.args.playerId;
        this.updateHandCount(playerId, notification.args.handCount);
        this.updateUnitsInPlayCount(playerId, notification.args.unitsInPlay);
        if (playerId === this.player_id) {
          return;
        }

        var x = notification.args.x;
        var y = notification.args.y;
        var cardType = notification.args.typeKey;
        var color = notification.args.playerColor;
        var cardNode = this.createZoomedSlidingCard({type: cardType}, color);
        var handCardsNode = this.getPlayerHandCardsIconNode(playerId);
        var position = this.getOrCreatePlacementPosition(x, y);

        this.slideNewElementTo(handCardsNode, cardNode, position)
          .on('End', lang.hitch(this, function() {
            dojo.destroy(cardNode);
            this.placeBattlefieldCard({type: cardType, playerColor: color, x: x, y: y});
          }));
      },

      onNotifIPlacedCard: function(notification) {
        var cardId = notification.args.cardId;
        var cardNode = this.getCurrentPlayerHandCardNodeByCardId(cardId);
        var x = notification.args.x;
        var y = notification.args.y;
        var type = query(cardNode).attr('data-type').pop();
        var color = query(cardNode).attr('data-color').pop();

        var position = this.getOrCreatePlacementPosition(x, y);
        this.slideToObjectAndDestroy(this.prepareForAnimation(cardNode), position, SLIDE_ANIMATION_DURATION);
        setTimeout(lang.hitch(this, function() {
          this.placeBattlefieldCard({type: type, playerColor: color, x: x, y: y});
        }), SLIDE_ANIMATION_DURATION);
      },

      onNotifPlayedAirStrike: function(notification) {
        var playerId = notification.args.playerId;
        var opponentPlayerId = notification.args.opponentPlayerId;
        var opponentUnitsInPlay = notification.args.opponentUnitsInPlay;
        var numDefeatedCards = notification.args.numDefeatedCards;

        this.updateAirStrikeCount(playerId, notification.args.numAirStrikes);
        this.updateUnitsDestroyedCount(playerId, numDefeatedCards);
        this.updateUnitsInPlayCount(opponentPlayerId, opponentUnitsInPlay);
        if (playerId === this.player_id) {
          return;
        }

        var x = notification.args.x;
        var y = notification.args.y;
        var airStrikeCard = this.createHiddenPlayerAirStrikeCard(notification.args.playerColor);
        var position = this.getOrCreatePlacementPosition(x, y);

        this.slideNewElementTo(this.getAirStrikeCounter(playerId).pop(), airStrikeCard, position)
          .on('End', lang.hitch(this, function() {
            dojo.destroy(airStrikeCard);
            this.explodeCard(position);
          }));
      },

      onNotifIPlayedAirStrike: function(notification) {
        var airStrikeNode = this.getCurrentPlayerAirStrikesNodeList()
          .filter('[data-id=' + notification.args.cardId + ']').pop();

        var x = notification.args.x;
        var y = notification.args.y;
        var position = this.getOrCreatePlacementPosition(x, y);
        this.slideToObjectAndDestroy(this.prepareForAnimation(airStrikeNode), position, SLIDE_ANIMATION_DURATION);
        setTimeout(lang.hitch(this, function() {
          dojo.destroy(airStrikeNode);
          this.explodeCard(position);
        }), SLIDE_ANIMATION_DURATION);
      },

      onNotifCardsDrawn: function(notification) {
        var playerId = notification.args.playerId;
        this.updateHandCount(playerId, notification.args.handCount);
        this.updateDeckCount(playerId, notification.args.deckCount);
      },

      onNotifMyCardsDrawn: function(notification) {
        var playerColor = notification.args.playerColor;
        var cardNodes = array.map(
          notification.args.cards,
          lang.hitch(this, function(card) {
            return this.createCurrentPlayerHandCard(card, playerColor);
          })
        );
        array.forEach(
          cardNodes,
          lang.hitch(this, function(cardDisplay, i) {
            var offset = i * 20;
            this.slideNewElementTo(
              this.getCurrentPlayerDeckNode(),
              cardDisplay,
              this.getCurrentPlayerHandCardsNode(),
              {x: offset, y: offset}
            ).on('End', lang.hitch(this, function(cardNode) {
              this.placeInCurrentPlayerHand(cardNode);
              this.enablePlayCards();
            }));
          })
        );
      },

      onNotifIReturnedToDeck: function(notification) {
        var selectedIds = notification.args.cardIds;
        var handCards = this.getCurrentPlayerHandCardsNodeList();
        // Sorting makes sure positioning is correct (and don't remove earlier card first thus repositioning
        // the latter card before animating
        array.forEach(
          array.map(selectedIds, lang.hitch(this, this.getCurrentPlayerHandCardNodeByCardId))
            .sort(lang.hitch(this, function(card1, card2) {
              return handCards.indexOf(card2) - handCards.indexOf(card1);
            })),
          lang.hitch(this, function(card) {
            this.slideToDeckAndDestroy(card, this.getCurrentPlayerDeckNode());
          })
        );
      },

      onNotifReturnedToDeck: function(notification) {
        var playerId = notification.args.playerId;
        this.updateHandCount(playerId, notification.args.handCount);
        this.updateDeckCount(playerId, notification.args.deckCount);
      },

      onNotifNewScores: function(notification) {
        var scores = notification.args.scores;
        for (var playerId in scores) {
          if (scores.hasOwnProperty(playerId)) {
            this.scoreCtrl[playerId].toValue(scores[playerId]);
          }
        }
      }
    });
  }
);
