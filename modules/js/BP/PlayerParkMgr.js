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
    g_gamethemeurl + "modules/js/BX/CSSTransition.js",
],
    function (dojo, declare) {
        return declare("bp.PlayerParkMgr", bx.Util, {
            ENTRY_POSITION: {
                posX: 0,
                posY: -1,
            },

            constructor(shapeMgr, parkMgr) {
                this.shapeMgr = shapeMgr;
                this.parkMgr = parkMgr;
                this.beforeTakeParkFcts = [];
                this.returnParkFcts = [];
                this.cssTransition = new bx.CSSTransition();
            },

            setup(gamedatas) {
                const parksPerPlayerId = {};
                Object.keys(gamedatas.players).forEach(playerId => parksPerPlayerId[playerId] = []);
                for (const parkId in gamedatas.parks) {
                    const park = gamedatas.parks[parkId];
                    if (park.playerId !== null) {
                        parksPerPlayerId[park.playerId].push(park);
                    }
                }

                for (const playerId in parksPerPlayerId) {
                    const entryElem = this.parkMgr.getPlayerEntryElement(playerId);
                    entryElem.remove();
                    const parksArea = this.getPlayerAreaElement(playerId);
                    parksArea.appendChild(entryElem);
                    this.replacePlayerParkArea(playerId, parksPerPlayerId[playerId], true);
                }

                for (const shapeId in gamedatas.shapes) {
                    const shape = gamedatas.shapes[shapeId];
                    if (shape.shapeLocationId == gameui.SHAPE_LOCATION_ID_PLAYER_PARK) {
                        const shapeElem = this.shapeMgr.getShapeElementById(shape.shapeId);
                        this.moveShapeElementToPlayerPark(
                            shape.playerId,
                            shapeElem,
                            shape.parkId,
                            shape.parkTopX,
                            shape.parkTopY,
                            true);
                        this.applyTransformToShape(shapeElem, shape.parkRotation, shape.parkHorizontalFlip, shape.parkVerticalFlip);
                    }
                }
            },

            registerBeforeTakePark(callback) {
                this.beforeTakeParkFcts.push(callback);
            },

            onBeforeTakePark(parkId, supplyPile) {
                for (const fct of this.beforeTakeParkFcts) {
                    fct(parkId, supplyPile);
                }
            },

            registerReturnPark(callback) {
                this.returnParkFcts.push(callback);
            },

            onReturnPark(parkId, isInstantaneous, afterReturnCallback) {
                return Promise.all(this.returnParkFcts.map(fct => fct(parkId, isInstantaneous)));
            },

            assignParkElementToGrid(parkElement, gridX, gridY) {
                let transition = false;
                if ('gridX' in parkElement.dataset &&
                    'gridY' in parkElement.dataset &&
                    (parkElement.dataset.gridX != gridX || parkElement.dataset.gridY != gridY)) {
                    transition = true;
                }
                parkElement.style.setProperty('--bp-park-area-grid-x', gridX);
                parkElement.style.setProperty('--bp-park-area-grid-y', gridY);
                parkElement.dataset.gridX = gridX;
                parkElement.dataset.gridY = gridY;
                if (transition) {
                    this.cssTransition.addElement(parkElement);
                }
            },

            getMinXMaxYFromPlayerParks(playerParks) {
                const Xs = playerParks.map(p => parseInt(p.posX));
                const Ys = playerParks.map(p => parseInt(p.posY));
                return {
                    minX: Math.min(...Xs, this.ENTRY_POSITION.posX),
                    maxY: Math.max(...Ys, this.ENTRY_POSITION.posY),
                };
            },

            getGridXYFromPosXY(posX, posY, minMax) {
                return {
                    x: 0 + parseInt(posX) - minMax.minX,
                    y: 0 - parseInt(posY) + minMax.maxY,
                };
            },

            placeElementInPlayerArea(playerId, element, posX, posY, minMax) {
                const gridPos = this.getGridXYFromPosXY(posX, posY, minMax);
                this.assignParkElementToGrid(element, gridPos.x, gridPos.y);
                const parksArea = this.getPlayerAreaElement(playerId);
                parksArea.appendChild(element);
            },

            resizePlayerArea(playerId, gridMaxX = 0, gridMaxY = 0) {
                const parksArea = this.getPlayerAreaElement(playerId);
                const parkElems = this.getAllPlayerAreaParkElement(playerId);
                const parkEntryElem = this.parkMgr.getPlayerEntryElement(playerId);

                // Resize park area based on content
                parksArea.style.width = (
                    (Math.max(gridMaxX, ...parkElems.map(c => parseInt(c.dataset.gridX))) + 1) *
                    gameui.getGridSize() * this.parkMgr.PARK_NB_GRIDS) + 'px';
                parksArea.style.height = (
                    (Math.max(gridMaxY, ...parkElems.map(c => parseInt(c.dataset.gridY))) + 1) *
                    gameui.getGridSize() * this.parkMgr.PARK_NB_GRIDS +
                    parkEntryElem.offsetHeight) + 'px';

                // Sort parks to fix z-index when a shape overlaps more than one park
                const sortPark = () => {
                    const parkElems = this.getAllPlayerAreaParkElement(playerId);
                    for (const parkElem of parkElems) {
                        parkElem.remove();
                        gameui.clearPos(parkElem);
                    }
                    parkElems.sort((p1, p2) => {
                        const cmp = parseInt(p2.dataset.gridY) - parseInt(p1.dataset.gridY);
                        if (cmp != 0) return cmp;
                        return (parseInt(p2.dataset.gridX) - parseInt(p1.dataset.gridX));
                    });
                    for (const parkElem of parkElems) {
                        parksArea.appendChild(parkElem);
                    }
                };
                if (gameui.isFastMode()) {
                    sortPark();
                } else {
                    this.cssTransition.callOnTransitionEnd(sortPark);
                }
            },

            getPlayerAreaElement(playerId) {
                return document.querySelector('#bp-player-area-' + playerId + ' .bp-player-area-parks');
            },

            getAllPlayerAreaElement(playerId) {
                const elems = this.getAllPlayerAreaParkElement(playerId);
                elems.push(this.parkMgr.getPlayerEntryElement(playerId));
                return elems;
            },

            getAllPlayerAreaParkElement(playerId) {
                return Array.from(this.getPlayerAreaElement(playerId).querySelectorAll('.bp-park'));
            },

            getPlayerControlsElement(playerId) {
                return document.querySelector('#bp-player-area-' + playerId + ' .bp-player-area-park-controls');
            },

            removeHoverShape() {
                const hoverShape = document.querySelector('.bp-shape-hover');
                if (hoverShape) {
                    hoverShape.remove();
                }
            },

            addPlayerParkShapeMovement(playerId, shapeElem, initialParkPosition, neighbourPositions, extraPlacementParkIdSet, callback, onAcceptClick) {
                const parksArea = this.getPlayerAreaElement(playerId);
                let parkId = null
                let rotation = 0;
                let flipH = false;
                let flipV = false;
                let x = null;
                let y = null;
                if (initialParkPosition !== null) {
                    parkId = initialParkPosition.parkId;
                    rotation = initialParkPosition.parkRotation;
                    flipH = initialParkPosition.parkHorizontalFlip;
                    flipV = initialParkPosition.parkVerticalFlip;
                    x = initialParkPosition.parkTopX;
                    y = initialParkPosition.parkTopY;
                }

                const doCallback = () => callback(parkId, x, y, this.normalizeRotation(rotation), flipH, flipV);
                const hoverElement = this.shapeMgr.createShapeElementFromShapeDefId(shapeElem.dataset.shapeDefId);
                hoverElement.classList.add('bp-shape-hover');

                // Click on grid
                for (const gridElem of Array.from(parksArea.querySelectorAll('.bp-grid-event'))) {
                    if (!gridElem.parentElement.classList.contains('bp-park')) {
                        continue;
                    }
                    if (parseInt(gridElem.dataset.gridX) < 0) {
                        if (!extraPlacementParkIdSet.has(gridElem.dataset.parkId)) {
                            gridElem.style.removeProperty('pointer-events');
                            continue;
                        } else {
                            gridElem.style.setProperty('pointer-events', 'all', 'important');
                        }
                    }
                    gameui.connect(gridElem, 'mouseover', () => {
                        hoverElement.remove()
                        gridElem.appendChild(hoverElement);
                    });
                    gameui.connect(gridElem, 'mouseout', () => {
                        hoverElement.remove()
                    });
                    gameui.addClickable(
                        gridElem,
                        () => {
                            if (gameui.isInterfaceLocked()) {
                                return;
                            }
                            this.showParkControls(playerId);
                            parkId = gridElem.dataset.parkId;
                            x = parseInt(gridElem.dataset.gridX);
                            y = parseInt(gridElem.dataset.gridY);
                            this.placementMoveShapeElementToPlayerPark(playerId, shapeElem, parkId, x, y);
                            doCallback();
                        }, {
                        border: false,
                        }
                    );
                }

                // Click on arrows
                const controlsElem = this.getPlayerControlsElement(playerId);
                const directions = ['up', 'down', 'left', 'right'];
                for (const direction of directions) {
                    const elem = controlsElem.querySelector('.bp-arrow-' + direction);
                    gameui.addClickable(
                        elem,
                        () => {
                            if (gameui.isInterfaceLocked() || parkId === null) {
                                return;
                            }
                            const newPos = neighbourPositions[parkId][x][y][direction];
                            if (newPos === null) {
                                return;
                            }
                            parkId = newPos.parkId;
                            x = newPos.x;
                            y = newPos.y;
                            this.placementMoveShapeElementToPlayerPark(playerId, shapeElem, parkId, x, y);
                            doCallback();
                        }, {
                        border: false,
                        }
                    );
                }

                // Click to flip and rotate
                const clickTransform = (classId, doIt) => {
                    gameui.addClickable(
                        controlsElem.querySelector('.bp-arrow-' + classId),
                        () => {
                            if (gameui.isInterfaceLocked() || parkId === null) {
                                return;
                            }
                            doIt();
                            this.applyTransformToShape(shapeElem, rotation, flipH, flipV);
                            this.applyTransformToShape(hoverElement, rotation, flipH, flipV);
                            doCallback();
                        }, {
                        border: false,
                        }
                    );
                };

                clickTransform('flip-h', () => {
                    const normalizedRot = this.normalizeRotation(rotation);
                    const flip = (normalizedRot != 90 && normalizedRot != 270);
                    flipH = (flip ? !flipH : flipH);
                    flipV = (flip ? flipV : !flipV);
                });
                clickTransform('flip-v', () => {
                    const normalizedRot = this.normalizeRotation(rotation);
                    const flip = (normalizedRot == 90 || normalizedRot == 270);
                    flipH = (flip ? !flipH : flipH);
                    flipV = (flip ? flipV : !flipV);
                });
                clickTransform('cw', () => rotation += 90);
                clickTransform('ccw', () => rotation -= 90);

                const accepElem = controlsElem.querySelector('.bp-arrow-accept');
                gameui.addClickable(accepElem, () => onAcceptClick(accepElem), { border: false });
            },

            setShapeMovementValid(playerId, isValid, validPosition = null) {
                const controlsElem = this.getPlayerControlsElement(playerId);
                if (controlsElem === null) {
                    return;
                }
                const accepElem = controlsElem.querySelector('.bp-arrow-accept');
                if (controlsElem === null) {
                    return;
                }
                if (isValid) {
                    accepElem.classList.remove('bp-invalid');
                } else {
                    accepElem.classList.add('bp-invalid');
                }
                const overlappedIconsElem = controlsElem.querySelector('.bp-overlapped-icons');
                overlappedIconsElem.innerHTML = '';
                if (validPosition !== null) {
                    for (const icon of validPosition.overlappedIcons) {
                        const iconElem = this.createIconElement(icon);
                        overlappedIconsElem.appendChild(iconElem);
                    }
                    // shapeId is always equal to shapeDefIf for statues so it's fine like this
                    for (const shapeId of validPosition.statueShapeIds) {
                        const shapeElem = this.shapeMgr.createShapeElementFromShapeDefId(shapeId);
                        overlappedIconsElem.appendChild(shapeElem);
                    }
                }
            },

            createIconElement(icon) {
                const elem = document.createElement('div');
                elem.classList.add('bp-icon-' + this.toDashCase(icon.replace(/.*\\/, '')));
                return elem;
            },

            moveShapeElementToPlayerPark(playerId, shapeElem, parkId, x, y, isInstantaneous = false) {
                this.placePlacementShapeElementInParkGrid(playerId, shapeElem);
                const parksArea = this.getPlayerAreaElement(playerId);
                const parkGrid = parksArea.querySelector('.bp-park[data-park-id="' + parkId + '"] .bp-park-grid-position[data-grid-x="' + x + '"][data-grid-y="' + y + '"]');
                if (shapeElem.parentElement != parkGrid) {
                    return gameui.slide(shapeElem, parkGrid, {
                        lockId: 'moveShapeElementToPlayerPark',
                        phantom: true,
                        isInstantaneous: isInstantaneous,
                    }).then(() => this.resizePlayerArea(playerId));
                }
                return new Promise((resolve, reject) => {
                    resolve();
                });
            },

            placementMoveShapeElementToPlayerPark(playerId, shapeElem, parkId, x, y) {
                this.moveShapeElementToPlayerPark(playerId, shapeElem, parkId, x, y).then(() => {
                    this.placePlacementShapeElementOverParkGrid(playerId, shapeElem, parkId, x, y);
                });
            },

            placePlacementShapeElementInParkGrid(playerId, shapeElem) {
                if ('placementParkId' in shapeElem.dataset) {
                    const parksArea = this.getPlayerAreaElement(playerId);
                    const parkGrid = parksArea.querySelector('.bp-park[data-park-id="' + shapeElem.dataset.placementParkId + '"] .bp-park-grid-position[data-grid-x="' + shapeElem.dataset.placementX + '"][data-grid-y="' + shapeElem.dataset.placementY + '"]');
                    gameui.changeParent(shapeElem, parkGrid);
                    dojo.style(shapeElem, { top: null, left: null, position: null });
                    delete shapeElem.dataset.placementParkId;
                    delete shapeElem.dataset.placementX;
                    delete shapeElem.dataset.placementY;
                }
            },

            placePlacementShapeElementOverParkGrid(playerId, shapeElem, parkId, x, y) {
                const parksArea = this.getPlayerAreaElement(playerId);
                gameui.changeParent(shapeElem, parksArea);
                shapeElem.dataset.placementParkId = parkId;
                shapeElem.dataset.placementX = x;
                shapeElem.dataset.placementY = y;
            },

            normalizeRotation(rotation) {
                while (rotation >= 360) {
                    rotation -= 360;
                }
                while (rotation < 0) {
                    rotation += 360;
                }
                return rotation;
            },

            applyTransformToShape(shapeElem, rotation, flipH, flipV) {
                const transform = [];
                const normalizedRot = this.normalizeRotation(rotation);
                if (normalizedRot == 90) {
                    transform.push('translate(-50%, -50%) rotate(' + rotation + 'deg) translate(50%, -50%)');
                } else if (normalizedRot == 180 || normalizedRot == 0) {
                    transform.push('rotate(' + rotation + 'deg)');
                } else if (normalizedRot == 270) {
                    transform.push('translate(-50%, -50%) rotate(' + rotation + 'deg) translate(-50%, 50%)');
                }
                if (flipH === true || flipH === 1 || flipH === "1") {
                    transform.push('scaleX(-1)');
                }
                if (flipV === true || flipV === 1 || flipV === "1") {
                    transform.push('scaleY(-1)');
                }

                shapeElem.style.transform = transform.join(' ');
            },

            addPlayerPlacementPark(playerId, newParkValidPositions, playerParks, selectedParkDefId) {
                const parksArea = this.getPlayerAreaElement(playerId);
                for (const parkElem of this.getAllPlayerAreaElement(playerId)) {
                    const newX = 1 + parseInt(parkElem.dataset.gridX);
                    const newY = 1 + parseInt(parkElem.dataset.gridY);
                    this.assignParkElementToGrid(parkElem, newX, newY);
                }

                const placementElements = [];
                const minMax = this.getMinXMaxYFromPlayerParks(playerParks);
                --minMax.minX;
                ++minMax.maxY;
                for (const pos of newParkValidPositions) {
                    const placementElem = this.parkMgr.createParkElementFromParkDefId(selectedParkDefId);
                    placementElem.classList.add('bp-park-placement');
                    placementElem.dataset.posX = pos.posX;
                    placementElem.dataset.posY = pos.posY;
                    this.placeElementInPlayerArea(playerId, placementElem, pos.posX, pos.posY, minMax);
                    placementElements.push(placementElem);
                }

                setTimeout(() => {
                    parksArea.style.setProperty('--bp-placement-parks-visible', 1)
                }, 500);

                this.resizePlayerArea(playerId);
                return placementElements;
            },

            removePlayerPlacementPark(playerId) {
                const parksArea = this.getPlayerAreaElement(playerId);
                if (parksArea === null) {
                    return;
                }
                parksArea.style.setProperty('--bp-placement-parks-visible', 0);
                let hasPlacementPark = false;
                for (const placementPark of parksArea.querySelectorAll('.bp-park-placement')) {
                    hasPlacementPark = true;
                    placementPark.remove();
                }
                if (hasPlacementPark) {
                    for (const parkElem of this.getAllPlayerAreaElement(playerId)) {
                        const newX = parseInt(parkElem.dataset.gridX) - 1;
                        const newY = parseInt(parkElem.dataset.gridY) - 1;
                        this.assignParkElementToGrid(parkElem, newX, newY);
                    }
                    this.resizePlayerArea(playerId);
                }
            },

            replacePlayerParkArea(playerId, parks, isInstantaneous = false) {
                const flagClass = 'bp-park-delete-flag';
                const minMax = this.getMinXMaxYFromPlayerParks(parks);

                for (const parkElem of this.getAllPlayerAreaParkElement(playerId)) {
                    parkElem.classList.add(flagClass);
                }

                let gridMaxX = 0;
                let gridMaxY = 0;
                const parksArea = this.getPlayerAreaElement(playerId);
                for (const park of parks) {
                    const parkElem = this.parkMgr.getParkElementById(park.parkId);
                    parkElem.classList.remove(flagClass);
                    const gridPos = this.getGridXYFromPosXY(park.posX, park.posY, minMax);
                    gridMaxX = Math.max(gridMaxX, gridPos.x);
                    gridMaxY = Math.max(gridMaxY, gridPos.y);
                    this.assignParkElementToGrid(parkElem, gridPos.x, gridPos.y);
                    if (parkElem.closest('.bp-player-area-parks') === null) {
                        const changeParent = () => {
                            parkElem.remove();
                            parksArea.appendChild(parkElem);
                            this.resizePlayerArea(playerId, gridMaxX, gridMaxY);
                        };
                        this.onBeforeTakePark(park.parkId, park.supplyPile);
                        if (isInstantaneous) {
                            changeParent();
                        } else {
                            gameui.slide(parkElem, parksArea, {
                                lockId: 'replacePlayerParkArea',
                                phantom: false,
                                pos: {
                                    x: gameui.getGridSize() * this.parkMgr.PARK_NB_GRIDS * gridPos.x,
                                    y: gameui.getGridSize() * this.parkMgr.PARK_NB_GRIDS * gridPos.y,
                                },
                                clearPos: true,
                                changeParent: false,
                            }).then(changeParent);
                        }
                    }
                }

                const parkEntryElem = this.parkMgr.getPlayerEntryElement(playerId);
                const gridPos = this.getGridXYFromPosXY(this.ENTRY_POSITION.posX, this.ENTRY_POSITION.posY, minMax);
                this.assignParkElementToGrid(parkEntryElem, gridPos.x, gridPos.y);

                for (const parkElem of this.getAllPlayerAreaParkElement(playerId)) {
                    if (parkElem.classList.contains(flagClass)) {
                        parkElem.classList.remove(flagClass);
                        this.onReturnPark(parkElem.dataset.parkId, isInstantaneous)
                            .then(() => this.resizePlayerArea(playerId, gridMaxX, gridMaxY));
                    }
                }
                this.resizePlayerArea(playerId, gridMaxX, gridMaxY);
            },

            showParkControls(playerId) {
                const elem = this.getPlayerControlsElement(playerId);
                elem.classList.add('bp-visible-controls');
            },

            hideParkControls(playerId) {
                const elem = this.getPlayerControlsElement(playerId);
                if (elem === null) {
                    return;
                }
                elem.classList.remove('bp-visible-controls');
            },
        });
    });