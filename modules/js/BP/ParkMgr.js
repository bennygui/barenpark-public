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
        return declare("bp.ParkMgr", null, {
            PARK_NB_GRIDS: 4,

            setup(gamedatas) {
                const elemCreationElem = gameui.getElementCreationElement();
                for (const parkId in gamedatas.parks) {
                    const park = gamedatas.parks[parkId];
                    const parkElem = this.createParkElement(parkId, park.parkDefId, park.supplyPile, park.supplyPileCount, true);
                    elemCreationElem.appendChild(parkElem);
                    if (park.playerId !== null && park.posX == 0 && park.posY == 0) {
                        const parkEntryElem = this.createParkEntryElement(
                            park.parkDefId,
                            park.playerId,
                            gamedatas.players[park.playerId].parkSideIsFront
                        );
                        elemCreationElem.appendChild(parkEntryElem);
                    }
                }
            },

            createParkElementFromParkDefId(parkDefId) {
                const element = document.createElement('div');
                element.classList.add('bp-park');
                element.dataset.parkDefId = parkDefId;
                return element;
            },

            createParkElement(parkId, parkDefId, supplyPile = -1, supplyPileCount = 0, setId = false) {
                const element = this.createParkElementFromParkDefId(parkDefId);
                element.dataset.parkId = parkId;
                element.style.setProperty('--bp-supply-board-order', supplyPile);
                element.dataset.supplyPileCount = supplyPileCount;
                if (setId) {
                    element.id = 'bp-park-id-' + parkId;
                }

                // Create a grid to position shapes and a grid over it to react to click events
                for (let x = 0; x < this.PARK_NB_GRIDS; ++x) {
                    for (let y = 0; y < this.PARK_NB_GRIDS; ++y) {
                        const gridPos = document.createElement('div');
                        gridPos.classList.add('bp-park-grid-position');
                        const gridEvent = document.createElement('div');
                        gridEvent.classList.add('bp-park-grid-event');
                        const grids = [gridPos, gridEvent];
                        for (const grid of grids) {
                            grid.dataset.parkId = parkId;
                            grid.dataset.gridX = x;
                            grid.dataset.gridY = y;
                            element.appendChild(grid);
                        }
                    }
                }
                return element;
            },

            createParkEntryElement(parkDefId, playerId, parkSideIsFront) {
                const element = document.createElement('div');
                element.classList.add('bp-park-entry');
                element.dataset.parkDefId = parkDefId;
                element.dataset.playerId = playerId;
                if (parkSideIsFront) {
                    element.dataset.parkSide = 'top';
                } else {
                    element.dataset.parkSide = 'bottom';
                }
                return element;
            },

            getPlayerEntryElement(playerId) {
                return document.querySelector('.bp-park-entry[data-player-id="' + playerId + '"]');
            },

            getParkElementById(parkId) {
                return document.getElementById('bp-park-id-' + parkId);
            },
        });
    });