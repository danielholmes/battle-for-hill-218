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
    var CARD_WIDTH = 80;
    var CARD_HEIGHT = 112;
    var SLIDE_ANIMATION_DURATION = 700;
    
    return declare("bgagame.battleforhilldhau", ebg.core.gamegui, {
        constructor: function() {
            this.battlefieldMap = new ebg.scrollmap();
        },
        
        /**
         * Sets up the game user interface according to current game situation specified
         * in parameters. Method is called each time the game interface is displayed to a player, ie:
         *  - when the game starts
         *  - when a player refreshes the game page (F5)
         */
        setup: function(datas) {
            this.setupPlayerCards(datas.players);
            this.setupBattlefield(datas.battlefield);
            this.setupNotifications();
        },

        setupPlayerCards: function(players) {
            for (var id in players) {
                if (players.hasOwnProperty(id)) {
                    var player = players[id];
                    dojo.place(this.format_block('jstpl_counter_icons', {}), this.getPlayerBoardNode(id));
                    this.updateDeckCount(id, player.deckSize);
                    this.updateHandCount(id, player.handSize);
                    this.updateAirStrikeCount(id, player.numAirStrikes);
                    if (id.toString() === this.player_id.toString()) {
                        this.setupCurrentPlayerCards(player);
                    }
                }
            }
        },

        setupCurrentPlayerCards: function(data) {
            // Air strikes
            var airStrikeIds = array.filter(data.cards, function(card) { return card.type === 'air-strike'; })
                .map(function(card) { return card.id; });
            this.getAirStrikeDeckNodeList(this.player_id).data('ids', airStrikeIds);

            // Hand
            var handCards = array.filter(data.cards, function(card) { return card.type !== 'air-strike'; });
            array.forEach(
                array.map(
                    handCards,
                    lang.hitch(this, function(card) { return this.createCurrentPlayerHandCard(card, data.color); })
                ),
                lang.hitch(this, function(card) { this.placeInCurrentPlayerHand(card); })
            );
        },

        setupBattlefield: function(data) {
            array.forEach(data, lang.hitch(this, this.placeBattlefieldCard));

            this.battlefieldMap.create(
                $('map_container'),
                $('map_scrollable'),
                $('map_surface'),
                this.getBattlefieldNode()
            );
            this.battlefieldMap.setupOnScreenArrows(150);
            query('movetop').on('click', lang.hitch(this, this.onMoveTop));
            query('moveleft').on('click', lang.hitch(this, this.onMoveLeft));
            query('moveright').on('click', lang.hitch(this, this.onMoveRight));
            query('movedown').on('click', lang.hitch(this, this.onMoveDown));
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

            this.enableHandCardsClick(
                this.onHandCardPlayClick,
                // TODO: Change to jstpl once refactor in a way that can
                '<div>\
                    <strong>Play this card on the battlefield</strong>\
                    <div class="tooltip-card {cardType} color-{cardColor}"></div>\
                </div>',
                'Deselect this card',
                true
            );

            var _this = this;
            this.placePositionClickSignal = query(this.getBattlefieldNode()).on(
                '.battlefield-position.clickable:click',
                function() { lang.hitch(_this, _this.onPlacePositionClick)({target: this}); }
            );

            var airStrikeNode = this.getAirStrikeDeckNodeList(this.player_id);
            var airStrikeIds = airStrikeNode.data('ids')[0];
            if (airStrikeIds.length > 0) {
                airStrikeNode.addClass('clickable');
                airStrikeNode.on('click', lang.hitch(this, function() {
                    var remainingIds = this.getAirStrikeDeckNodeList(this.player_id).data('ids')[0];
                    if (remainingIds.length > 0) {
                        this.playCard(remainingIds[0]);
                    }
                }));
            }
        },

        onEnterChooseAttack: function(possiblePlacements) {
            query(this.getBattlefieldNode()).addClass('state-choose-attack');
            array.forEach(
                possiblePlacements,
                lang.hitch(this, function(possiblePlacement) {
                    this.activatePossiblePlacementPosition(possiblePlacement, 'Attack this card');
                })
            );
            
            var _this = this;
            this.attackPositionClickSignal = query(this.getBattlefieldNode()).on(
                '.battlefield-position.clickable:click',
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
            if (this.placePositionClickSignal) {
                this.placePositionClickSignal.remove();
                this.placePositionClickSignal = null;
            }
            this.deactivateAllPlacementPositions();
            this.disablePlayableCardsClick();
        },

        onLeaveChooseAttack: function() {
            if (this.attackPositionClickSignal) {
                this.attackPositionClickSignal.remove();
                this.attackPositionClickSignal = null;
            }
            query(this.getBattlefieldNode()).removeClass('state-choose-attack');
            this.deactivateAllPlacementPositions();
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function(stateName, args) {
            if (this.isCurrentPlayerActive()) {
                switch (stateName) {
                    case 'returnToDeck':
                        this.addActionButton('button_1_id', _('Return the selected cards.'), 'onSubmitReturnCards');
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// DOM Node Utility methods
        getBattlefieldNode: function() {
            return query('#map_scrollable_oversurface').pop();
        },

        getPlayerBoardNode: function(playerId) {
            return query('#player_board_' + playerId).pop();
        },

        getCurrentPlayerCardsNode: function() {
            return dom.byId('my-cards');
        },
        
        getCurrentPlayerHandCardsNodeList: function() {
            // Note: .hand-card not needed since have .hand-cards container?
            return query(this.getCurrentPlayerCardsNode()).query('.hand-card');
        },

        getCurrentPlayerHandCardsNode: function() {
            return query(this.getCurrentPlayerCardsNode()).query('.hand-cards').pop();
        },

        getCurrentPlayerHandCardNodeByCardId: function(cardId) {
            return query(this.getCurrentPlayerHandCardsNodeList()).filter('[data-id=' + cardId + ']').pop();
        },

        getCurrentPlayerPlayableCardNodeByCardId: function(cardId) {
            return this.getCurrentPlayerPlayableCardNodeList().filter('[data-id=' + cardId + ']').pop();
        },

        getPlayerDeckNode: function(playerId) {
            return query('#overall_player_board_' + playerId).query('.deck-count-icon').pop();
        },

        getAirStrikeDeckNodeList: function(playerId) {
            return query('#overall_player_board_' + playerId).query('.air-strike-count-icon');
        },

        getPlayerHandCardsIconNode: function(playerId) {
            return query('#overall_player_board_' + playerId).query('.hand-count-icon').pop();
        },

        getCurrentPlayerDeckNode: function() {
            return this.getPlayerDeckNode(this.player_id);
        },

        placeInCurrentPlayerHand: function(card) {
            dojo.place(this.recoverFromAnimation(card), this.getCurrentPlayerHandCardsNode());
        },

        getCurrentPlayerPlayableCardNodeList: function() {
            return query(this.getCurrentPlayerCardsNode()).query('.playable-card');
        },

        getCurrentPlayerSelectedCardIds: function() {
            var selectedIds = this.getCurrentPlayerPlayableCardNodeList().filter('.selected').attr('data-id');
            var airStrikeNodeList = this.getAirStrikeDeckNodeList(this.player_id);
            var airStrikeIds = airStrikeNodeList.data('ids')[0];
            if (domClass.contains(airStrikeNodeList.pop(), 'selected') && airStrikeIds.length > 0) {
                selectedIds.push(airStrikeIds[0]);
            }
            return selectedIds;
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

        createBattlefieldCard: function(card, color) {
            var coloredCard = lang.mixin({}, card, {color: color});
            return domConstruct.toDom(this.format_block('jstpl_battlefield_card', coloredCard));
        },

        placeBattlefieldCard: function(card) {
            var position = this.getOrCreatePlacementPosition(card.x, card.y);
            dojo.place(this.createBattlefieldCard(card, card.playerColor || ''), position);
        },

        updateDeckCount: function(playerId, count) {
            query(this.getPlayerBoardNode(playerId)).query('.deck-count').pop().innerHTML = count;
        },

        updateHandCount: function(playerId, count) {
            query(this.getPlayerBoardNode(playerId)).query('.hand-count').pop().innerHTML = count;
        },

        updateAirStrikeCount: function(playerId, count) {
            query(this.getPlayerBoardNode(playerId)).query('.air-strike-count').pop().innerHTML = count;
        },

        ///////////////////////////////////////////////////
        //// Animation Utility methods
        prepareForAnimation: function(node) {
            return query(node)
                .style('zIndex', 100)
                .style('position', 'absolute')
                .pop();
        },

        recoverFromAnimation: function(node) {
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
            dojo.place(newElement, query('body').pop());
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
        enableHandCardsClick: function(handler, tooltip, selectedTooltip, tooltipIsHtml) {
            var subSelector = '.hand-cards';
            var cardsContainer = this.getCurrentPlayerCardsNode();
            if (cardsContainer === null) {
                return;
            }

            var formatTooltip = function(card, tooltip) {
                return tooltip.replace('{cardColor}', query(card).attr('data-color').pop())
                    .replace('{cardType}', query(card).attr('data-type').pop());
            };
            var applyTooltip = lang.hitch(this, function(node, tooltip) {
                this.addTooltip(node.id, '', formatTooltip(node, tooltip));
            });
            if (tooltipIsHtml) {
                applyTooltip = lang.hitch(this, function(node, tooltip) {
                    this.addTooltipHtml(node.id, formatTooltip(node, tooltip));
                });
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
                    applyTooltip(cardNode, _(tooltip));
                }));

            // TODO: Work out how to do this handling properly
            var _this = this;
            this.currentPlayerCardsClickSignal = query(cardsContainer).on(
                '.playable-card:click',
                function() {
                    var cardNode = this;
                    lang.hitch(_this, function() {
                        handler({target: cardNode});
                        this.removeTooltip(cardNode.id);
                        if (domClass.contains(cardNode, 'selected')) {
                            applyTooltip(cardNode, _(selectedTooltip));
                        } else {
                            applyTooltip(cardNode, _(tooltip));
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
                        this.removeTooltip(cardNode.id);
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
        deactivateAllPlacementPositions: function(position) {
            var clickablePositions = query(this.getBattlefieldNode()).query('.clickable');
            clickablePositions.removeClass('clickable');
            clickablePositions.forEach(lang.hitch(this, function(positionNode) {
                this.removeTooltip(positionNode.id);
            }));
        },

        activatePossiblePlacementPosition: function(position, tooltip) {
            var placementNode = this.getOrCreatePlacementPosition(position.x, position.y);
            query(placementNode).addClass('clickable');
            this.addTooltip(placementNode.id, '', _(tooltip));
        },

        getOrCreatePlacementPosition: function(x, y) {
            var position = query(this.getBattlefieldNode()).query('[data-x=' + x + '][data-y=' + y + ']').pop();
            if (position) {
                return position;
            }

            var left = -CARD_WIDTH / 2 + (x * CARD_WIDTH);
            var top = -CARD_HEIGHT / 2 - (y * CARD_HEIGHT);
            position = domConstruct.toDom(this.format_block(
                'jstpl_battlefield_position',
                {x: x, y: y, top: top, left: left}
            ));
            dojo.place(position, this.getBattlefieldNode());
            return position;
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
                "/battleforhilldhau/battleforhilldhau/returnToDeck.html",
                {
                    lock: true,
                    ids: selectedIds.join(',')
                },
                this,
                function() {
                    var handCards = this.getCurrentPlayerHandCardsNodeList();
                    // Sorting makes sure positioning is correct (and don't remove earlier card first thus repositioning the
                    // latter card before animating
                    array.forEach(
                        array.map(selectedIds, lang.hitch(this, this.getCurrentPlayerHandCardNodeByCardId))
                            .sort(lang.hitch(this,
                                function(card1, card2) { return handCards.indexOf(card2) - handCards.indexOf(card1); }
                            )),
                        lang.hitch(this, function(card) {
                            this.slideToDeckAndDestroy(card, this.getCurrentPlayerDeckNode());
                            // TODO: on complete, increment deck size counter
                        })
                    );
                },
                function() {}
            );
        },

        onHandCardPlayClick: function(e) {
            var clickedCard = e.target;
            this.playCard(query(clickedCard).attr('data-id')[0]);
        },

        playCard: function(cardId) {
            this.getCurrentPlayerPlayableCardNodeList().forEach(function(card) {
                if (query(card).attr('data-id')[0] === cardId) {
                    query(card).toggleClass('selected');
                } else {
                    query(card).removeClass('selected');
                }
            });

            var airStrikeNodeList = this.getAirStrikeDeckNodeList(this.player_id);
            var airStrikeIds = airStrikeNodeList.data('ids')[0];
            if (airStrikeIds.indexOf(cardId) >= 0) {
                airStrikeNodeList.toggleClass('selected');
            } else {
                airStrikeNodeList.removeClass('selected');
            }

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
                "/battleforhilldhau/battleforhilldhau/playCard.html",
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
                "/battleforhilldhau/battleforhilldhau/chooseAttack.html",
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
            this.battlefieldMap.scroll(0, CARD_HEIGHT);
        },

        onMoveLeft: function(e) {
            e.preventDefault();
            this.battlefieldMap.scroll(CARD_WIDTH, 0);
        },

        onMoveRight: function(e) {
            e.preventDefault();
            this.battlefieldMap.scroll(-CARD_WIDTH, 0);
        },

        onMoveDown: function(e) {
            e.preventDefault();
            this.battlefieldMap.scroll(0, -CARD_HEIGHT);
        },
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications
        setupNotifications: function() {
            dojo.subscribe('returnedToDeck', lang.hitch(this, this.notif_returnedToDeck));
            dojo.subscribe('cardsDrawn', lang.hitch(this, this.notif_cardsDrawn));
            dojo.subscribe('myCardsDrawn', lang.hitch(this, this.notif_myCardsDrawn));
            dojo.subscribe('iPlacedCard', lang.hitch(this, this.notif_iPlacedCard));
            dojo.subscribe('placedCard', lang.hitch(this, this.notif_placedCard));
            dojo.subscribe('iPlayedAirStrike', lang.hitch(this, this.notif_iPlayedAirStrike));
            dojo.subscribe('playedAirStrike', lang.hitch(this, this.notif_playedAirStrike));
            dojo.subscribe('cardAttacked', lang.hitch(this, this.notif_cardAttacked));
            dojo.subscribe('newScores', lang.hitch(this, this.notif_newScores));
        },

        notif_cardAttacked: function(notification) {
            var x = notification.args.x;
            var y = notification.args.y;
            var position = this.getOrCreatePlacementPosition(x, y);
            this.fadeOutAndDestroy(query(position).query('.battlefield-card').pop());
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
            var cardNode = this.createBattlefieldCard({type: cardType}, color);
            var handCardsNode = this.getPlayerHandCardsIconNode(playerId);
            var position = this.getOrCreatePlacementPosition(x, y);

            this.slideNewElementTo(handCardsNode, cardNode, position)
                .on("End", lang.hitch(this, function() {
                    dojo.place(this.recoverFromAnimation(cardNode), position);
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
            this.updateAirStrikeCount(playerId, notification.args.count);
            if (playerId === this.player_id) {
                return;
            }

            var x = notification.args.x;
            var y = notification.args.y;
            var airStrikeDeckNode = this.getAirStrikeDeckNodeList(playerId).pop();
            var airStrikeCard = this.createHiddenPlayerAirStrikeCard(notification.args.playerColor);
            var position = this.getOrCreatePlacementPosition(x, y);

            this.slideNewElementTo(airStrikeDeckNode, airStrikeCard, position)
                .on("End", lang.hitch(this, function() {
                    dojo.destroy(airStrikeCard);
                    dojo.destroy(position);
                }));
        },

        notif_iPlayedAirStrike: function(notification) {
            var cardId = notification.args.cardId;
            var airStrikesNode = this.getAirStrikeDeckNodeList(this.player_id);
            var airStrikeIds = airStrikesNode.data('ids')[0];
            var idIndex = airStrikeIds.indexOf(cardId);
            if (idIndex >= 0) {
                airStrikeIds.splice(idIndex, 1);
                airStrikesNode.data('ids', airStrikeIds);
            }
            var x = notification.args.x;
            var y = notification.args.y;

            var position = this.getOrCreatePlacementPosition(x, y);
            var cardNode = this.createCurrentPlayerAirStrikeCard(notification.args.playerColor);
            this.slideNewElementTo(airStrikesNode.pop(), cardNode, position)
                .on('End', function() {
                    dojo.destroy(cardNode);
                    dojo.destroy(position);
                });
        },

        notif_cardsDrawn: function(notification) {
            var playerId = notification.args.playerId;
            this.updateHandCount(playerId, notification.args.handCount);
            this.updateDeckCount(playerId, notification.args.deckCount);
        },

        notif_myCardsDrawn: function(notification) {
            var playerColor = notification.args.playerColor;
            array.forEach(
                array.map(
                    notification.args.cards,
                    lang.hitch(this, function(card) {
                        return this.createCurrentPlayerHandCard(card, playerColor);
                    })
                ),
                lang.hitch(this, function (cardDisplay, i) {
                    var offset = i * 20;
                    this.slideNewElementTo(
                        this.getCurrentPlayerDeckNode(),
                        cardDisplay,
                        this.getCurrentPlayerHandCardsNode(),
                        {x: offset, y: offset}
                    ).on("End", lang.hitch(this, this.placeInCurrentPlayerHand));
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
                    var score = notification.args[playerId];
                    this.scoreCtrl[playerId].toValue(score);
                }
            }
        }
   });             
});
