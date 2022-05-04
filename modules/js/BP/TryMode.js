/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * barenpark implementation : © Guillaume Benny bennygui@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define([
    "dojo",
    "dojo/_base/declare",
],
    function (dojo, declare) {
        return declare("bp.TryMode", null, {
            constructor() {
                // Format: ['notif', delay]
                this.notificationsToRegister.push(['NTF_SWAP_SHAPES', null]);
                this.notificationsToRegister.push(['NTF_CREATE_SHAPE', null]);
                this.notificationsToRegister.push(['NTF_DELETE_SHAPE', null]);
                this.notificationsToRegister.push(['NTF_CREATE_PARK', null]);
                this.notificationsToRegister.push(['NTF_DELETE_PARK', null]);
            },

            notif_SwapShapes(args) {
                const shapeElem1 = this.shapeMgr.getShapeElementById(args.args.shapeId1);
                const shapeElem2 = this.shapeMgr.getShapeElementById(args.args.shapeId2);
                const parentElem1 = shapeElem1.parentElement;
                const parentElem2 = shapeElem2.parentElement;
                shapeElem1.remove();
                shapeElem2.remove();
                parentElem1.appendChild(shapeElem2);
                parentElem2.appendChild(shapeElem1);
            },

            notif_CreateShape(args) {
                const elemCreationElem = gameui.getElementCreationElement();
                const shapeElem = this.shapeMgr.createShapeElement(args.args.shape, true);
                elemCreationElem.appendChild(shapeElem);
                this.supplyBoardMgr.addShapeIdToClassId(args.args.shape.shapeId, args.args.shape.classId);
                if (args.args.shape.shapeLocationId == gameui.SHAPE_LOCATION_ID_SUPPLY_BOARD) {
                    this.supplyBoardMgr.moveShapeIdToSupplyBoard(args.args.shape.shapeId, true).then(() => {
                        this.playerSupplyMgr.moveShapeIdToPlayerSupply(args.args.shape.shapeId, this.player_id);
                    });
                } else {
                    this.playerSupplyMgr.moveShapeIdToPlayerSupply(args.args.shape.shapeId, this.player_id, true);
                }
            },

            notif_DeleteShape(args) {
                const shapeElem = this.shapeMgr.getShapeElementById(args.args.shapeId)
                if (shapeElem !== null) {
                    shapeElem.remove();
                }
                this.supplyBoardMgr.removeShapeIdToClassId(args.args.shapeId);
            },

            notif_CreatePark(args) {
                const elemCreationElem = gameui.getElementCreationElement();
                const parkElem = this.parkMgr.createParkElement(args.args.park.parkId, args.args.park.parkDefId, args.args.park.supplyPile, args.args.park.supplyPileCount, true);
                elemCreationElem.appendChild(parkElem);
                this.supplyBoardMgr.moveParkIdToSupplyBoard(args.args.park.parkId, true);
            },

            notif_DeletePark(args) {
                const parkElem = this.parkMgr.getParkElementById(args.args.parkId);
                if (parkElem !== null) {
                    parkElem.remove();
                }
            },

            onUpdateActionButtonsdAfter(stateName, args) {
                this.inherited(arguments);
                this.addTopEnterTryModeButton(stateName);
            },

            addTopEnterTryModeButton(stateName) {
                let state = null
                for (const stateId in this.gamedatas.gamestates) {
                    const currentState = this.gamedatas.gamestates[stateId];
                    if (currentState.name == stateName) {
                        state = currentState;
                        break;
                    }
                }
                if (state === null || !state.possibleactions) {
                    return;
                }
                if (state.possibleactions.includes('enterTryMode')) {
                    this.addTopButtonSecondary(
                        'bp-button-enter-try-mode',
                        _('Enter "Try Mode"'),
                        () => {
                            const action = () => {
                                this.enterTryModeSeen = true;
                                this.serverAction('enterTryMode');
                            };
                            if (!this.mustShowChangeModeWarning() || this.enterTryModeSeen) {
                                action();
                            } else {
                                this.confirmationDialog(
                                    this.format_string_recursive(
                                        _('In "Try Mode", you will be able to try any shapes and any parks to better see the result.${newline}Only you can see what you try. Note that tiles and parks will be duplicated to allow the other players to take those tiles and parks.${newline}You can disable this message in the Options below the game.'),
                                        { 'newline': '<br/><br/>' }),
                                    action
                                );
                            }
                        });
                } else if (state.possibleactions.includes('exitTryMode')) {
                    this.addTopButtonImportant('bp-button-exit-try-mode', _('Exit "Try Mode"'), () => this.serverAction('exitTryMode'));
                }
            },

            onStatePrivateTryModeChooseTile(args) {
                this.addTryModeChoose('tryModeChooseTile', 'tryModeChoosePark', args);
            },
            addTryModeChoose(tileAction, parkAction, args) {
                if (args.args.choosableShapeIds) {
                    for (const shapeId of args.args.choosableShapeIds) {
                        const shapeElem = this.shapeMgr.getShapeElementById(shapeId);
                        this.addClickable(
                            shapeElem,
                            () => {
                                this.serverAction(tileAction, { shapeId: shapeId });
                            }, {
                            border: false,
                            outline: true,
                            childEventSelector: '.bp-grid-event',
                        });
                    }
                }

                for (const parkId of args.args.choosableParkIds) {
                    const parkElem = this.parkMgr.getParkElementById(parkId);
                    this.addClickable(parkElem, () => {
                        this.serverAction(parkAction, { parkId: parkId });
                    });
                }
            },
            onUndoStatePrivateTryModePlacePark() {
                this.placeTileInParkPositionIsValid = false;
                this.placeTileInParkPosition = null;
                this.setTopButtonValid(this.TOP_BUTTON_PLACE_IN_PARK_ID, this.placeTileInParkPositionIsValid);
                this.playerParkMgr.setShapeMovementValid(this.player_id, this.placeTileInParkPositionIsValid);
            },
            onUndoStatePrivateTryModeChooseTile() {
                this.placeTileInParkPositionIsValid = false;
                this.placeTileInParkPosition = null;
                this.setTopButtonValid(this.TOP_BUTTON_PLACE_IN_PARK_ID, this.placeTileInParkPositionIsValid);
                this.playerParkMgr.setShapeMovementValid(this.player_id, this.placeTileInParkPositionIsValid);
            },

            onButtonsStatePrivateTryModePlaceTile(args) {
                this.setupPlaceTileInParkButton('tryModePlaceTile');
            },
            onStatePrivateTryModePlaceTile(args) {
                this.addTryModeChoose('tryModeChangeChooseTile', 'tryModeChangeChoosePark', args);
                this.setupPlaceTileInParkState(args.args.selectedShapeId, args.args.validPositions, args.args.neighbourPositions);
            },
            onStatePrivateTryModePlacePark(args) {
                this.addTryModeChoose('tryModeChangeChooseTile', 'tryModeChangeChoosePark', args);
                this.setupPlacePlayerParkState('tryModePlacePark', args);
            },
        });
    });