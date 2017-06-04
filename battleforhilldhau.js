"use strict";

define([
    "dojo",
    "dojo/_base/declare",
    "dojo/_base/lang",
    "dojo/dom",
    "dojo/query",
    "dojo/_base/array",
    "dojo/dom-construct",
    "dojo/dom-geometry",
    "dojo/fx",
    "dojo/NodeList-data",
    "dojo/NodeList-traverse",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/scrollmap"
],
function (dojo, declare, lang, dom, query, array, domConstruct, domGeom, fx) {
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
                    if (id.toString() === this.player_id.toString()) {
                        this.setupCurrentPlayerCards(player);
                    } else {
                        this.setupHiddenPlayerCards(player);
                    }

                    var playerBoard = query('#player_board_' + id);
                    playerBoard.addClass('player-color-' + player.color);
                    dojo.place(this.format_block('jstpl_counter_icons', {}), playerBoard.pop());
                }
            }
        },

        setupCurrentPlayerCards: function(data) {
            var cardsContainer = this.getPlayerCardsNodeById(data.id);

            // Air strikes
            var airStrikeCards = array.filter(data.cards, function(card) { return card.type === 'air-strike'; });
            array.forEach(airStrikeCards, lang.hitch(this, function(card) {
                dojo.place(
                    this.createCurrentPlayerAirStrikeCard(card, data.color),
                    query(cardsContainer).query('.air-strikes').pop()
                );
            }));

            // Hand
            var handCards = array.filter(data.cards, function(card) { return card.type !== 'air-strike'; });
            array.forEach(
                array.map(
                    handCards,
                    lang.hitch(this, function(card) { return this.createCurrentPlayerHandCard(card, data.color); })
                ),
                lang.hitch(this, function(card) { this.placeInPlayerHand(data.id, card); })
            );
        },

        setupHiddenPlayerCards: function(data) {
            var cardsContainer = this.getPlayerCardsNodeById(data.id);

            // Air strikes
            for (var i = 0; i < data.numAirStrikes; i++) {
                dojo.place(
                    this.createHiddenPlayerAirStrikeCard({type: 'air-strike'}, data.color),
                    query(cardsContainer).query('.air-strikes').pop()
                );
            }

            // Hand
            for (var j = 0; j < data.numCards - data.numAirStrikes; j++) {
                this.placeInPlayerHand(data.id, this.createHiddenPlayerHandCard({type: 'back'}, data.color));
            }
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
            }
        },

        onEnterReturnToDeck: function() {
            this.enableHandCardsClick(this.onHandCardReturnClick);
        },

        onEnterPlayCard: function(possiblePlacementsByCardId) {
            console.log('onEnterPlayCard', possiblePlacementsByCardId);
            this.possiblePlacementsByCardId = possiblePlacementsByCardId;
            this.enablePlayableCardsClick(this.onHandCardPlayClick);

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
            }
        },

        onLeavePlayCard: function() {
            if (this.attackPositionClickSignal) {
                this.attackPositionClickSignal.remove();
                this.attackPositionClickSignal = null;
            }
            this.deactivateAllPlacementPositions();
            this.disablePlayableCardsClick();
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

        getPlayerCardsNodeById: function(id) {
            return dom.byId('player-cards-' + id);
        },

        getCurrentPlayerCardsNode: function() {
            return this.getPlayerCardsNodeById(this.player_id);
        },
        
        getPlayerHandCardsNodeList: function(playerId) {
            // Note: .hand-card not needed since have .hand-cards container?
            return query(this.getPlayerCardsNodeById(playerId)).query('.hand-card');
        },
        
        getCurrentPlayerHandCardsNodeList: function() {
            return this.getPlayerHandCardsNodeList(this.player_id);
        },

        getPlayerHandCardsNodeById: function(playerId) {
            return query(this.getPlayerCardsNodeById(playerId)).query('.hand-cards').pop();
        },

        getCurrentPlayerHandCardsNode: function() {
            return this.getPlayerHandCardsNodeById(this.player_id);
        },

        getCurrentPlayerHandCardNodeByCardId: function(cardId) {
            return query(this.getCurrentPlayerHandCardsNodeList()).filter('[data-id=' + cardId + ']').pop();
        },

        getPlayerDeckNodeById: function(id) {
            return query('#overall_player_board_' + id).query('.deck-icon').pop();
        },

        getCurrentPlayerDeckNode: function() {
            return this.getPlayerDeckNodeById(this.player_id);
        },

        placeInPlayerHand: function(playerId, card) {
            dojo.place(this.recoverFromAnimation(card), this.getPlayerHandCardsNodeById(playerId));
        },

        placeInCurrentPlayerHand: function(card) {
            this.placeInPlayerHand(this.player_id, card);
        },

        getCurrentPlayerSelectedPlayableCardNodeList: function() {
            return query(this.getCurrentPlayerCardsNode()).query('.playable-card.selected');
        },

        createHiddenPlayerAirStrikeCard: function(card, color) {
            var coloredCard = lang.mixin({}, card, {color: color});
            return domConstruct.toDom(this.format_block('jstpl_opponent_air_strike_card', coloredCard));
        },

        createCurrentPlayerAirStrikeCard: function(card, color) {
            var coloredCard = lang.mixin({}, card, {color: color});
            return domConstruct.toDom(this.format_block('jstpl_air_strike_card', coloredCard));
        },

        createCurrentPlayerHandCard: function(card, color) {
            var coloredCard = lang.mixin({}, card, {color: color});
            return domConstruct.toDom(this.format_block('jstpl_hand_card', coloredCard));
        },

        createHiddenPlayerHandCard: function(card, color) {
            var coloredCard = lang.mixin({}, card, {color: color});
            return domConstruct.toDom(this.format_block('jstpl_opponent_hand_card', coloredCard));
        },

        createBattlefieldCard: function(card, color) {
            var coloredCard = lang.mixin({}, card, {color: color});
            return domConstruct.toDom(this.format_block('jstpl_battlefield_card', coloredCard));
        },

        placeBattlefieldCard: function(card) {
            var position = this.getOrCreatePlacementPosition(card.x, card.y);
            dojo.place(this.createBattlefieldCard(card, card.playerColor || ''), position);
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
        enableHandCardsClick: function(handler) {
            this.enableCardsClick(handler, '.hand-cards');
        },

        enablePlayableCardsClick: function(handler) {
            this.enableCardsClick(handler);
        },

        enableCardsClick: function(handler, subSelector) {
            var cardsContainer = this.getCurrentPlayerCardsNode();
            if (cardsContainer === null) {
                return;
            }

            this.disablePlayableCardsClick();

            var clickableContainer = query(cardsContainer);
            if (subSelector) {
                clickableContainer = clickableContainer.query(subSelector);
            }
            clickableContainer.addClass('clickable');

            // TODO: Work out how to do this handling properly
            var _this = this;
            this.currentPlayerCardsClickSignal = query(cardsContainer).on(
                '.playable-card:click',
                function() { lang.hitch(_this, handler)({target: this}); }
            );
        },

        removeClickableOnNodeAndChildren: function(node) {
            var nodeList = query(node);
            nodeList.removeClass('clickable');
            array.forEach(nodeList.children(), lang.hitch(this, this.removeClickableOnNodeAndChildren));
        },

        disablePlayableCardsClick: function() {
            var cardsContainer = this.getCurrentPlayerCardsNode();
            if (cardsContainer !== null) {
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
            query(this.getBattlefieldNode()).query('.clickable').removeClass('clickable');
        },

        activatePossiblePlacementPosition: function(position) {
            query(this.getOrCreatePlacementPosition(position.x, position.y)).addClass('clickable');
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

            var selectedIds = this.getCurrentPlayerSelectedPlayableCardNodeList().attr('data-id');
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
            this.getCurrentPlayerSelectedPlayableCardNodeList().forEach(function(card) {
                if (card !== clickedCard) {
                    query(card).removeClass('selected');
                }
            });
            query(clickedCard).toggleClass('selected');

            this.deactivateAllPlacementPositions();
            var selectedIds = this.getCurrentPlayerSelectedPlayableCardNodeList().attr('data-id');
            if (selectedIds.length === 0) {
                return;
            }

            var cardId = selectedIds.pop();
            var possiblePlacements = this.possiblePlacementsByCardId[cardId];
            console.log('onHandCardPlayClick', cardId, possiblePlacements);
            array.forEach(possiblePlacements, lang.hitch(this, this.activatePossiblePlacementPosition));
        },

        onAttackPositionClick: function(e) {
            if (!this.checkAction('playCard')) {
                return;
            }

            var id = this.getCurrentPlayerSelectedPlayableCardNodeList().attr('data-id').pop();
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
        },

        notif_placedCard: function(notification) {
            var playerId = notification.args.playerId;
            if (playerId === this.player_id) {
                return;
            }

            var x = notification.args.x;
            var y = notification.args.y;
            var cardType = notification.args.typeKey;
            var color = notification.args.playerColor;
            var cardNode = this.createBattlefieldCard({type: cardType}, color);
            var handCardsNode = this.getPlayerHandCardsNodeById(playerId);
            var position = this.getOrCreatePlacementPosition(x, y);

            dojo.destroy(this.getPlayerHandCardsNodeList(playerId).pop());
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

        notif_cardsDrawn: function(notification) {
            var playerId = notification.args.playerId;
            if (playerId === this.player_id) {
                return;
            }

            var playerColor = notification.args.playerColor;
            var numCards = notification.args.numCards;
            array.forEach(
                array.map(
                    new Array(numCards),
                    lang.hitch(this, function() {
                        return this.createHiddenPlayerHandCard({type: 'back'}, playerColor);
                    })
                ),
                lang.hitch(this, function (cardDisplay, i) {
                    var offset = i * 20;
                    this.slideNewElementTo(
                        this.getPlayerDeckNodeById(playerId),
                        cardDisplay,
                        this.getPlayerHandCardsNodeById(playerId),
                        {x: offset, y: offset}
                    ).on("End", lang.hitch(this, function(card) { this.placeInPlayerHand(playerId, card); }));
                })
            );
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
            if (playerId === this.player_id) {
                return;
            }

            var numCards = notification.args.numCards;
            // Just use random cards - doesn't matter which one actually moved
            var handCards = this.getPlayerHandCardsNodeList(playerId);
            var cardNodesToMove = [];
            while (cardNodesToMove.length < numCards) {
                var proposedCard = handCards[Math.floor(Math.random() * handCards.length)];
                if (cardNodesToMove.indexOf(proposedCard) === -1) {
                    cardNodesToMove.push(proposedCard);
                }
            }
            array.forEach(
                cardNodesToMove.sort(lang.hitch(this,
                    function(card1, card2) { return handCards.indexOf(card2) - handCards.indexOf(card1); }
                )),
                lang.hitch(
                    this,
                    function(card) { this.slideToDeckAndDestroy(card, this.getPlayerDeckNodeById(playerId)); }
                )
            );
        }
   });             
});
