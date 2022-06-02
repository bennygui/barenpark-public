{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- barenpark implementation : © Guillaume Benny bennygui@gmail.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    barenpark_barenpark.tpl
-->
<div id="bp-display-last-turn" class="bx-hidden">
    <div>{DISPLAY_LAST_TURN}</div>
</div>

<div id='bp-area-full'>
    <div id='bp-supply-board'>
        <div class='bp-shape-supply-board-wrap'>
            <div class='bp-shape-supply-board'>
                <button class='bp-supply-board-view-button' id='bp-supply-board-button-shape-green-toilet'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>
                <button class='bp-supply-board-view-button' id='bp-supply-board-button-shape-green-playground'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>
                <button class='bp-supply-board-view-button' id='bp-supply-board-button-shape-green-river'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>
                <button class='bp-supply-board-view-button' id='bp-supply-board-button-shape-green-food-street'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>

                <div class='bp-supply-board-overlap' id='bp-supply-shape-green-toilet'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-green-playground'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-green-river'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-green-food-street'></div>

                <button class='bp-supply-board-view-button' id='bp-supply-board-button-shape-white-animal-house-gobi'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>
                <button class='bp-supply-board-view-button' id='bp-supply-board-button-shape-white-animal-house-koala'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>
                <button class='bp-supply-board-view-button' id='bp-supply-board-button-shape-white-animal-house-polar'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>
                <button class='bp-supply-board-view-button' id='bp-supply-board-button-shape-white-animal-house-panda'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>

                <div class='bp-supply-board-overlap' id='bp-supply-shape-white-animal-house-gobi'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-white-animal-house-koala'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-white-animal-house-polar'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-white-animal-house-panda'></div>

                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-gobi6'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-gobi7'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-gobi8'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-koala6'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-koala7'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-koala8'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-polar6'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-polar7'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-polar8'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-panda6'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-panda7'></div>
                <div class='bp-supply-board-overlap' id='bp-supply-shape-orange-enclosure-panda8'></div>

                <div class="bp-icon-shape-green-base bx-hidden" id="bp-icon-shape-green-base-supply"></div>
                <div class="bp-icon-shape-white-animal-house-base bx-hidden" id="bp-icon-shape-white-animal-house-base-supply"></div>
                <div class="bp-icon-shape-orange-enclosure-base bx-hidden" id="bp-icon-shape-orange-enclosure-base-supply"></div>
            </div>
        </div>
        <div class='bp-supply-board-line' id='bp-supply-shape-bear-statue'></div>
        <div class='bp-supply-board-line' id='bp-supply-park'></div>
        <div class='bp-supply-board-achievements bx-hidden'>
            <div class='bp-supply-board-overlap' id='bp-supply-board-achievement-0'>
                <button class='bp-supply-board-view-button'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>
            </div>
            <div class='bp-supply-board-overlap' id='bp-supply-board-achievement-1'>
                <button class='bp-supply-board-view-button'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>
            </div>
            <div class='bp-supply-board-overlap' id='bp-supply-board-achievement-2'>
                <button class='bp-supply-board-view-button'><div class='bp-inline-eye'></div>&nbsp;<span></span></button>
            </div>
        </div>
    </div>
    <div id='bp-player-area'>
        <!-- BEGIN player-area -->
        <div class='bp-player-area-container-wrap'>
            <div id='bp-player-area-{PLAYER_ID}' class='bp-player-area-container'>
                <div class='bp-player-area-title'>
                    <h3 style='color: #{PLAYER_COLOR};'>{PLAYER_NAME}</h3>
                    <div class='bp-try-mode-title bx-hidden'>{TRY_MODE_TITLE}</div>
                    <div class='bp-prepare-mode-title bx-hidden'>{PREPARE_MODE_TITLE}</div>
                </div>
                <div class='bp-has-undo-action-title bx-hidden'>{HAS_UNDO_ACTION_TITLE}</div>
                <div class='bp-player-area-park-controls'>
                    <div class="bp-park-controls bp-arrow-flip-h"></div>
                    <div class="bp-park-controls bp-arrow-up"></div>
                    <div class="bp-park-controls bp-arrow-flip-v"></div>
                    <div class="bp-park-controls bp-arrow-left"></div>
                    <div class='bp-player-area-parks'></div>
                    <div class="bp-park-controls bp-arrow-right"></div>
                    <div class="bp-park-controls bp-arrow-cw"></div>
                    <div class="bp-park-controls bp-arrow-down"></div>
                    <div class="bp-park-controls bp-arrow-ccw"></div>
                    <div class="bp-park-controls bp-overlapped-icons"></div>
                    <div class="bp-park-controls bp-arrow-accept"></div>
                </div>
                <div class='bp-player-area-supply'></div>
                <div class='bp-player-area-achievement'></div>
            </div>
        </div>
        <!-- END player-area -->
    </div>
</div>
<div id='bp-element-creation'></div>

{OVERALL_GAME_FOOTER}