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
    "dojo/fx/easing",
    "dojo/NodeList-data",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare, lang, dom, query, array, domConstruct, domGeom, fx, easing) {
    return declare("bgagame.battleforhilldhau", ebg.core.gamegui, {
        constructor: function() {
            this.slideAnimationDuration = 1000;
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
                dojo.place(this.createMyHandCard(card), query('#my-hand .air-strikes').pop());
            }));

            var handCards = array.filter(data.hand, function(card) { return card.type !== 'air-strike'; });
            array.forEach(
                array.map(
                    handCards,
                    lang.hitch(this, this.createMyHandCard)
                ),
                lang.hitch(this, this.placeInMyHand)
            );

            this.getMyHandCardsNodes().filter('.clickable').connect('onclick', this, 'onHandCardClick');
        },

        setupOpponentHand: function(data) {
            this.opponentColor = data.color;

            for (var i = 0; i < data.numAirStrikes; i++) {
                dojo.place(
                    this.createOpponentHandCard({type: 'air-strike'}),
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
                dojo.place(this.createBattlefieldCard(card, ''), 'battlefield-panel');
            }));
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function(stateName, args) {
            console.log('Entering state', stateName);
            query('#game-container').addClass('state-' + stateName);
            switch (stateName) {
                case 'returnToDeck':
                    this.onEnterReturnToDeck();
                    break;
            }
        },

        onEnterReturnToDeck: function() {
            var handCards = this.getMyHandCardsNodes();
            handCards.addClass('clickable');
            // TODO: Find the proper way to do this and pass hand-card through the event
            var _this = this;
            handCards.on('click', function() { _this.onHandCardReturnClick({target: this}); });

            query('#return-cards').on('click', lang.hitch(this, this.onSubmitReturnCards));
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
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
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

        getMyHandCardNodeById: function(cardId) {
            return query(this.getMyHandCardsNodes()).filter('[data-id=' + cardId + ']').pop();
        },

        placeInMyHand: function(card) {
            dojo.place(this.recoverFromAnimation(card), this.getMyHandCardsContainerNode())
        },

        getSelectedHandCards: function() {
            return this.getMyHandCardsNodes().filter('.selected');
        },

        createMyHandCard: function(card) {
            var coloredCard = lang.mixin({}, card, {color: this.myColor});
            return domConstruct.toDom(this.format_block('jstpl_hand_card', coloredCard));
        },

        createOpponentHandCard: function(card) {
            var coloredCard = lang.mixin({}, card, {color: this.opponentColor});
            return domConstruct.toDom(this.format_block('jstpl_opponent_hand_card', coloredCard));
        },

        createBattlefieldCard: function(card, color) {
            var coloredCard = lang.mixin({}, card, {color: color});
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

        slideToOpponentDeckAndDestroy: function(card) {
            this.slideToObjectAndDestroy(
                this.prepareForAnimation(card),
                this.getOpponentDeckNode(),
                this.slideAnimationDuration
            );
        },

        slideFromOpponentDeck: function(card, target, offset) {
            if (!offset) {
                offset = {x: 0, y:0};
            }
            this.prepareForAnimation(card);
            dojo.place(card, query('body').pop());
            this.placeOnObjectPos(card, this.getOpponentDeckNode(), offset.x, offset.y);
            var targetPosition = this.getCentredPosition(card, target);
            return fx.slideTo({
                node: card,
                left: targetPosition.x + offset.x,
                top: targetPosition.y + offset.y,
                units: "px",
                duration: this.slideAnimationDuration
            }).play();
        },

        slideToMyDeckAndDestroy: function(card) {
            this.slideToObjectAndDestroy(
                this.prepareForAnimation(card),
                this.getMyDeckNode(),
                this.slideAnimationDuration
            );
        },

        slideFromMyDeck: function(card, target, offset) {
            if (!offset) {
                offset = {x: 0, y:0};
            }
            this.prepareForAnimation(card);
            dojo.place(card, query('body').pop());
            this.placeOnObjectPos(card, this.getMyDeckNode(), offset.x, offset.y);
            var targetPosition = this.getCentredPosition(card, target);
            return fx.slideTo({
                node: card,
                left: targetPosition.x + offset.x,
                top: targetPosition.y + offset.y,
                units: "px",
                duration: this.slideAnimationDuration
            }).play();
        },

        ///////////////////////////////////////////////////
        //// Player's action
        onHandCardReturnClick: function(e) {
            query(e.target).toggleClass('selected');
            // TODO: Where should this business logic go?
            if (this.getSelectedHandCards().length === 2) {
                query('#return-cards').removeAttr('disabled');
            } else {
                query('#return-cards').attr('disabled', 'disabled');
            }
        },

        onSubmitReturnCards: function() {
            if (!this.checkAction('returnToDeck')) {
                return;
            }

            this.ajaxcall(
                "/battleforhilldhau/battleforhilldhau/returnToDeck.html",
                {
                    lock: true,
                    ids: this.getSelectedHandCards().attr('data-id').join(',')
                },
                function(result) { },
                function(isError) {
                    // TODO: Re-enable button if error
                }
            );
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
            // 
        },

        notif_returnedToDeck: function(notification) {
            // Return old cards to deck
            // Sorting makes sure positioning is correct (and don't remove earlier card first thus repositioning the
            // latter card before animating
            var handCards = this.getMyHandCardsNodes();
            array.forEach(
                array.map(notification.args.oldIds, lang.hitch(this, this.getMyHandCardNodeById))
                    .sort(lang.hitch(this,
                        function(card1, card2) { return handCards.indexOf(card2) - handCards.indexOf(card1); }
                    )),
                lang.hitch(this, this.slideToMyDeckAndDestroy)
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
                            this.slideFromMyDeck(cardDisplay, this.getMyHandCardsContainerNode(), {x: offset, y: offset})
                                .on("End", lang.hitch(this, this.placeInMyHand));
                        })
                    );
                }),
                this.slideAnimationDuration
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
                lang.hitch(this, this.slideToOpponentDeckAndDestroy)
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
                            this.slideFromOpponentDeck(cardDisplay, this.getOpponentHandCardsContainerNode(), {x: offset, y: offset})
                                .on("End", lang.hitch(this, this.placeInOpponentHand));
                        })
                    );
                }),
                this.slideAnimationDuration
            );
        }
   });             
});
