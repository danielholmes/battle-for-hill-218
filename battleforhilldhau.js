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
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/scrollmap"
],
function (dojo, declare, lang, dom, query, array, domConstruct, domGeom, fx) {
    var CARD_WIDTH = 80;
    var CARD_HEIGHT = 112;
    var SLIDE_ANIMATION_DURATION = 1000;
    
    return declare("bgagame.battleforhilldhau", ebg.core.gamegui, {
        constructor: function() {
            this.battlefieldMap = new ebg.scrollmap();
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        setup: function(datas) {
            this.setupMyHand(datas.me);
            this.setupOpponentHand(datas.opponent);
            this.setupBattlefield(datas.battlefield, datas.me.color);
            this.setupNotifications();
        },

        setupMyHand: function(data) {
            this.myColor = data.color;

            var airStrikeCards = array.filter(data.hand, function(card) { return card.type === 'air-strike'; });
            array.forEach(airStrikeCards, lang.hitch(this, function(card) {
                dojo.place(this.createMyAirStrikeCard(card), query('#my-hand .air-strikes').pop());
            }));

            var handCards = array.filter(data.hand, function(card) { return card.type !== 'air-strike'; });
            array.forEach(
                array.map(handCards, lang.hitch(this, this.createMyHandCard)),
                lang.hitch(this, this.placeInMyHand)
            );
        },

        setupOpponentHand: function(data) {
            this.opponentColor = data.color;

            for (var i = 0; i < data.numAirStrikes; i++) {
                dojo.place(
                    this.createOpponentAirStrikeCard({type: 'air-strike'}),
                    query('#opponent-hand .air-strikes').pop()
                );
            }
            for (var j = 0; j < data.handSize - data.numAirStrikes; j++) {
                dojo.place(
                    this.createOpponentHandCard({type: 'back'}),
                    query('#opponent-hand .hand-cards').pop()
                );
            }
        },

        setupBattlefield: function(data, viewingPlayerColor) {
            query('#battlefield-panel').addClass('viewing-player-color-' + viewingPlayerColor);
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
        onLeavingState: function( stateName )
        {
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
                        this.addActionButton('button_1_id', _('Return cards to Deck'), 'onSubmitReturnCards');
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// DOM Node Utility methods
        getOpponentDeckNode: function() {
            return query('#player_boards > .player-board:not(.current-player-board)').pop();
        },

        getOpponentHandCardsContainerNode: function() {
            return query('#opponent-hand .hand-cards').pop();
        },

        getOpponentHandCardsNodes: function() {
            return query(this.getOpponentHandCardsContainerNode()).query('.hand-card');
        },

        placeInOpponentHand: function(card) {
            dojo.place(this.recoverFromAnimation(card), this.getOpponentHandCardsContainerNode())
        },

        getMyDeckNode: function() {
            return query('#player_boards > .player-board.current-player-board').pop();
        },

        getMyHandCardsContainerNode: function() {
            return query('#my-hand .hand-cards').pop();
        },

        getMyHandCardsNodes: function() {
            return query(this.getMyHandCardsContainerNode()).query('.hand-card');
        },

        getMyPlayableCardsNodes: function() {
            return query('#my-hand .playable-card');
        },

        getMyHandCardNodeById: function(cardId) {
            return query(this.getMyHandCardsNodes()).filter('[data-id=' + cardId + ']').pop();
        },

        placeInMyHand: function(card) {
            dojo.place(this.recoverFromAnimation(card), this.getMyHandCardsContainerNode())
        },

        getMySelectedPlayableCards: function() {
            return this.getMyPlayableCardsNodes().filter('.selected');
        },

        createMyHandCard: function(card) {
            var coloredCard = lang.mixin({}, card, {color: this.myColor});
            return domConstruct.toDom(this.format_block('jstpl_hand_card', coloredCard));
        },
        
        createMyAirStrikeCard: function(card) {
            var coloredCard = lang.mixin({}, card, {color: this.myColor});
            return domConstruct.toDom(this.format_block('jstpl_air_strike_card', coloredCard));
        },

        createOpponentHandCard: function(card) {
            var coloredCard = lang.mixin({}, card, {color: this.opponentColor});
            return domConstruct.toDom(this.format_block('jstpl_opponent_hand_card', coloredCard));
        },

        createOpponentAirStrikeCard: function(card) {
            var coloredCard = lang.mixin({}, card, {color: this.opponentColor});
            return domConstruct.toDom(this.format_block('jstpl_opponent_air_strike_card', coloredCard));
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
            this.enableCardsClick(this.getMyHandCardsNodes(), handler);
        },

        enablePlayableCardsClick: function(handler) {
            this.enableCardsClick(this.getMyPlayableCardsNodes(), handler);
        },

        enableCardsClick: function(cardNodes, handler) {
            this.disablePlayableCardsClick();

            cardNodes.addClass('clickable');
            // TODO: Find the proper way to do this and pass hand-card through the event
            var _this = this;
            this.myPlayableCardsClickSignal = cardNodes.on(
                'click',
                function() { lang.hitch(_this, handler)({target: this}); }
            );
        },

        disablePlayableCardsClick: function() {
            this.getMyPlayableCardsNodes().removeClass('clickable').removeClass('selected');
            if (this.myPlayableCardsClickSignal) {
                this.myPlayableCardsClickSignal.remove();
            }
        },

        ///////////////////////////////////////////////////
        //// Player's action
        onHandCardReturnClick: function(e) {
            query(e.target).toggleClass('selected');
        },

        onHandCardPlayClick: function(e) {
            var clickedCard = e.target;
            this.getMyPlayableCardsNodes().forEach(function(card) {
                if (card !== clickedCard) {
                    query(card).removeClass('selected');
                }
            });
            query(clickedCard).toggleClass('selected');

            // TODO: Remove all clickable positions
            var selectedIds = this.getMySelectedPlayableCards().attr('data-id');
            for (var i in selectedIds) {
                var selectedId = selectedIds[i];
                console.log('selected', this.possiblePlacementsByCardId[selectedId]);
            }
        },

        onSubmitReturnCards: function() {
            if (!this.checkAction('returnToDeck')) {
                return;
            }

            var selectedIds = this.getMySelectedPlayableCards().attr('data-id');
            // TODO: Where should this business logic go?
            if (selectedIds.length !== 2) {
                alert('Must select exactly 2 cards to return');
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
            dojo.subscribe('opponentReturnedToDeck', lang.hitch(this, this.notif_opponentReturnedToDeck));

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
            var handCards = this.getMyHandCardsNodes();
            array.forEach(
                array.map(notification.args.oldIds, lang.hitch(this, this.getMyHandCardNodeById))
                    .sort(lang.hitch(this,
                        function(card1, card2) { return handCards.indexOf(card2) - handCards.indexOf(card1); }
                    )),
                lang.hitch(this, function(card) { this.slideToDeckAndDestroy(card, this.getMyDeckNode()); })
            );

            // Take card from deck then slide to hand
            setTimeout(
                lang.hitch(this, function() {
                    array.forEach(
                        array.map(
                            notification.args.replacements,
                            lang.hitch(this, this.createMyHandCard)
                        ),
                        lang.hitch(this, function (cardDisplay, i) {
                            var offset = i * 20;
                            this.slideNewElementTo(this.getMyDeckNode(), cardDisplay, this.getMyHandCardsContainerNode(), {x: offset, y: offset})
                                .on("End", lang.hitch(this, this.placeInMyHand));
                        })
                    );
                }),
                SLIDE_ANIMATION_DURATION
            );
        },

        notif_opponentReturnedToDeck: function(notification) {
            var numCards = notification.args.numCards;
            // Just use random cards - doesn't matter which one actually moved
            var handCards = this.getOpponentHandCardsNodes();
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
                lang.hitch(this, function(card) { this.slideToDeckAndDestroy(card, this.getOpponentDeckNode()); })
            );

            // Take card from deck then slide to hand
            var newCards = [];
            for (var i = 0; i < numCards; i++) {
                newCards.push(this.createOpponentHandCard({type: 'back'}));
            }
            setTimeout(
                lang.hitch(this, function() {
                    array.forEach(
                        newCards,
                        lang.hitch(this, function (cardDisplay, i) {
                            var offset = i * 20;
                            this.slideNewElementTo(this.getOpponentDeckNode(), cardDisplay, this.getOpponentHandCardsContainerNode(), {x: offset, y: offset})
                                .on("End", lang.hitch(this, this.placeInOpponentHand));
                        })
                    );
                }),
                SLIDE_ANIMATION_DURATION
            );
        }
   });             
});
