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
        return declare("bp.LastMove", null, {
            NEW_MOVE_CLASS: 'bp-new-move',

            constructor() {
                this.notificationsToRegister.push(['NTF_UPDATE_SAVED_MOVE_NUMBER', null]);
            },

            setup(gamedatas) {
                this.inherited(arguments);
                this.updateSavedMoveNumber(gamedatas.savedMoveNumber);
            },

            notif_UpdateSavedMoveNumber(args) {
                this.updateSavedMoveNumber(args.args.savedMoveNumber);
            },

            updateSavedMoveNumber(savedMoveNumber) {
                for (const elem of document.querySelectorAll('.' + this.NEW_MOVE_CLASS)) {
                    elem.classList.remove(this.NEW_MOVE_CLASS);
                }
                if (this.isSpectator) {
                    return;
                }
                let lastSeenMove = 0;
                if (this.player_id in savedMoveNumber['player']) {
                    lastSeenMove = parseInt(savedMoveNumber['player'][this.player_id]);
                }
                for (const mgr in savedMoveNumber) {
                    for (const id in savedMoveNumber[mgr]) {
                        if (parseInt(savedMoveNumber[mgr][id]) <= lastSeenMove) {
                            continue;
                        }
                        let elem = null;
                        switch (mgr) {
                            case 'shape':
                                elem = this.shapeMgr.getShapeElementById(id);
                                break;
                            case 'park':
                                elem = this.parkMgr.getParkElementById(id);
                                break;
                            case 'achievement':
                                elem = this.achievementMgr.getAchievementElementById(id);
                                break;
                        }
                        if (elem !== null) {
                            elem.classList.add(this.NEW_MOVE_CLASS);
                        }
                    }
                }
            },
        });
    });