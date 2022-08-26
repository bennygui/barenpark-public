/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * barenpark implementation : © Guillaume Benny bennygui@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * barenpark.js
 *
 * barenpark user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define([
    "dojo",
    "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    g_gamethemeurl + "modules/js/BX/Game.js",
    g_gamethemeurl + "modules/js/BP/ShapeMgr.js",
    g_gamethemeurl + "modules/js/BP/ParkMgr.js",
    g_gamethemeurl + "modules/js/BP/AchievementMgr.js",
    g_gamethemeurl + "modules/js/BP/PlayerSupplyMgr.js",
    g_gamethemeurl + "modules/js/BP/PlayerParkMgr.js",
    g_gamethemeurl + "modules/js/BP/SupplyBoardMgr.js",
    g_gamethemeurl + "modules/js/BP/PlayerScore.js",
    g_gamethemeurl + "modules/js/BP/TryMode.js",
    g_gamethemeurl + "modules/js/BP/LastMove.js",
],
    function (dojo, declare) {
        return declare("bgagame.barenpark", [bx.Game, bp.PlayerScore, bp.TryMode, bp.LastMove], {
            TOP_BUTTON_PLACE_IN_PARK_ID: 'bp-button-place-in-park',

            SHAPE_LOCATION_ID_SUPPLY_BOARD: 0,
            SHAPE_LOCATION_ID_PLAYER_SUPPLY: 1,
            SHAPE_LOCATION_ID_PLAYER_PARK: 2,

            GRID_SIZE: 80,

            PREF_COLUMN_DISPLAY_ID: 'PREF_COLUMN_DISPLAY_ID',
            PREF_COLUMN_DISPLAY_VALUE_ADAPT: 'c-adapt',
            PREF_COLUMN_DISPLAY_VALUE_1_COLUMN: 'c-1',
            PREF_COLUMN_DISPLAY_VALUE_2_COLUMN: 'c-2',

            PREF_ZOOM_FACTOR_ID: 'PREF_ZOOM_FACTOR_ID',
            PREF_ZOOM_FACTOR_DEFAULT_VALUE: 2,
            PREF_ZOOM_SUPPLY_FACTOR_ID: 'PREF_ZOOM_SUPPLY_FACTOR_ID',
            PREF_ZOOM_SUPPLY_FACTOR_DEFAULT_VALUE: 2,

            PREF_SHOW_SHAPE_GRID_ID: 'PREF_SHOW_SHAPE_GRID_ID',
            PREF_SHOW_SHAPE_GRID_DEFAULT_VALUE: false,

            constructor() {
                this.zoomFactor = this.PREF_ZOOM_FACTOR_DEFAULT_VALUE;
                this.zoomSupplyFactor = this.PREF_ZOOM_SUPPLY_FACTOR_DEFAULT_VALUE;

                this.PREF_COLUMN_DISPLAY_VALUES = [
                    this.PREF_COLUMN_DISPLAY_VALUE_ADAPT,
                    this.PREF_COLUMN_DISPLAY_VALUE_1_COLUMN,
                    this.PREF_COLUMN_DISPLAY_VALUE_2_COLUMN
                ];
                // Format: ['notif', delay]
                this.notificationsToRegister.push(['NTF_MOVE_SHAPE_TO_PLAYER_SUPPLY', 600]);
                this.notificationsToRegister.push(['NTF_MOVE_SHAPE_TO_PLAYER_PARK', 600]);
                this.notificationsToRegister.push(['NTF_MOVE_SHAPE_TO_SUPPLY_BOARD', 500]);
                this.notificationsToRegister.push(['NTF_REPLACE_PLAYER_PARK_AREA', 600]);
                this.notificationsToRegister.push(['NTF_REPLACE_SUPPLY_BOARD_PARKS', 600]);
                this.notificationsToRegister.push(['NTF_UPDATE_SUPPLY_SHAPES_COUNT', null]);
                this.notificationsToRegister.push(['NTF_UPDATE_SUPPLY_ACHIEVEMENTS_COUNT', null]);
                this.notificationsToRegister.push(['NTF_DISPLAY_LAST_TURN', null]);
                this.notificationsToRegister.push(['NTF_MOVE_ACHIEVEMENT_TO_PLAYER', 600]);
                this.notificationsToRegister.push(['NTF_MOVE_ACHIEVEMENT_TO_SUPPLY_BOARD', 600]);

                // Format: ['prefId', defaultValue, {value: 'description', ...}]
                const prefColDesc = {};
                prefColDesc[this.PREF_COLUMN_DISPLAY_VALUE_ADAPT] = _('Adapt the view to the screen size. This is the default.');
                prefColDesc[this.PREF_COLUMN_DISPLAY_VALUE_1_COLUMN] = _('Force to display in one column: supply board at the top and then the player parks.');
                prefColDesc[this.PREF_COLUMN_DISPLAY_VALUE_2_COLUMN] = _('Force to display in two columns: supply board on the right and player parks on the left.');
                this.localPreferenceToRegister.push([this.PREF_COLUMN_DISPLAY_ID, this.PREF_COLUMN_DISPLAY_VALUE_ADAPT, prefColDesc]);
                this.localPreferenceToRegister.push([this.PREF_ZOOM_FACTOR_ID, this.PREF_ZOOM_FACTOR_DEFAULT_VALUE, {}]);
                this.localPreferenceToRegister.push([this.PREF_ZOOM_SUPPLY_FACTOR_ID, this.PREF_ZOOM_SUPPLY_FACTOR_DEFAULT_VALUE, {}]);
                this.localPreferenceToRegister.push([this.PREF_SHOW_SHAPE_GRID_ID, this.PREF_SHOW_SHAPE_GRID_DEFAULT_VALUE, {}]);

                this.htmlTextForLogKeys.push('shapeImage');
                this.htmlTextForLogKeys.push('parkImage');
                this.htmlTextForLogKeys.push('achievementImage');

                this.shapeMgr = new bp.ShapeMgr();
                this.parkMgr = new bp.ParkMgr();
                this.achievementMgr = new bp.AchievementMgr();
                this.playerSupplyMgr = new bp.PlayerSupplyMgr(this.shapeMgr);
                this.playerParkMgr = new bp.PlayerParkMgr(this.shapeMgr, this.parkMgr);
                this.supplyBoardMgr = new bp.SupplyBoardMgr(this.shapeMgr, this.parkMgr);

                this.playerParkMgr.registerBeforeTakePark((parkId, supplyPile) => {
                    this.supplyBoardMgr.replaceParkInPileWithStandIn(parkId, supplyPile);
                });
                this.playerParkMgr.registerReturnPark((parkId, isInstantaneous) => {
                    return this.supplyBoardMgr.moveParkIdToSupplyBoard(parkId, isInstantaneous);
                });

                this.placeTileInParkPosition = null;
                this.placeTileInParkPositionIsValid = false;

                dojo.connect(window, "onresize", this, () => this.setZoomSupplyFactor(this.zoomSupplyFactor));
            },

            setup(gamedatas) {
                this.setupPlayersPanel(gamedatas);

                this.shapeMgr.setup(gamedatas);
                this.parkMgr.setup(gamedatas);
                this.achievementMgr.setup(gamedatas);
                this.playerSupplyMgr.setup(gamedatas);
                this.playerParkMgr.setup(gamedatas);
                this.supplyBoardMgr.setup(gamedatas);

                if (gamedatas.isLastTurn) {
                    this.displayLastTurn();
                }
                this.inherited(arguments);
            },

            setupPlayersPanel(gamedatas) {
                // Hide score in panel depending on game option
                if (gamedatas.hideScore) {
                    for (const elem of document.querySelectorAll('#player_boards .player_score .player_score_value')) {
                        elem.classList.add('bx-hidden');
                    }
                }
                const playerOrderClass = 'bp-player-panel-player-order';
                const playerIdArray = Object.keys(gamedatas.players);
                playerIdArray.sort((p1, p2) => gamedatas.players[p1].player_no - gamedatas.players[p2].player_no);

                for (const playerOrder in playerIdArray) {
                    const playerId = playerIdArray[playerOrder];
                    const playerBoardElem = document.getElementById('player_board_' + playerId);

                    // Show player order
                    const playerOrderElem = document.createElement('div');
                    playerOrderElem.dataset.playerOrder = (parseInt(playerOrder) + 1);
                    playerOrderElem.classList.add(playerOrderClass);
                    playerBoardElem.appendChild(playerOrderElem);
                }
                this.addTooltipToClass(playerOrderClass, _('Player order'), '');

                const playerPanelContainer = document.getElementById('player_boards');
                const displayOptionsPanel = document.createElement('div');
                displayOptionsPanel.classList.add('player-board');
                playerPanelContainer.appendChild(displayOptionsPanel);

                const columnDescription = document.createElement('div');
                columnDescription.classList.add('bp-player-panel-column-description');
                columnDescription.innerText = _('Display in columns');
                displayOptionsPanel.appendChild(columnDescription);

                // Preference for 1 or 2 columns
                const columnElem = document.createElement('div');
                columnElem.classList.add('bp-player-panel-columns');
                displayOptionsPanel.appendChild(columnElem);
                for (const prefValue of this.PREF_COLUMN_DISPLAY_VALUES) {
                    const elem = document.createElement('div');
                    elem.id = 'bp-player-panel-columns-' + prefValue;
                    elem.classList.add('bp-icon-column', prefValue);
                    columnElem.appendChild(elem);
                    dojo.connect(elem, 'onclick', () => {
                        this.setLocalPreference(this.PREF_COLUMN_DISPLAY_ID, prefValue);
                    });
                    this.addTooltip(
                        elem.id,
                        this.getLocalPreferenceValueDescription(this.PREF_COLUMN_DISPLAY_ID, prefValue),
                        ''
                    );
                }

                // Preference for zoom factor for tiles
                const zoomDescription = document.createElement('div');
                zoomDescription.classList.add('bp-player-panel-column-description');
                zoomDescription.innerText = _('Zoom Player Parks');
                displayOptionsPanel.appendChild(zoomDescription);

                const sliderElem = document.createElement('input');
                sliderElem.id = 'bp-player-panel-zoom-slider';
                sliderElem.classList.add('bp-player-panel-zoom');
                sliderElem.type = 'range';
                sliderElem.min = '0';
                sliderElem.max = '10';
                sliderElem.value = '0';
                sliderElem.addEventListener('input', (e) => {
                    const zoom = (2 - (parseInt(sliderElem.value) / 10));
                    this.setLocalPreference(this.PREF_ZOOM_FACTOR_ID, zoom);
                });
                displayOptionsPanel.appendChild(sliderElem);
                this.addTooltip(
                    sliderElem.id,
                    _('Zoom player tiles and parks'),
                    ''
                );

                // Preference for zoom factor for supply
                const zoomSupplyDescription = document.createElement('div');
                zoomSupplyDescription.classList.add('bp-player-panel-column-description');
                zoomSupplyDescription.innerHTML = this.format_string_recursive(
                    _('Zoom Supply${newline}(use 2 columns to zoom more)'),
                    { newline: '<br>' }
                );
                displayOptionsPanel.appendChild(zoomSupplyDescription);

                const sliderSupplyElem = document.createElement('input');
                sliderSupplyElem.id = 'bp-player-panel-zoom-supply-slider';
                sliderSupplyElem.classList.add('bp-player-panel-zoom');
                sliderSupplyElem.type = 'range';
                sliderSupplyElem.min = '0';
                sliderSupplyElem.max = '10';
                sliderSupplyElem.value = '0';
                sliderSupplyElem.addEventListener('input', (e) => {
                    const zoom = (2 - (parseInt(sliderSupplyElem.value) / 10));
                    this.setLocalPreference(this.PREF_ZOOM_SUPPLY_FACTOR_ID, zoom);
                });
                displayOptionsPanel.appendChild(sliderSupplyElem);
                this.addTooltip(
                    sliderSupplyElem.id,
                    _('Zoom supply board tiles. If your display is too small, you can force to display in two columns to zoom more.'),
                    ''
                );

                // Preference for show grid
                const showGridLabel = document.createElement('label');
                showGridLabel.classList.add('bp-player-panel-column-description');

                const showGridElem = document.createElement('input');
                showGridElem.id = 'bp-player-panel-show-grid-checkbox';
                showGridElem.type = 'checkbox';
                showGridElem.addEventListener('change', (e) => {
                    this.setLocalPreference(this.PREF_SHOW_SHAPE_GRID_ID, showGridElem.checked);
                });
                showGridLabel.appendChild(showGridElem);

                const showGridLabelText = document.createElement('span');
                showGridLabelText.innerText = ' ' + _('Show tile grid');
                showGridLabel.appendChild(showGridLabelText);

                displayOptionsPanel.appendChild(showGridLabel);
            },

            getGridSize() {
                return this.GRID_SIZE / this.zoomFactor;
            },

            setZoomFactor(zoomFactor) {
                if (zoomFactor > 2) {
                    zoomFactor = 2;
                }
                this.zoomFactor = zoomFactor;
                document.body.style.setProperty('--bp-zoom-factor', zoomFactor);
                const sliderElem = document.getElementById('bp-player-panel-zoom-slider');
                if (sliderElem !== null) {
                    sliderElem.value = 20 - (zoomFactor * 10);
                }
                for (const playerId in this.gamedatas.players) {
                    this.playerParkMgr.resizePlayerArea(playerId)
                }
            },

            setZoomSupplyFactor(zoomFactor) {
                if (zoomFactor > 2) {
                    zoomFactor = 2;
                }
                this.zoomSupplyFactor = zoomFactor;
                const supplyBoard = document.getElementById('bp-supply-board');
                supplyBoard.style.setProperty('--bp-zoom-factor', zoomFactor);
                if (zoomFactor < 2) {
                    const maxWidth = document.getElementById('bp-area-full').offsetWidth;
                    const boardWidth = document.querySelector('.bp-shape-supply-board-wrap').offsetWidth;
                    if (boardWidth > maxWidth) {
                        this.setZoomSupplyFactor(zoomFactor + 0.1);
                        return;
                    }
                }
                const sliderElem = document.getElementById('bp-player-panel-zoom-supply-slider');
                if (sliderElem !== null) {
                    sliderElem.value = 20 - (zoomFactor * 10);
                }
            },

            getElementCreationElement() {
                return document.getElementById('bp-element-creation');
            },

            onLocalPreferenceChanged(prefId, value) {
                switch (prefId) {
                    case this.PREF_COLUMN_DISPLAY_ID:
                        document.body.classList.remove('bp-one-column-display', 'bp-two-column-display');
                        this.interface_min_width = 900;
                        switch (value) {
                            case this.PREF_COLUMN_DISPLAY_VALUE_1_COLUMN:
                                document.body.classList.add('bp-one-column-display');
                                break;
                            case this.PREF_COLUMN_DISPLAY_VALUE_2_COLUMN:
                                document.body.classList.add('bp-two-column-display');
                                this.interface_min_width = 1300;
                                break;
                        }
                        this.onGameUiWidthChange();
                        for (const elem of document.querySelectorAll('.bp-player-panel-columns .bp-icon-column')) {
                            elem.classList.remove('selected');
                        }
                        const elem = document.querySelector('.bp-player-panel-columns .bp-icon-column.' + value);
                        if (elem !== null) {
                            elem.classList.add('selected');
                        }
                        this.setZoomFactor(this.zoomFactor);
                        break;
                    case this.PREF_ZOOM_FACTOR_ID:
                        this.setZoomFactor(value);
                        break;
                    case this.PREF_ZOOM_SUPPLY_FACTOR_ID:
                        this.setZoomSupplyFactor(value);
                        break;
                    case this.PREF_SHOW_SHAPE_GRID_ID:
                        const showGridCheckbox = document.getElementById('bp-player-panel-show-grid-checkbox');
                        if (value === "true" || value === true) {
                            document.body.classList.add('bp-show-shape-grid');
                            showGridCheckbox.checked = true;
                        } else {
                            document.body.classList.remove('bp-show-shape-grid');
                            showGridCheckbox.checked = false;
                        }
                        break;
                }
            },

            getHtmlTextForLogArg(key, value) {
                switch (key) {
                    case 'shapeImage': {
                        const element = this.shapeMgr.createShapeElementFromShapeDefId(value);
                        return element.outerHTML;
                    }
                    case 'parkImage': {
                        const element = this.parkMgr.createParkElementFromParkDefId(value);
                        return element.outerHTML;
                    }
                    case 'achievementImage': {
                        const element = this.achievementMgr.createAchievementElementFromAchievementId(value);
                        return element.outerHTML;
                    }
                }
                return this.inherited(arguments);
            },

            onStateChangedBefore(stateName, args) {
                this.inherited(arguments);
                this.removeAllClickable();
                this.removeAllSelected();
                this.removeAllCurrentTurnIndicator();
                this.playerParkMgr.removePlayerPlacementPark(this.player_id);
                this.playerParkMgr.hideParkControls(this.player_id);
                this.playerParkMgr.removeHoverShape();
            },

            onStateChangedAfter(stateName, args) {
                this.inherited(arguments);
                this.addAllCurrentTurnIndicator(args);
            },

            removeAllCurrentTurnIndicator() {
                for (const elem of document.querySelectorAll('.bp-current-turn-indicator')) {
                    elem.classList.remove('bp-current-turn-indicator');
                }
            },

            addAllCurrentTurnIndicator(args) {
                if (!args || !args.args) {
                    return;
                }
                if (args.args.currentTurnShapeIds) {
                    for (const shapeId of args.args.currentTurnShapeIds) {
                        const shapeElem = this.shapeMgr.getShapeElementById(shapeId);
                        shapeElem.classList.add('bp-current-turn-indicator');
                    }
                }
                if (args.args.currentTurnParkIds) {
                    for (const parkId of args.args.currentTurnParkIds) {
                        const parkElem = this.parkMgr.getParkElementById(parkId);
                        parkElem.classList.add('bp-current-turn-indicator');
                    }
                }
                if (args.args.currentTurnAchievementIds) {
                    for (const achievementId of args.args.currentTurnAchievementIds) {
                        const achievementElem = this.achievementMgr.getAchievementElementById(achievementId);
                        achievementElem.classList.add('bp-current-turn-indicator');
                    }
                }
            },

            onUpdateActionButtonsdAfter(stateName, args) {
                this.hideModeTitle();
                this.hideHasUndoAction();
                if (args !== null) {
                    if (args.isInTryMode) {
                        this.addTryModeTitle();
                    } else if (args.isInPrepareMode) {
                        this.addPrepareModeTitle();
                    }
                    if (args.hasUndoAction) {
                        this.showHasUndoAction();
                    }
                }
                this.addTopUndoButton(args);
                this.inherited(arguments);
            },

            hideHasUndoAction() {
                const elem = document.querySelector('#bp-player-area-' + this.player_id + ' .bp-has-undo-action-title');
                if (elem !== null) {
                    elem.classList.add('bx-hidden');
                }
            },

            showHasUndoAction() {
                const mainTitleElem = document.getElementById('pagemaintitletext');
                const elem = document.createElement('span');
                elem.classList.add('bp-has-undo-action-title');
                elem.innerText = _('Part of your prepared turn was undone');
                mainTitleElem.appendChild(elem);

                const playerParkTitle = document.querySelector('#bp-player-area-' + this.player_id + ' .bp-has-undo-action-title');
                if (playerParkTitle !== null) {
                    playerParkTitle.classList.remove('bx-hidden');
                }
            },

            hideModeTitle() {
                for (const elem of document.querySelectorAll('.bp-try-mode-title')) {
                    elem.classList.add('bx-hidden');
                }
                for (const elem of document.querySelectorAll('.bp-prepare-mode-title')) {
                    elem.classList.add('bx-hidden');
                }
            },

            addTryModeTitle() {
                const mainTitleElem = document.getElementById('pagemaintitletext');
                const elem = document.createElement('span');
                elem.classList.add('bp-try-mode-title');
                elem.innerText = _('Try mode');
                mainTitleElem.insertBefore(elem, mainTitleElem.firstChild);

                const playerAreaElem = document.querySelector('#bp-player-area-' + this.player_id + ' .bp-try-mode-title');
                playerAreaElem.classList.remove('bx-hidden');
            },

            addPrepareModeTitle() {
                const mainTitleElem = document.getElementById('pagemaintitletext');
                const elem = document.createElement('span');
                elem.classList.add('bp-prepare-mode-title');
                elem.innerText = _('Prepare mode');
                mainTitleElem.insertBefore(elem, mainTitleElem.firstChild);

                const playerAreaElem = document.querySelector('#bp-player-area-' + this.player_id + ' .bp-prepare-mode-title');
                playerAreaElem.classList.remove('bx-hidden');
            },

            mustShowChangeModeWarning() {
                const USER_PREF_MODE_WARNING_ID = 150;
                const USER_PREF_MODE_WARNING_VALUE_ENABLED = 1;
                return (this.prefs[USER_PREF_MODE_WARNING_ID].value == USER_PREF_MODE_WARNING_VALUE_ENABLED);
            },

            onButtonsStatePrivateInactiveTurn(args) {
                debug('onButtonsStatePrivateInactiveTurn');
                if (args.playerParksAreFull) {
                    return;
                }
                this.addTopButtonSecondary(
                    'bp-button-enter-play-loop',
                    _('Prepare next turn'),
                    () => {
                        const action = () => {
                            this.prepareNextTurnSeen = true;
                            this.serverAction('enterPlayLoop');
                        };
                        if (!this.mustShowChangeModeWarning() || this.prepareNextTurnSeen) {
                            action();
                        } else {
                            this.confirmationDialog(
                                this.format_string_recursive(
                                    _("You will be able to prepare your next turn. Only you can see what you do.${newline}Note that what you do might be automatically undone depending on what the other players do.${newline}You can disable this message in the Options below the game."),
                                    { 'newline': '<br/><br/>' }),
                                action
                            );
                        }
                    }
                );
            },
            onStatePrivateInactiveTurn(args) {
                debug('onStatePrivateInactiveTurn');
                this.placeTileInParkPositionIsValid = false;
                this.placeTileInParkPosition = null;
            },

            onButtonsStatePrivateChooseTileFromPlayerSupply(args) {
                debug('onButtonsPrivateChooseTileFromPlayerSupply');
            },
            onStatePrivateChooseTileFromPlayerSupply(args) {
                debug('onStatePrivateChooseTileFromPlayerSupply');
                this.placeTileInParkPositionIsValid = false;
                this.placeTileInParkPosition = null;
                this.addClickableToChooseTileFromPlayerSupply('chooseTileFromPlayerSupply', args.args.shapeIds);
            },
            addClickableToChooseTileFromPlayerSupply(serverAction, shapeIds) {
                for (const shapeId of shapeIds) {
                    const shapeElem = this.shapeMgr.getShapeElementById(shapeId);
                    this.addClickable(
                        shapeElem,
                        () => {
                            this.serverAction(serverAction, { shapeId: shapeId });
                        }, {
                        border: false,
                        outline: true,
                    });
                }
            },
            notif_MoveShapeToPlayerSupply(args) {
                const shapeElem = this.shapeMgr.getShapeElementById(args.args.shapeId);
                this.playerParkMgr.placePlacementShapeElementInParkGrid(args.args.playerId, shapeElem);
                this.playerSupplyMgr.moveShapeIdToPlayerSupply(args.args.shapeId, args.args.playerId);
            },
            notif_MoveShapeToSupplyBoard(args) {
                this.supplyBoardMgr.moveShapeIdToSupplyBoard(args.args.shapeId);
            },

            onButtonsStatePrivatePlaceTileInPark(args) {
                debug('onButtonsPrivatePlaceTileInPark');
                this.setupPlaceTileInParkButton('placeTileInPark');
            },
            setupPlaceTileInParkButton(serverAction) {
                this.addTopButtonPrimaryWithValid(
                    this.TOP_BUTTON_PLACE_IN_PARK_ID,
                    _('Place in park'),
                    _('Tile must have a valid position (within parks, no overlaps, orthogonally adjacent to other tiles)'),
                    () => {
                        this.serverAction(serverAction, this.placeTileInParkPosition).then(() => {
                            this.placeTileInParkPosition = null;
                            this.placeTileInParkPositionIsValid = false;
                        });
                    }
                );
                this.setTopButtonValid(this.TOP_BUTTON_PLACE_IN_PARK_ID, this.placeTileInParkPositionIsValid);
                this.playerParkMgr.setShapeMovementValid(this.player_id, this.placeTileInParkPositionIsValid);
            },
            onStatePrivatePlaceTileInPark(args) {
                debug('onStatePrivatePlaceTileInPark');
                this.addClickableToChooseTileFromPlayerSupply('changeChooseTileFromPlayerSupply', args.args.shapeIds);
                this.setupPlaceTileInParkState(args.args.selectedShapeId, args.args.validPositions, args.args.neighbourPositions);
            },
            setupPlaceTileInParkState(selectedShapeId, validPositions, neighbourPositions) {
                validPositions = this.uiStringToValidPositions(validPositions);
                this.updatePlaceTileInParkPosition(validPositions, this.placeTileInParkPosition);
                const shapeElem = this.shapeMgr.getShapeElementById(selectedShapeId);
                this.addSelected(shapeElem, { border: false, outline: true });
                this.playerParkMgr.addPlayerParkShapeMovement(
                    this.player_id,
                    shapeElem,
                    this.placeTileInParkPosition,
                    neighbourPositions,
                    new Set(Object.values(validPositions).filter((s) => s.parkTopX < 0).map((s) => s.parkId)),
                    (parkId, x, y, rotation, flipH, flipV) => {
                        this.updatePlaceTileInParkPosition(validPositions, {
                            parkId: parkId,
                            parkTopX: x,
                            parkTopY: y,
                            parkRotation: rotation,
                            parkHorizontalFlip: flipH,
                            parkVerticalFlip: flipV,
                        });
                    },
                    (accepElem) => {
                        const button = document.getElementById(this.TOP_BUTTON_PLACE_IN_PARK_ID);
                        if (button !== null && getComputedStyle(accepElem).opacity == 1) {
                            button.click();
                        }
                    }
                );
            },
            positionToKey(position) {
                return position.parkId + '|' +
                    position.parkTopX + '|' +
                    position.parkTopY + '|' +
                    position.parkRotation + '|' +
                    position.parkHorizontalFlip + '|' +
                    position.parkVerticalFlip;
            },
            uiStringToValidPositions(validPositions) {
                const ret = {};
                if (validPositions.length == 0) {
                    return ret;
                }
                for (const validPosition of validPositions.split(';')) {
                    const parts = validPosition.split('|');
                    const shapeId = parts[0];
                    const parkId = parts[1];
                    const parkTopX = parseInt(parts[2]);
                    const parkTopY = parseInt(parts[3]);
                    const parkRotation = parseInt(parts[4]);
                    const parkHorizontalFlip = (parts[5] == '1');
                    const parkVerticalFlip = (parts[6] == '1');
                    const overlappedIcons = parts[7].length == 0 ? [] : parts[7].split(',').map(icon => {
                        switch (icon) {
                            case 'P': return 'BP\\Park';
                            case 'S': return 'BP\\ShapeBearStatue';
                            case 'G': return 'BP\\ShapeGreenBase';
                            case 'W': return 'BP\\ShapeWhiteAnimalHouseBase';
                            case 'O': return 'BP\\ShapeOrangeEnclosureBase';
                        }
                        throw new Error('Unknown icon: ' + icon);
                    });
                    const statueShapeIds = parts[8].length == 0 ? [] : parts[8].split(',');
                    const pos = {
                        shapeId: shapeId,
                        parkId: parkId,
                        parkTopX: parkTopX,
                        parkTopY: parkTopY,
                        parkRotation: parkRotation,
                        parkHorizontalFlip: parkHorizontalFlip,
                        parkVerticalFlip: parkVerticalFlip,
                        overlappedIcons: overlappedIcons,
                        statueShapeIds: statueShapeIds,
                    };
                    ret[this.positionToKey(pos)] = pos;
                }
                return ret;
            },
            onUndoStatePrivatePlaceTileInPark() {
                this.placeTileInParkPositionIsValid = false;
                this.placeTileInParkPosition = null;
            },
            notif_MoveShapeToPlayerPark(args) {
                const shapeElem = this.shapeMgr.getShapeElementById(args.args.shapeId);
                this.playerParkMgr.moveShapeElementToPlayerPark(
                    args.args.playerId,
                    shapeElem,
                    args.args.parkId,
                    args.args.parkTopX,
                    args.args.parkTopY
                ).then(() => this.wait(100)).then(() =>
                    this.playerParkMgr.applyTransformToShape(
                        shapeElem,
                        args.args.parkRotation,
                        args.args.parkHorizontalFlip,
                        args.args.parkVerticalFlip)
                );
            },
            notif_MoveAchievementToPlayer(args) {
                this.achievementMgr.moveAchievementIdToPlayer(args.args.achievementId, args.args.playerId);
            },
            notif_MoveAchievementToSupplyBoard(args) {
                this.achievementMgr.moveAchievementIdToSupplyBoard(args.args.achievementId)
            },

            updatePlaceTileInParkPosition(validPositions, newPosition) {
                if (newPosition === null) {
                    newPosition = {};
                }
                newPosition = dojo.clone(newPosition);
                if (this.placeTileInParkPosition === null) {
                    this.placeTileInParkPosition = {
                        parkId: null,
                        parkTopX: null,
                        parkTopY: null,
                        parkRotation: 0,
                        parkHorizontalFlip: false,
                        parkVerticalFlip: false,
                    };
                }
                for (const key in newPosition) {
                    if (newPosition[key] === null) {
                        delete newPosition[key];
                    }
                }
                Object.assign(this.placeTileInParkPosition, newPosition);
                let validPosition = null;
                this.placeTileInParkPositionIsValid = false;
                const posKey = this.positionToKey(this.placeTileInParkPosition);
                if (posKey in validPositions) {
                    validPosition = validPositions[posKey];
                    this.placeTileInParkPositionIsValid = true;
                }
                this.setTopButtonValid(this.TOP_BUTTON_PLACE_IN_PARK_ID, this.placeTileInParkPositionIsValid);
                this.playerParkMgr.setShapeMovementValid(this.player_id, this.placeTileInParkPositionIsValid, validPosition);
                if (this.placeTileInParkPosition.parkId !== null) {
                    this.playerParkMgr.showParkControls(this.player_id);
                }
            },

            onButtonsStatePrivateChooseFromSupplyBoard(args) {
                const actionsElem = document.getElementById('generalactions');
                for (const icon of args.choosableIcons) {
                    const iconElem = this.shapeMgr.createIconElement(icon);
                    actionsElem.appendChild(iconElem);
                }
            },
            onStatePrivateChooseFromSupplyBoard(args) {
                debug('onStatePrivateChooseFromSupplyBoard');

                this.updateChoosableSupplyBoardShapes('chooseShapeFromSupplyBoard', args.args.choosableShapeIds);
                this.updateChoosableSupplyBoardParks('chooseParkFromSupplyBoard', args.args.choosableParkIds);
            },
            updateChoosableSupplyBoardParks(serverAction, choosableParkIds) {
                for (const parkId of choosableParkIds) {
                    const parkElem = this.parkMgr.getParkElementById(parkId);
                    this.addClickable(parkElem, () => {
                        this.serverAction(serverAction, { parkId: parkId });
                    });
                }
            },
            onStatePrivatePassTurnChooseFromSupplyBoard(args) {
                debug('onStatePrivatePassTurnChooseFromSupplyBoard');

                this.updateChoosableSupplyBoardShapes('chooseShapeFromSupplyBoardAndPass', args.args.choosableShapeIds);
            },
            updateChoosableSupplyBoardShapes(serverAction, choosableShapeIds) {
                for (const shapeId of choosableShapeIds) {
                    const shapeElem = this.shapeMgr.getShapeElementById(shapeId);
                    this.addClickable(
                        shapeElem, () => {
                            this.serverAction(serverAction, { shapeId: shapeId });
                        }, {
                        outline: true,
                        border: false,
                        childEventSelector: '.bp-grid-event',
                    });
                }
            },
            notif_UpdateSupplyShapesCount(args) {
                this.supplyBoardMgr.updateShapesViewerCounts(args.args.supplyShapesCount);
            },
            notif_UpdateSupplyAchievementsCount(args) {
                this.achievementMgr.updateSupplyAchievementsCount(args.args.achievementSupplyPile, args.args.supplyAchievementsCount);
            },

            onStatePrivatePlacePlayerPark(args) {
                debug('onStatePrivatePlacePlayerPark');
                this.setupPlacePlayerParkState('placePlayerPark', args);
                this.updateChoosableSupplyBoardParks('changeChooseParkFromSupplyBoard', args.args.choosableParkIds);
                this.updateChoosableSupplyBoardShapes('changeChooseShapeFromSupplyBoard', args.args.choosableShapeIds);
            },
            setupPlacePlayerParkState(serverAction, args) {
                const parkElem = this.parkMgr.getParkElementById(args.args.selectedParkId);
                this.addSelected(parkElem);
                const placementElements = this.playerParkMgr.addPlayerPlacementPark(
                    this.player_id,
                    args.args.newParkValidPositions,
                    args.args.playerParks,
                    args.args.selectedParkDefId
                );
                for (const elem of placementElements) {
                    this.addClickable(elem, () => {
                        this.serverAction(serverAction, {
                            posX: elem.dataset.posX,
                            posY: elem.dataset.posY,
                        });
                    });
                }
            },
            notif_ReplacePlayerParkArea(args) {
                debug('notif_ReplacePlayerParkArea');
                this.playerParkMgr.removePlayerPlacementPark(args.args.playerId);
                this.playerParkMgr.replacePlayerParkArea(args.args.playerId, args.args.playerParks);
            },
            notif_ReplaceSupplyBoardParks(args) {
                debug('notif_ReplaceSupplyBoardParks');
                this.supplyBoardMgr.replaceSupplyBoardParks(args.args.parks, args.args.supplyPilesCount);
            },

            onButtonsStatePrivateConfirmTurn(args) {
                debug('onButtonStatePrivateStateConfirmTurn');
                if (this.isCurrentPlayerActive()) {
                    this.addTopButtonImportant(
                        'bp-button-confirm-turn',
                        _('Confirm Turn'),
                        () => this.serverAction('confirmTurn')
                    );
                }
            },

            onButtonsStatePrivatePassTurnNoShape(args) {
                if (this.isCurrentPlayerActive()) {
                    this.addTopButtonImportant(
                        'bp-button-pass-turn',
                        _('Pass'),
                        () => this.serverAction('passTurn')
                    );
                }
            },

            notif_DisplayLastTurn(args) {
                debug('notif_DisplayLastTurn');
                this.displayLastTurn();
            },
            displayLastTurn() {
                document.getElementById('bp-display-last-turn').classList.remove('bx-hidden');
            },
        });
    });