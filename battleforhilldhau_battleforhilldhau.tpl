{OVERALL_GAME_HEADER}

<div id="game-container">
    <!-- BEGIN player_cards -->
    <div id="players-panel">
        <div id="my-cards" class="player-cards whiteblock">
            <h3>My cards</h3>
            <div class="air-strikes"></div>
            <div class="hand-cards"></div>
        </div>
    </div>
    <!-- END player_cards -->
    <div id="battlefield-panel" class="whiteblock {BATTLEFIELD_PANEL_CLASS}">
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
    var jstpl_air_strike_card = '<div id="playable-card-${id}" class="playable-card" data-id="${id}" data-type="${type}" data-color="${color}">\
        <div class="selected-border card"></div><div class="card ${type} color-${color}"></div>\
    </div>';
    var jstpl_hand_card = '<div id="playable-card-${id}" class="playable-card hand-card" data-id="${id}" data-type="${type}" data-color="${color}">\
        <div class="selected-border card"></div><div class="card ${type} color-${color}"></div>\
    </div>';
    var jstpl_opponent_hand_card = '<div class="playable-card hand-card"><div class="card ${type} color-${color}"></div></div>';
    var jstpl_opponent_air_strike_card = '<div class="playable-card"><div class="card ${type} color-${color}"></div></div>';
    var jstpl_battlefield_position = '<div id="position-${x}-${y}" class="battlefield-position" style="left: ${left}px;top: ${top}px" data-x="${x}" data-y="${y}">\
        <div class="clickable-indicator"></div>\
    </div>';
    var jstpl_battlefield_card = '<div class="battlefield-card"><div class="card ${type} color-${color}"></div></div>';
    var jstpl_counter_icons = '<div id="counter-icons">\
        <div class="air-strike-count-icon"></div><span class="air-strike-count"></span>\
        <div class="hand-count-icon"></div><span class="hand-count"></span>\
        <div class="deck-count-icon"></div><span class="deck-count"></span>\
    </div>';
</script>

{OVERALL_GAME_FOOTER}
