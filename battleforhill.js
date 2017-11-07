"use strict";

define([
    "dojo",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom",
    "dojo/query",
    "dojo/_base/array",
    "dojo/dom-construct",
    "dojo/dom-class",
    "dojo/dom-geometry",
    "dojo/fx",
    "dojo/NodeList-data",
    "dojo/NodeList-traverse",
    "dojo/NodeList-html",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/scrollmap"
],
function (dojo, declare, lang, dom, query, array, domConstruct, domClass, domGeom, fx) {
    // Should be the same dimensions as battlefield-card in css
    var CARD_WIDTH = 160;
    var CARD_HEIGHT = 230;
    var SLIDE_ANIMATION_DURATION = 700;
    var MAX_ZOOM = 10;
    var MIN_ZOOM = 2;
    
    return declare("bgagame.battleforhill", ebg.core.gamegui, {
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
                    domConstruct.place(this.format_block('jstpl_counter_icons', {playerId: id}), this.getPlayerBoardNode(id));
                    this.addTooltip('deck-count-' + id, _('Number of cards left in deck'), '');
                    this.addTooltip('hand-count-' + id, _('Number of battlefield cards in hand'), '');
                    this.updatePlayerNumber(id, player.number);
                    this.setAirStrikeTooltipToDefault(id);
                    this.updateDeckCount(id, player.deckSize);
                    this.updateHandCount(id, player.handSize);
                    this.updateScoreAuxCount(id, player.scoreAux);

                    if (id.toString() === this.player_id.toString()) {
                        var airStrikeCards = array.filter(
                            player.cards,
                            function(card) { return card.type === 'air-strike'; }
                        );
                        this.setupAirStrikeIcons(id, airStrikeCards);
                        this.setupCurrentPlayerCards(player);
                    } else {
                        var dummyAirStrikeCards = array.map(
                            new Array(player.numAirStrikes),
                            function(_, i) { return {id: i}; }
                        );
                        this.setupAirStrikeIcons(id, dummyAirStrikeCards);
                    }
                }
            }
        },

        setupAirStrikeIcons: function(playerId, cards) {
            var airStrikeNodes = array.map(
                cards,
                lang.hitch(this, function(card) {
                    return this.format_block('jstpl_air_strike_icon', Object.assign({playerId: playerId}, card));
                })
            );
            var playerIcons = query(this.getPlayerBoardNode(playerId)).query('.player-icons').pop();
            array.forEach(
                airStrikeNodes,
                lang.hitch(this, function(node) { domConstruct.place(node, playerIcons, 2); })
            );
        },

        setupCurrentPlayerCards: function(data) {
            var handCards = array.filter(data.cards, function(card) { return card.type !== 'air-strike'; });
            var handCardNodes = array.map(
                handCards,
                lang.hitch(this, function(card) { return this.createCurrentPlayerHandCard(card, data.color); })
            );
            array.forEach(
                handCardNodes,
                lang.hitch(this, function(card) { this.placeInCurrentPlayerHand(card); })
            );
            array.forEach(
                handCardNodes,
                lang.hitch(this, function(card) { this.updatePlayableCardTooltip(card); })
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

        ///////////////////////////////////////////////////
        //// Game & client states
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
            this.enableHandCardsClick(this.onHandCardReturnClick, 'Return this card', 'Don\'t return this card');
        },

        onEnterPlayCard: function(possiblePlacementsByCardId) {
            this.possiblePlacementsByCardId = possiblePlacementsByCardId;

            this.enablePlayHandCards();

            var _this = this;
            this.placeButtonClickSignal = query(this.getBattlefieldInteractionNode()).on(
                '.battlefield-button.clickable:click',
                function() { lang.hitch(_this, _this.onPlacePositionClick)({target: this}); }
            );

            this.setAirStrikeTooltip(this.player_id, null, _('Play Air Strike card'));
            var airStrikeNodes = this.getCurrentPlayerAirStrikesNodeList().addClass('clickable');
            this.placeAirStrikeClickSignal = airStrikeNodes.on('click', lang.hitch(this, function(e) {
                this.playCard(query(e.currentTarget).attr('data-id')[0]);
            }));
        },

        onEnterChooseAttack: function(possiblePlacements) {
            query(this.getBattlefieldInteractionNode()).addClass('state-choose-attack');
            array.forEach(
                possiblePlacements,
                lang.hitch(this, function(possiblePlacement) {
                    this.activatePossiblePlacementPosition(possiblePlacement, 'Attack this card');
                })
            );
            
            var _this = this;
            this.attackPositionClickSignal = query(this.getBattlefieldInteractionNode()).on(
                '.battlefield-button.clickable:click',
                function() { lang.hitch(_this, _this.onAttackPositionClick)({target: this}); }
            );
        },

        ///////////////////////////////////////////////////
        // onLeavingState: this method is called each time we are leaving a game state.
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
            this.disableAirStrikesClick();
            this.setAirStrikeTooltipToDefault(this.player_id);
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
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function(stateName, args) {
            if (this.isCurrentPlayerActive()) {
                switch (stateName) {
                    case 'returnToDeck':
                        this.addActionButton('button_1_id', _('Return the selected cards'), 'onSubmitReturnCards');
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// DOM Node Utility methods
        getBattlefieldNode: function() {
            return query('#map_scrollable').query('.zoomable').pop();
        },

        getBattlefieldInteractionNode: function() {
            return query('#map_scrollable_oversurface').query('.zoomable').pop();
        },

        getPlayerBoardNode: function(playerId) {
            return query('#player_board_' + playerId).pop();
        },

        getAirStrikesNodeList: function(playerId) {
            return query(this.getPlayerBoardNode(playerId)).query('.air-strike-icon');
        },

        getCurrentPlayerAirStrikesNodeList: function() {
            return this.getAirStrikesNodeList(this.player_id);
        },

        getCurrentPlayerCardsNode: function() {
            return dom.byId('players-panel');
        },
        
        getCurrentPlayerHandCardsNodeList: function() {
            // Note: .hand-card not needed since have .hand-cards container?
            return query(this.getCurrentPlayerCardsNode()).query('.hand-card');
        },

        getCurrentPlayerHandCardsNode: function() {
            return query(this.getCurrentPlayerCardsNode()).query('.player-cards').pop();
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

        placeInCurrentPlayerHand: function(card) {
            domConstruct.place(this.recoverFromAnimation(card), this.getCurrentPlayerHandCardsNode());
        },

        getCurrentPlayerPlayableCardNodeList: function() {
            return query(this.getCurrentPlayerCardsNode()).query('.playable-card');
        },

        getCurrentPlayerSelectedCardIds: function() {
            var cardIds = this.getCurrentPlayerPlayableCardNodeList().filter('.selected').attr('data-id');
            var airStrikeSelectedIds = this.getCurrentPlayerAirStrikesNodeList().filter('.selected').attr('data-id');
            return cardIds.concat(airStrikeSelectedIds);
        },

        createHiddenPlayerAirStrikeCard: function(color) {
            var card = {type: 'air-strike', color: color};
            return domConstruct.toDom(this.format_block('jstpl_opponent_air_strike_card', card));
        },

        createCurrentPlayerAirStrikeCard: function(color) {
            var card = {type: 'air-strike', color: color};
            return domConstruct.toDom(this.format_block('jstpl_air_strike_card', card));
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
            var namePlural = name;
            if (namePlural.lastIndexOf('s') === namePlural.length - 1) {
                namePlural += "'";
            } else {
                namePlural += "'s";
            }
            return domConstruct.toDom(this.format_block('jstpl_base_indicator', {namePlural: namePlural}));
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

        updateScoreAuxCount: function(playerId, count) {
            this.updateCounter('.score-aux', playerId, count);
        },

        updateCounter: function(counterSelector, playerId, count) {
            query(this.getPlayerBoardNode(playerId))
                .query(counterSelector)
                .query('.counter-text')
                .pop().innerHTML = count;
        },

        setAirStrikeTooltipToDefault: function(playerId) {
            this.setAirStrikeTooltip(playerId, _('Number of air strike cards left in hand'));
        },

        setAirStrikeTooltip: function(playerId, tooltip, action) {
            if (!action) {
                action = '';
            }
            if (!tooltip) {
                tooltip = '';
            }
            array.forEach(
                this.getAirStrikesNodeList(playerId),
                lang.hitch(
                    this,
                    function (airStrikeIcon) {
                        this.addTooltip(airStrikeIcon.id, tooltip, action);
                    }
                )
            );
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

        enablePlayHandCards: function() {
            this.enableHandCardsClick(
                this.onHandCardPlayClick,
                'Play this card on the battlefield',
                'Deselect this card'
            );
        },

        updateUi: function() {
            var screenHeight = window.innerHeight
                || document.documentElement.clientHeight
                || document.body.clientHeight;

            var containerPos = dojo.position('battlefield-panel');

            screenHeight /= this.gameinterface_zoomFactor;

            var mapHeight = Math.max(500, screenHeight - containerPos.y - 30);
            dojo.style('battlefield-panel', 'height', mapHeight + 'px');
        },

        ///////////////////////////////////////////////////
        //// Animation Utility methods
        prepareForAnimation: function(node) {
            if (!node) {
                throw new Error("Must provide a node");
            }
            return query(node)
                .style('zIndex', 100)
                .style('position', 'absolute')
                .pop();
        },

        recoverFromAnimation: function(node) {
            if (!node) {
                throw new Error("Must provide a node");
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
                offset = {x: 0, y:0};
            }
            this.prepareForAnimation(newElement);
            domConstruct.place(newElement, query('body').pop());
            this.placeOnObject(newElement, from);
            var targetPosition = this.getCentredPosition(newElement, target);
            return fx.slideTo({
                node: newElement,
                left: targetPosition.x + offset.x,
                top: targetPosition.y + offset.y,
                units: "px",
                duration: SLIDE_ANIMATION_DURATION
            }).play();
        },

        ///////////////////////////////////////////////////
        // Interaction utility methods
        enableHandCardsClick: function(handler, tooltip, selectedTooltip) {
            var subSelector = '.player-cards';
            var cardsContainer = this.getCurrentPlayerCardsNode();
            if (cardsContainer === null) {
                return;
            }

            handler = lang.hitch(this, handler);
            if (!selectedTooltip) {
                selectedTooltip = tooltip;
            }

            this.disablePlayableCardsClick();

            var clickableContainer = query(cardsContainer);
            if (subSelector) {
                clickableContainer = clickableContainer.query(subSelector);
            }
            clickableContainer.addClass('clickable');

            clickableContainer.query('.playable-card')
                .forEach(lang.hitch(this, function(cardNode) {
                    if (!cardNode.id) {
                        console.error('Trying to add tooltip to node without id', cardNode);
                    }
                    this.updatePlayableCardTooltip(cardNode, tooltip);
                }));

            // TODO: Work out how to do this handling properly
            var _this = this;
            this.currentPlayerCardsClickSignal = query(cardsContainer).on(
                '.playable-card:click',
                function() {
                    var cardNode = this;
                    lang.hitch(_this, function() {
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

        disableAirStrikesClick: function() {
            this.getCurrentPlayerAirStrikesNodeList().removeClass('selected').removeClass('clickable');
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

        ///////////////////////////////////////////////////
        //// Battlefield utility methods
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
            this.addTooltip(buttonNode.id, '', _(tooltip));
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

            var left = -CARD_WIDTH / 2 + (x * CARD_WIDTH);
            var top = -CARD_HEIGHT / 2 - (y * CARD_HEIGHT);
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

        ///////////////////////////////////////////////////
        //// Player's action
        onHandCardReturnClick: function(e) {
            query(e.target).toggleClass('selected');
        },

        onSubmitReturnCards: function() {
            if (!this.checkAction('returnToDeck')) {
                return;
            }

            var selectedIds = this.getCurrentPlayerSelectedCardIds();
            // TODO: Where should this business logic go?
            if (selectedIds.length !== 2) {
                this.showMessage( _('You must select exactly 2 cards to return'), 'error');
                return;
            }

            this.disablePlayableCardsClick();
            this.ajaxcall(
                "/battleforhill/battleforhill/returnToDeck.html",
                {
                    lock: true,
                    ids: selectedIds.join(',')
                },
                function() {},
                function() {}
            );
        },

        onHandCardPlayClick: function(e) {
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

            this.getCurrentPlayerAirStrikesNodeList().forEach(function(card) {
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
                    this.activatePossiblePlacementPosition(possiblePlacement, 'Place card here');
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
            this.ajaxcall(
                "/battleforhill/battleforhill/playCard.html",
                {
                    lock: true,
                    id: id,
                    x: x,
                    y: y
                },
                function() {},
                function() {}
            );
        },

        onAttackPositionClick: function(e) {
            if (!this.checkAction('chooseAttack')) {
                return;
            }

            var position = query(e.target);
            var x = position.attr('data-x').pop();
            var y = position.attr('data-y').pop();
            this.ajaxcall(
                "/battleforhill/battleforhill/chooseAttack.html",
                {
                    lock: true,
                    x: x,
                    y: y
                },
                function() {},
                function() {}
            );
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
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications
        setupNotifications: function() {
            dojo.subscribe('iReturnedToDeck', lang.hitch(this, this.notif_iReturnedToDeck));
            dojo.subscribe('returnedToDeck', lang.hitch(this, this.notif_returnedToDeck));
            dojo.subscribe('cardsDrawn', lang.hitch(this, this.notif_cardsDrawn));
            dojo.subscribe('myCardsDrawn', lang.hitch(this, this.notif_myCardsDrawn));
            dojo.subscribe('iPlacedCard', lang.hitch(this, this.notif_iPlacedCard));
            dojo.subscribe('placedCard', lang.hitch(this, this.notif_placedCard));
            dojo.subscribe('iPlayedAirStrike', lang.hitch(this, this.notif_iPlayedAirStrike));
            dojo.subscribe('playedAirStrike', lang.hitch(this, this.notif_playedAirStrike));
            dojo.subscribe('cardAttacked', lang.hitch(this, this.notif_cardAttacked));
            dojo.subscribe('newScores', lang.hitch(this, this.notif_newScores));
            dojo.subscribe('endOfGame', this, function() {});
            // Delay end of game for interface stock stability before switching to game result
            this.notifqueue.setSynchronous('endOfGame', 2000);
        },

        notif_cardAttacked: function(notification) {
            var x = notification.args.x;
            var y = notification.args.y;
            var position = this.getOrCreatePlacementPosition(x, y);
            this.explodeCard(position);
        },

        notif_placedCard: function(notification) {
            var playerId = notification.args.playerId;
            this.updateHandCount(playerId, notification.args.handCount);
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
                .on("End", lang.hitch(this, function() {
                    dojo.destroy(cardNode);
                    this.placeBattlefieldCard({type: cardType, playerColor: color, x: x, y: y});
                }));
        },

        notif_iPlacedCard: function(notification) {
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

        notif_playedAirStrike: function(notification) {
            var playerId = notification.args.playerId;
            if (playerId === this.player_id) {
                return;
            }

            var x = notification.args.x;
            var y = notification.args.y;
            var airStrikeNode = this.getAirStrikesNodeList(playerId).pop();
            var airStrikeCard = this.createHiddenPlayerAirStrikeCard(notification.args.playerColor);
            var position = this.getOrCreatePlacementPosition(x, y);

            this.slideNewElementTo(airStrikeNode, airStrikeCard, position)
                .on('End', lang.hitch(this, function() {
                    dojo.destroy(airStrikeCard);
                    this.explodeCard(position);
                }));
            dojo.destroy(airStrikeNode);
        },

        notif_iPlayedAirStrike: function(notification) {
            var airStrikesNode = this.getCurrentPlayerAirStrikesNodeList()
                .filter('[data-id=' + notification.args.cardId + ']').pop();

            var x = notification.args.x;
            var y = notification.args.y;

            var position = this.getOrCreatePlacementPosition(x, y);
            var cardNode = this.createCurrentPlayerAirStrikeCard(notification.args.playerColor);
            this.slideNewElementTo(airStrikesNode, cardNode, position)
                .on('End', lang.hitch(this, function() {
                    dojo.destroy(cardNode);
                    this.explodeCard(position);
                }));
            dojo.destroy(airStrikesNode);
        },

        notif_cardsDrawn: function(notification) {
            var playerId = notification.args.playerId;
            this.updateHandCount(playerId, notification.args.handCount);
            this.updateDeckCount(playerId, notification.args.deckCount);
        },

        notif_myCardsDrawn: function(notification) {
            var playerColor = notification.args.playerColor;
            var cardNodes = array.map(
                notification.args.cards,
                lang.hitch(this, function(card) {
                    return this.createCurrentPlayerHandCard(card, playerColor);
                })
            );
            array.forEach(
                cardNodes,
                lang.hitch(this, function (cardDisplay, i) {
                    var offset = i * 20;
                    this.slideNewElementTo(
                        this.getCurrentPlayerDeckNode(),
                        cardDisplay,
                        this.getCurrentPlayerHandCardsNode(),
                        {x: offset, y: offset}
                    ).on('End', lang.hitch(this, function(cardNode) {
                        this.placeInCurrentPlayerHand(cardNode);
                        this.enablePlayHandCards();
                    }));
                })
            );
        },

        notif_iReturnedToDeck: function(notification) {
            var selectedIds = notification.args.cardIds;
            var handCards = this.getCurrentPlayerHandCardsNodeList();
            // Sorting makes sure positioning is correct (and don't remove earlier card first thus repositioning
            // the latter card before animating
            array.forEach(
                array.map(selectedIds, lang.hitch(this, this.getCurrentPlayerHandCardNodeByCardId))
                    .sort(lang.hitch(this,
                        function(card1, card2) { return handCards.indexOf(card2) - handCards.indexOf(card1); }
                    )),
                lang.hitch(this, function(card) {
                    this.slideToDeckAndDestroy(card, this.getCurrentPlayerDeckNode());
                })
            );
        },

        notif_returnedToDeck: function(notification) {
            var playerId = notification.args.playerId;
            this.updateHandCount(playerId, notification.args.handCount);
            this.updateDeckCount(playerId, notification.args.deckCount);
        },

        notif_newScores: function(notification) {
            for (var playerId in notification.args) {
                if (notification.args.hasOwnProperty(playerId)) {
                    var score = notification.args[playerId].score;
                    var scoreAux = notification.args[playerId].scoreAux;
                    this.scoreCtrl[playerId].toValue(score);
                    this.updateScoreAuxCount(playerId, scoreAux);
                }
            }
        }
   });             
});
