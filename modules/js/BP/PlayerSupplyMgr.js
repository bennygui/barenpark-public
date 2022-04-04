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
        return declare("bp.PlayerSupplyMgr", null, {
            constructor(shapeMgr) {
                this.shapeMgr = shapeMgr;
            },

            setup(gamedatas) {
                for (const shapeId in gamedatas.shapes) {
                    const shape = gamedatas.shapes[shapeId];
                    if (shape.shapeLocationId == gameui.SHAPE_LOCATION_ID_PLAYER_SUPPLY) {
                        this.moveShapeIdToPlayerSupply(shapeId, shape.playerId, true);
                    }
                }
            },

            getPlayerSupplyElement(playerId) {
                return document.querySelector('#bp-player-area-' + playerId + ' .bp-player-area-supply');
            },

            moveShapeIdToPlayerSupply(shapeId, playerId, isInstantaneous = false) {
                const playerSupply = this.getPlayerSupplyElement(playerId);
                const shapeElem = this.shapeMgr.getShapeElementById(shapeId);
                gameui.slide(shapeElem, playerSupply, {
                    phantom: true,
                    isInstantaneous: isInstantaneous,
                }).then(() => shapeElem.style.removeProperty('transform'));
            },
        });
    });