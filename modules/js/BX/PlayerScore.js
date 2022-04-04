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
        "ebg/counter",
    ],
    function(dojo, declare) {
        return declare("bx.PlayerScore", null, {
            constructor() {
                this.notificationsToRegister.push(['NTF_UPDATE_PLAYER_SCORE', -1]);

                this.playerScoreCounter = {};
            },

            setup(gamedatas) {
                this.inherited(arguments);
                for (const playerId in gamedatas.players) {
                    const playerInfo = gamedatas.players[playerId];
                    this.playerScoreCounter[playerId] = new ebg.counter();
                    this.playerScoreCounter[playerId].create('player_score_' + playerId);
                    this.playerScoreCounter[playerId].setValue(playerInfo.score);
                }
            },

            notif_UpdatePlayerScore(args) {
                this.playerScoreCounter[args.args.playerId].toValue(args.args.playerScore);
                if (args.args.setNotificationDuration !== false) {
                    this.notifqueue.setSynchronousDuration(1);
                }
            },
        });
    });