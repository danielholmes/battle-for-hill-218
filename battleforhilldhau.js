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

            var playerBoard = $('player_board_' + this.player_id);
            dojo.place(this.format_block('jstpl_deck_icon', {}), playerBoard);
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
            array.forEach(data, lang.hitch(this, function(card) {
                dojo.place(this.createBattlefieldCard(card, ''), 'map_scrollable_oversurface');
            }));

            this.battlefieldMap.create(
                $('map_container'),
                $('map_scrollable'),
                $('map_surface'),
                $('map_scrollable_oversurface')
            );
            this.battlefieldMap.setupOnScreenArrows(150);
            query('movetop').on('click', lang.hitch(this, this.onMoveTop));
            query('moveleft').on('click', lang.hitch(this, this.onMoveLeft));
            query('moveright').on('click', lang.hitch(this, this.onMoveRight));
            query('movedown').on('click', lang.hitch(this, this.onMoveDown));
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function(stateName, event) {
            console.log('Entering state', stateName, event);
            switch (stateName) {
                case 'returnToDeck':
                    this.onEnterReturnToDeck();
                    break;
                case 'playCard':
                    this.onEnterPlayCard(event.args);
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
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function(stateName) {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
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
        getPlayerCardsNodeById: function(id) {
            return dom.byId('player-cards-' + id);
        },

        getCurrentPlayerCardsNode: function() {
            return this.getPlayerCardsNodeById(this.player_id);
        },
        
        getPlayerHandCardsNodeList: function(id) {
            // Note: .hand-card not needed since have .hand-cards container?
            return query(this.getPlayerCardsNodeById(id)).query('.hand-card');
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
            return query('#overall_player_board_' + id).pop();
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
            var left = -CARD_WIDTH / 2 + (card.x * CARD_WIDTH);
            var top = -CARD_HEIGHT / 2 + (card.y * CARD_HEIGHT);
            var coloredCard = lang.mixin({}, card, {color: color, left: left, top: top});
            return domConstruct.toDom(this.format_block('jstpl_battlefield_card', coloredCard));
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

        slideNewElementTo: function(deckNode, card, target, offset) {
            if (!offset) {
                offset = {x: 0, y:0};
            }
            this.prepareForAnimation(card);
            dojo.place(card, query('body').pop());
            this.placeOnObject(card, deckNode);
            var targetPosition = this.getCentredPosition(card, target);
            return fx.slideTo({
                node: card,
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

            this.ajaxcall(
                "/battleforhilldhau/battleforhilldhau/returnToDeck.html",
                {
                    lock: true,
                    ids: selectedIds.join(',')
                },
                function(result) { },
                function(isError) { }
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

            // TODO: Remove all clickable positions on battlefield
            var selectedIds = this.getCurrentPlayerSelectedPlayableCardNodeList().attr('data-id');
            console.log(this.possiblePlacementsByCardId);
            for (var i in selectedIds) {
                var selectedId = selectedIds[i];
                console.log('selected', selectedId, this.possiblePlacementsByCardId[selectedId]);
            }
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
            dojo.subscribe('hiddenPlayerReturnedToDeck', lang.hitch(this, this.notif_hiddenPlayerReturnedToDeck));

            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
        },

        notif_returnedToDeck: function(notification) {
            // Return old cards to deck
            // Sorting makes sure positioning is correct (and don't remove earlier card first thus repositioning the
            // latter card before animating
            this.disablePlayableCardsClick();
            var handCards = this.getCurrentPlayerHandCardsNodeList();
            array.forEach(
                array.map(notification.args.oldIds, lang.hitch(this, this.getCurrentPlayerHandCardNodeByCardId))
                    .sort(lang.hitch(this,
                        function(card1, card2) { return handCards.indexOf(card2) - handCards.indexOf(card1); }
                    )),
                lang.hitch(this, function(card) { this.slideToDeckAndDestroy(card, this.getCurrentPlayerDeckNode()); })
            );

            // Take card from deck then slide to hand
            var playerColor = notification.args.playerColor;
            setTimeout(
                lang.hitch(this, function() {
                    array.forEach(
                        array.map(
                            notification.args.replacements,
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
                }),
                SLIDE_ANIMATION_DURATION
            );
        },

        notif_hiddenPlayerReturnedToDeck: function(notification) {
            var playerId = notification.args.playerId;
            // Wait for full data in specific notification
            if (playerId === this.player_id) {
                return;
            }

            var numCards = notification.args.numCards;
            var playerColor = notification.args.playerColor;
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
                lang.hitch(this, function(card) { this.slideToDeckAndDestroy(card, this.getPlayerDeckNodeById(playerId)); })
            );

            // Take card from deck then slide to hand
            var newCards = [];
            for (var i = 0; i < numCards; i++) {
                newCards.push(this.createHiddenPlayerHandCard({type: 'back'}, playerColor));
            }
            setTimeout(
                lang.hitch(this, function() {
                    array.forEach(
                        newCards,
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
                }),
                SLIDE_ANIMATION_DURATION
            );
        }
   });             
});
