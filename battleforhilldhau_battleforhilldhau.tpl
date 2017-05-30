{OVERALL_GAME_HEADER}

<div id="game-container">
    <div id="players-panel">

        <!-- BEGIN player_cards -->
        <div id="player-cards-{PLAYER_ID}" class="player-cards whiteblock {EXTRA_CONTAINER_CLASS}">
            <h3>{PLAYER_LABEL}</h3>
            <div class="air-strikes"></div>
            <div class="hand-cards"></div>
        </div>
        <!-- END player_cards -->
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
    var jstpl_air_strike_card = '<div class="playable-card" data-id="${id}"><div class="selected-border landscape-border card"></div><div class="card ${type} color-${color}"></div></div>';
    var jstpl_hand_card = '<div class="playable-card hand-card" data-id="${id}"><div class="selected-border card"></div><div class="card ${type} color-${color}"></div></div>';
    var jstpl_opponent_hand_card = '<div class="playable-card hand-card"><div class="card ${type} color-${color}"></div></div>';
    var jstpl_opponent_air_strike_card = '<div class="playable-card"><div class="card ${type} color-${color}"></div></div>';
    var jstpl_battlefield_card = '<div class="battlefield-card" style="left: ${left}px;top: ${top}px"><div class="card ${type} color-${color}"></div></div>';
    var jstpl_counter_icons = '<div id="counter-icons"><div class="deck-icon"></div><span class="deck-count">0</span><div class="placed-icon"></div><span class="placed-count">0</span></div>';
</script>

{OVERALL_GAME_FOOTER}
