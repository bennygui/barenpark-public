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
    g_gamethemeurl + "modules/js/BX/Util.js",
],
    function (dojo, declare) {
        return declare("bp.SupplyBoardMgr", bx.Util, {
            OVERLAP_DISPLAY_GRID: 4,

            constructor(shapeMgr, parkMgr) {
                this.shapeMgr = shapeMgr;
                this.parkMgr = parkMgr;
                this.shapeIdToClassId = {};
            },

            setup(gamedatas) {
                this.setupShapes(gamedatas);
                this.setupParks(gamedatas);
                this.setupShapesViewer(gamedatas);
            },

            setupShapes(gamedatas) {
                const shapesPerClassId = {};
                for (const shapeId in gamedatas.shapes) {
                    const shape = gamedatas.shapes[shapeId];
                    this.addShapeIdToClassId(shapeId, shape.classId);
                    if (!(shape.classId in shapesPerClassId)) {
                        shapesPerClassId[shape.classId] = [];
                    }
                    shapesPerClassId[shape.classId].push(shape);
                }
                for (const classId in shapesPerClassId) {
                    shapesPerClassId[classId].sort((s1, s2) => {
                        let cmp = (s2.shapeScore - s1.shapeScore);
                        if (cmp == 0) {
                            cmp = (s1.shapeId - s2.shapeId);
                        }
                        return cmp;
                    });
                    for (let i = 0; i < shapesPerClassId[classId].length; ++i) {
                        const shape = shapesPerClassId[classId][i];
                        const shapeElem = this.shapeMgr.getShapeElementById(shape.shapeId);
                        const order = shapesPerClassId[classId].length - i;
                        shapeElem.style.setProperty('--bp-supply-board-order', order);
                        shapeElem.style.setProperty('--bp-supply-board-x', Math.floor((order - 1) % this.OVERLAP_DISPLAY_GRID));
                        shapeElem.style.setProperty('--bp-supply-board-y', Math.floor((order - 1) / this.OVERLAP_DISPLAY_GRID));
                    }
                }

                for (const shapeId in gamedatas.shapes) {
                    const shape = gamedatas.shapes[shapeId];
                    if (shape.shapeLocationId == gameui.SHAPE_LOCATION_ID_SUPPLY_BOARD) {
                        this.moveShapeIdToSupplyBoard(shapeId, true);
                    }
                }
            },

            setupShapesViewer(gamedatas) {
                const preventEvent = (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    return false;
                };
                for (const shapesCount of gamedatas.supplyShapesCount) {
                    const button = this.getSupplyBoardShapeButtonForClassId(shapesCount.classId);
                    if (button === null) continue;
                    const supplyElem = this.getSupplyBoardElemForClassId(shapesCount.classId);
                    const buttonDown = () => supplyElem.classList.add('bp-shape-spread');
                    const buttonUp = () => supplyElem.classList.remove('bp-shape-spread');
                    dojo.connect(button, 'touchstart', buttonDown);
                    dojo.connect(button, 'mousedown', buttonDown);
                    dojo.connect(button, 'touchend', buttonDown);
                    dojo.connect(button, 'mouseup', buttonUp);
                    dojo.connect(button, 'mouseleave', buttonUp);
                    dojo.connect(button, 'oncontextmenu', preventEvent);
                }
                this.updateShapesViewerCounts(gamedatas.supplyShapesCount);
            },

            addShapeIdToClassId(shapeId, classId) {
                this.shapeIdToClassId[shapeId] = classId;
            },

            removeShapeIdToClassId(shapeId) {
                delete this.shapeIdToClassId[shapeId];
            },

            updateShapesViewerCounts(supplyShapesCount) {
                for (const shapesCount of supplyShapesCount) {
                    const button = this.getSupplyBoardShapeButtonForClassId(shapesCount.classId);
                    if (button === null) continue;
                    button.querySelector('span').innerText = shapesCount.count + 'x';
                }
            },

            getSupplyBoardShapeButtonForClassId(classId) {
                const id = 'bp-supply-board-button-' + this.toDashCase(classId.replace(/.*\\/, ''));
                return document.getElementById(id);
            },

            getSupplyBoardElemForShapeId(shapeId) {
                return this.getSupplyBoardElemForClassId(this.shapeIdToClassId[shapeId]);
            },

            getSupplyBoardElemForClassId(classId) {
                const id = 'bp-supply-' + this.toDashCase(classId.replace(/.*\\/, ''));
                return document.getElementById(id);
            },

            moveShapeIdToSupplyBoard(shapeId, isInstantaneous = false) {
                const supplyBoardElem = this.getSupplyBoardElemForShapeId(shapeId);
                const shapeElem = this.shapeMgr.getShapeElementById(shapeId);
                return gameui.slide(shapeElem, supplyBoardElem, {
                    phantom: true,
                    isInstantaneous: isInstantaneous,
                }).then(() => {
                    shapeElem.style.removeProperty('transform');
                    shapeElem.style.removeProperty('--bp-park-area-grid-x');
                    shapeElem.style.removeProperty('--bp-park-area-grid-y');
                    delete shapeElem.dataset.placementParkId;
                    delete shapeElem.dataset.placementX;
                    delete shapeElem.dataset.placementY;
                });
            },

            setupParks(gamedatas) {
                const standInSupplyPile = [0, 1];
                for (const parkId in gamedatas.parks) {
                    const park = gamedatas.parks[parkId];
                    if (park.playerId === null) {
                        standInSupplyPile.splice(standInSupplyPile.indexOf(parseInt(park.supplyPile)), 1);
                        this.moveParkIdToSupplyBoard(parkId, true);
                    }
                }
                for (const supplyPile of standInSupplyPile) {
                    if (gamedatas.supplyPilesCount[supplyPile] > 0) {
                        this.createAndPlaceParkStandInElement(supplyPile, gamedatas.supplyPilesCount[supplyPile]);
                    }
                }
                this.supplyPilesCount = gamedatas.supplyPilesCount;
                for (const supplyPile in gamedatas.supplyPilesCount) {
                    if (gamedatas.supplyPilesCount[supplyPile] == 0) {
                        this.createAndPlaceParkEmptyPileElement(supplyPile);
                    }
                }
            },

            getSupplyBoardElemForParks() {
                return document.getElementById('bp-supply-park');
            },

            getSupplyStandInElement(supplyBoardOrder) {
                const elem = this.getElementInSupplyPile(supplyBoardOrder);
                if (elem === null) {
                    return null;
                }
                if (elem.classList.contains('bp-park-stand-in') || elem.classList.contains('bp-park-empty-pile')) {
                    return elem;
                }
                return null;
            },

            getElementInSupplyPile(supplyBoardOrder) {
                for (const elem of this.getSupplyBoardElemForParks().querySelectorAll('.bp-park')) {
                    if (elem.style.getPropertyValue('--bp-supply-board-order') == supplyBoardOrder) {
                        return elem;
                    }
                }
                return null;
            },

            replaceParkInPileWithStandIn(parkId, supplyPile) {
                if (supplyPile === null) {
                    return;
                }
                if (parkId !== null) {
                    const parkElem = this.parkMgr.getParkElementById(parkId);
                    if (parkElem.parentElement != this.getSupplyBoardElemForParks()) {
                        return;
                    }
                    gameui.fixAbsolutePositionInPlace(parkElem);
                }
                if (this.supplyPilesCount[supplyPile] <= 1) {
                    this.createAndPlaceParkEmptyPileElement(supplyPile);
                } else {
                    this.createAndPlaceParkStandInElement(supplyPile, this.supplyPilesCount[supplyPile] - 1);
                }
            },

            createAndPlaceParkStandInElement(supplyPile, supplyPileCount) {
                const element = document.createElement('div');
                element.classList.add('bp-park');
                element.classList.add('bp-park-stand-in');
                element.style.setProperty('--bp-supply-board-order', supplyPile);
                element.dataset.supplyPileCount = supplyPileCount;
                const supplyElem = this.getSupplyBoardElemForParks();
                supplyElem.appendChild(element);
                return element;
            },

            createParkEmptyPileElement(supplyPile) {
                const element = document.createElement('div');
                element.classList.add('bp-park');
                element.classList.add('bp-park-empty-pile');
                element.style.setProperty('--bp-supply-board-order', supplyPile);
                element.dataset.supplyPileCount = 0;
                return element;
            },

            createAndPlaceParkEmptyPileElement(supplyPile) {
                const element = this.createParkEmptyPileElement(supplyPile);
                const supplyElem = this.getSupplyBoardElemForParks();
                supplyElem.appendChild(element);
                return element;
            },

            moveParkIdToSupplyBoard(parkId, isInstantaneous = false) {
                const parkElem = this.parkMgr.getParkElementById(parkId);
                const supplyElem = this.getSupplyBoardElemForParks();
                const standInElem = this.getSupplyStandInElement(parkElem.style.getPropertyValue('--bp-supply-board-order'));
                if (standInElem !== null) {
                    gameui.fixAbsolutePositionInPlace(standInElem);
                }
                return gameui.slide(parkElem, supplyElem, {
                    lockId: 'moveParkIdToSupplyBoard',
                    phantom: true,
                    isInstantaneous: isInstantaneous,
                }).then(() => {
                    if (standInElem !== null) {
                        standInElem.remove();
                    }
                });
            },

            replaceSupplyBoardParks(parks, supplyPilesCount) {
                this.supplyPilesCount = supplyPilesCount;
                for (let supplyPile = 0; supplyPile < supplyPilesCount.length; ++supplyPile) {
                    const elem = this.getElementInSupplyPile(supplyPile);
                    if (elem === null) {
                        if (supplyPilesCount[supplyPile] <= 1) {
                            standInElem = this.createAndPlaceParkEmptyPileElement(supplyPile);
                        } else {
                            standInElem = this.createAndPlaceParkStandInElement(supplyPile, supplyPilesCount[supplyPile] - 1);
                        }
                    }
                }

                const supplyElem = this.getSupplyBoardElemForParks();
                for (const park of parks) {
                    const standInElem = this.getSupplyStandInElement(park.supplyPile);
                    if (standInElem === null) {
                        const elem = this.getElementInSupplyPile(park.supplyPile);
                        if (elem.dataset.parkId != park.parkId) {
                            if (elem !== null) {
                                elem.remove();
                            }
                            const parkElem = this.parkMgr.createParkElement(park.parkId, park.parkDefId, park.supplyPile, park.supplyPileCount, true);
                            supplyElem.appendChild(parkElem);
                        }
                    } else {
                        // Don't create park if it already exist
                        if (this.parkMgr.getParkElementById(park.parkId) === null) {
                            const parkElem = this.parkMgr.createParkElement(park.parkId, park.parkDefId, park.supplyPile, park.supplyPileCount, true);
                            gameui.flipAndReplace(standInElem, parkElem);
                        }
                    }
                }
            },
        });
    });