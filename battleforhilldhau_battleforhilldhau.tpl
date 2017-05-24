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
        <div id="opponent-hand" class="whiteblock">
            <h3>Opponent Hand</h3>
        </div>
        <div id="my-hand" class="whiteblock">
            <h3>My Hand</h3>
        </div>
    </div>
    <div id="cards-panel" class="whiteblock">
        Cards Panel<br />
        Cards Panel<br />
        Cards Panel<br />
        Cards Panel<br />
    </div>
</div>


<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${id}"></div>';

*/

</script>  

{OVERALL_GAME_FOOTER}
