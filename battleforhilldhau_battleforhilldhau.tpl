{OVERALL_GAME_HEADER}

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
    <div id="battlefield-panel" class="whiteblock">
        <div id="map_container">
            <div id="map_scrollable"></div>
            <div id="map_surface"></div>
            <div id="map_scrollable_oversurface"></div>
            <a id="movetop" href="#"></a>
            <a id="moveleft" href="#"></a>
            <a id="moveright" href="#"></a>
            <a id="movedown" href="#"></a>
        </div>
    </div>
</div>

<script type="text/javascript">
var jstpl_hand_card = '<div class="hand-card" data-id="${id}"><div class="selected-border card"></div><div class="card ${type} color-${color}"></div></div>';
var jstpl_opponent_hand_card = '<div class="hand-card"><div class="card ${type} color-${color}"></div></div>';
var jstpl_battlefield_card = '<div class="battlefield-card" style="left: ${left}px;top: ${top}px"><div class="card ${type} color-${color}"></div></div>';
</script>

{OVERALL_GAME_FOOTER}
