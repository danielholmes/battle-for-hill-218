{OVERALL_GAME_HEADER}

<div id="game-container" class="{GAME_CONTAINER_CLASS}">
    <!-- BEGIN player_cards -->
    <div id="players-panel">
        <div id="my-cards" class="player-cards whiteblock">
            <h3>Your hand</h3>
            <div class="hand-cards"></div>
        </div>
    </div>
    <!-- END player_cards -->
    <div id="battlefield-panel" class="whiteblock">
        <div id="map_container">
            <div id="map_scrollable">
                <div class="zoomable"></div>
            </div>
            <div id="map_surface">
                <div class="zoomable"></div>
            </div>
            <div id="map_scrollable_oversurface">
                <div class="zoomable"></div>
            </div>
            <div id="zoom_controls">
                <a id="reset_map" role="button" class="zoom_control"><i class="fa fa-map-marker" aria-hidden="true"></i></a>
                <a id="zoom_in" role="button" class="zoom_control"><i class="fa fa-search-plus" aria-hidden="true"></i></a>
                <a id="zoom_out" role="button" class="zoom_control"><i class="fa fa-search-minus" aria-hidden="true"></i></a>
            </div>
            <a id="movetop" role="button"></a>
            <a id="moveleft" role="button"></a>
            <a id="moveright" role="button"></a>
            <a id="movedown" role="button"></a>
        </div>
    </div>
</div>

<script type="text/javascript">
    var jstpl_air_strike_card = '<div id="playable-card-${type}-${color}" class="playable-card">\
        <div class="card ${type} color-${color}"></div>\
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
    var jstpl_battlefield_button = '<div class="battlefield-button" style="left: ${left}px;top: ${top}px" data-x="${x}" data-y="${y}"></div>';
    var jstpl_counter_icons = '<div>\
        <div id="air-strike-count-${playerId}" class="counter-cell air-strike-count">\
            <span class="counter-icon"></span>\
            <span class="counter-text"></span>\
        </div>\
        <div id="hand-count-${playerId}" class="counter-cell hand-count">\
            <span class="counter-icon"></span>\
            <span class="counter-text"></span>\
        </div>\
        <div id="deck-count-${playerId}" class="counter-cell deck-count">\
            <span class="counter-icon"></span>\
            <span class="counter-text"></span>\
        </div>\
    </div>';
</script>

{OVERALL_GAME_FOOTER}
