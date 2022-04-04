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
var debug = isDebug ? console.info.bind(window.console) : function() {};

define([
        "dojo",
        "dojo/_base/declare",
    ],
    function(dojo, declare) {
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
                return element;
            },

            getShapeElementById(shapeId) {
                return document.getElementById('bp-shape-id-' + shapeId);
            },

            isShapeTransparentAtPos(shapeId, x, y) {
                x = Math.floor(x / gameui.GRID_SIZE);
                y = Math.floor(y / gameui.GRID_SIZE);
                if (y < 0) {
                    y = 0;
                }
                if (x < 0) {
                    x = 0;
                }
                const shapeArray = gameui.gamedatas.shapes[shapeId].shapeArray;
                if (y >= shapeArray.length) {
                    y = shapeArray.length - 1;
                }
                if (x >= shapeArray[y].length) {
                    x = shapeArray[y].length - 1;
                }
                return (shapeArray[y][x] == 0);
            },
        });
    });