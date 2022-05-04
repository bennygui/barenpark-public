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
        return declare("bp.ShapeMgr", null, {
            setup(gamedatas) {
                const elemCreationElem = gameui.getElementCreationElement();
                for (const shapeId in gamedatas.shapes) {
                    const shape = gamedatas.shapes[shapeId];
                    const shapeElem = this.createShapeElement(shape, true);
                    elemCreationElem.appendChild(shapeElem);
                }
            },

            createShapeElementFromShapeDefId(shapeDefId) {
                const element = document.createElement('div');
                element.classList.add('bp-shape');
                element.dataset.shapeDefId = shapeDefId;
                return element;
            },

            createShapeElement(shape, setId = false) {
                const element = this.createShapeElementFromShapeDefId(shape.shapeDefId);
                element.style.setProperty('--bp-base-grid-width', shape.baseGridWidth);
                element.style.setProperty('--bp-base-grid-height', shape.baseGridHeight);
                if (setId) {
                    element.id = 'bp-shape-id-' + shape.shapeId;
                }
                for (let y = 0; y < shape.shapeArray.length; ++y) {
                    for (let x = 0; x < shape.shapeArray[y].length; ++x) {
                        if (shape.shapeArray[y][x] == 0) {
                            continue;
                        }
                        const gridEvent = document.createElement('div');
                        gridEvent.classList.add('bp-grid-event');
                        gridEvent.dataset.gridX = x;
                        gridEvent.dataset.gridY = y;
                        element.appendChild(gridEvent);
                    }
                }
                const overlayElem = document.createElement('div');
                overlayElem.classList.add('bp-shape-overlay');
                element.appendChild(overlayElem);
                return element;
            },

            getShapeElementById(shapeId) {
                return document.getElementById('bp-shape-id-' + shapeId);
            },
        });
    });