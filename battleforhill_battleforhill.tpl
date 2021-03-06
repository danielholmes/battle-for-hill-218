{OVERALL_GAME_HEADER}

<div id="game-container" class="{GAME_CONTAINER_CLASS}">
    <!-- BEGIN player_cards -->
    <div id="players-panel" class="whiteblock">
        <h3>{YOUR_HAND}</h3>
        <div class="player-cards">
            <div class="air-strike-cards"></div>
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
    var jstpl_opponent_hand_card = '<div class="playable-card hand-card">\
        <div class="card ${type} color-${color}"></div>\
    </div>';
    var jstpl_opponent_air_strike_card = '<div class="playable-card"><div class="card ${type} color-${color}"></div></div>';
    var jstpl_battlefield_position = '<div id="position-${x}-${y}" class="battlefield-position" style="left: ${left}px;top: ${top}px" data-x="${x}" data-y="${y}"></div>';
    var jstpl_battlefield_card = '<div class="battlefield-card"><div class="card ${type} color-${color}"></div></div>';
    var jstpl_base_indicator = '<div class="battlefield-card">\
        <div class="card base-indicator">${baseName}</div>\
    </div>';
    var jstpl_battlefield_button = '<div id="battlefield-button-${x}-${y}" class="battlefield-button" style="left: ${left}px;top: ${top}px" data-x="${x}" data-y="${y}">\
        <div class="coordinates">${x},${y}</div>\
    </div>';
    var jstpl_explosion = '<div class="explosion"></div>';
    var jstpl_zoomed_sliding_card = '<div class="zoom${zoom}">${card}</div>';
    var jstpl_card_tooltip = '<div><strong>${message}</strong><div class="tooltip-card ${type} color-${color}"></div></div>';
    var jstpl_air_strike_icon = '<div id="air-strike-icon-${playerId}-${id}" data-id="${id}" class="air-strike-icon"></div>';
    var jstpl_counter_icons = '<div class="player-icons">\
        <div id="player-number-${playerId}" class="counter-cell player-number"><span class="counter-text"></span></div>\
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
        <div id="units-in-play-count-${playerId}" class="counter-cell units-in-play">\
            <span class="counter-icon"></span>\
            <span class="counter-text"></span>\
        </div>\
        <div id="units-destroyed-count-${playerId}" class="counter-cell units-destroyed">\
            <span class="counter-icon"></span>\
            <span class="counter-text"></span>\
        </div>\
      </div>';
</script>

{OVERALL_GAME_FOOTER}
