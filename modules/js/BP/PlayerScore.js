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
    g_gamethemeurl + "modules/js/BX/PlayerScore.js",
],
    function (dojo, declare) {
        return declare("bp.PlayerScore", bx.PlayerScore, {
            notif_UpdatePlayerScore(args) {
                if (args.args.score && args.args.shapeId) {
                    const shapeElem = this.shapeMgr.getShapeElementById(args.args.shapeId);
                    const parkElem = shapeElem.closest('.bp-park');
                    if (parkElem !== null) {
                        this.displayBigNumberOnElement(
                            parkElem,
                            args.args.score,
                            {
                                color: this.gamedatas.players[args.args.playerId].color,
                                changeParent: true,
                            }
                        );
                        this.notifqueue.setSynchronousDuration(800);
                        args.args['setNotificationDuration'] = false;
                    }
                }
                if (args.args.score && args.args.achievementId) {
                    const achievementElem = this.achievementMgr.getAchievementElementById(args.args.achievementId);
                    if (achievementElem !== null) {
                        this.displayBigNumberOnElement(
                            achievementElem,
                            args.args.score,
                            {
                                color: this.gamedatas.players[args.args.playerId].color,
                            }
                        );
                        this.notifqueue.setSynchronousDuration(800);
                        args.args['setNotificationDuration'] = false;
                    }
                }
                this.inherited(arguments);
            },
        });
    });