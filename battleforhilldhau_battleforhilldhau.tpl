{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- BattleForHillDhau implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    battleforhilldhau_battleforhilldhau.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->


<div id="game-container">
    <div id="players-panel">
        <div id="opponent-hand" class="whiteblock player-hand">
            <h3>Opponent Hand</h3>
            <div class="air-strikes"></div>
            <div class="hand-cards"></div>
        </div>
        <div id="my-hand" class="whiteblock player-hand">
            <h3>My Hand</h3>
            <div class="air-strikes"></div>
            <div class="hand-cards"></div>
        </div>
    </div>
    <div id="cards-panel" class="whiteblock">
    </div>
</div>


<script type="text/javascript">

var jstpl_hand_card = '<div class="hand-card"><div class="card ${type}"></div></div>';

</script>  

{OVERALL_GAME_FOOTER}
