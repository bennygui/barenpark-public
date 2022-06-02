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
    "ebg/core/gamegui",
    g_gamethemeurl + "modules/js/BX/Animation.js",
    g_gamethemeurl + "modules/js/BX/Button.js",
    g_gamethemeurl + "modules/js/BX/Preference.js",
    g_gamethemeurl + "modules/js/BX/UI.js",
    g_gamethemeurl + "modules/js/BX/Util.js",
],
    function (dojo, declare) {
        return declare("bx.Game", [ebg.core.gamegui, bx.Animation, bx.Button, bx.Preference, bx.UI, bx.Util], {
            constructor() {
                // Format: ['notif', delay]
                this.notificationsToRegister = [
                    ['NTF_CHANGE_PRIVATE_STATE', 1],
                    ['NTF_UNDO_PRIVATE_STATE', 1],
                ];
                this.htmlTextForLogKeys = [];
            },

            setup(gamedatas) {
                this.setupBrowserDetection();
                this.setupNotifications();
            },

            // [Undocumented] Override BGA framework functions to call onLoadingComplete when loading is done
            setLoader(value, max) {
                this.inherited(arguments);
                if (!this.isLoadingComplete && value >= 100) {
                    this.isLoadingComplete = true;
                    this.onLoadingComplete();
                }
            },

            onLoadingComplete() {
                this.inherited(arguments);
            },

            updatePlayerOrdering() {
                this.inherited(arguments);
            },

            onEnteringState(stateName, args) {
                if (args.args && args.args._private && args.args._private.privateStateId && args.args._private.privateStateId != args.id) {
                    this.setPrivateState(args.args._private.privateStateId, args.args._private);
                } else if (this.gamedatas.gamestate.type != 'game') {
                    if (this.previousStateName != stateName || !this.areObjectsEqual(this.previousStateArgs, args.args)) {
                        this.onStateChangedInternal(stateName, args);
                    }
                    this.previousStateName = stateName;
                    this.previousStateArgs = dojo.clone(args.args);
                }
            },

            onLeavingState(stateName) { },

            onStateChangedInternal(stateName, args) {
                this.onStateChangedBefore(stateName, args);
                this.onStateChangedNow(stateName, args);
                const functionName = this.toCamelCase('ON_' + stateName);
                if (functionName in this) {
                    debug('onStateChangedInternal: ' + stateName + ' (calling ' + functionName + ')');
                    this[functionName](args);
                } else {
                    debug('onStateChangedInternal: ' + stateName + ' (no function named ' + functionName + ')');
                }
                this.onStateChangedAfter(stateName, args);
            },
            onStateChangedBefore(stateName, args) { },
            onStateChangedNow(stateName, args) { },
            onStateChangedAfter(stateName, args) { },

            onUpdateActionButtons(stateName, args) {
                this.onUpdateActionButtonsBefore(stateName, args);
                this.onUpdateActionButtonsNow(stateName, args);
                const functionName = this.toCamelCase('ON_BUTTONS_' + stateName);
                if (functionName in this) {
                    debug('onUpdateActionButtons: ' + stateName + ' (calling ' + functionName + ')');
                    this[functionName](args);
                } else {
                    debug('onUpdateActionButtons: ' + stateName + ' (no function named ' + functionName + ')');
                }
                this.onUpdateActionButtonsdAfter(stateName, args);
            },
            onUpdateActionButtonsBefore(stateName, args) { },
            onUpdateActionButtonsNow(stateName, args) { },
            onUpdateActionButtonsdAfter(stateName, args) { },

            // @Override: This is a built-in BGA method, overriden to inject html into log items
            format_string_recursive(log, args) {
                try {
                    if (log && args && !args.processed) {
                        args.processed = true;
                        for (const key of this.htmlTextForLogKeys) {
                            if (!(key in args)) {
                                args[key] = '';
                            } else {
                                args[key] = this.getHtmlTextForLogArg(key, args[key]);
                            }
                        }
                    }
                } catch (e) {
                    console.error(log, args, "Exception thrown", e.stack);
                }
                return this.inherited(arguments);
            },


            setupBrowserDetection() {
                if (!navigator) {
                    return;
                }
                if (
                    (
                        navigator.platform
                        && /iPad|iPhone|iPod/.test(navigator.platform)
                    )
                    ||
                    (
                        /iPad|iPhone|iPod/.test(navigator.userAgent)
                        && !window.MSStream
                    )
                    ||
                    (
                        // Also include Safari on MacOS
                        /^((?!chrome|android).)*safari/i.test(navigator.userAgent)
                    )
                ) {
                    document.body.classList.add('bx-browser-is-ios');
                }
            },

            getHtmlTextForLogArg(key, value) {
                return '';
            },

            setupNotifications() {
                for (const notif of this.notificationsToRegister) {
                    const notifId = notif[0];
                    const delay = notif[1];
                    const functionName = 'notif_' + this.toPascalCase(notifId.replace(/^NTF_/, ''));
                    debug('Registering notification ' + notifId + ' (' + functionName + ')');
                    dojo.subscribe(notifId, this, args => this[functionName](args));
                    if (delay !== null) {
                        if (delay < 0) {
                            this.notifqueue.setSynchronous(notifId);
                        } else {
                            this.notifqueue.setSynchronous(notifId, delay);
                        }
                    }
                }
            },

            notif_ChangePrivateState(notif) {
                this.setPrivateState(notif.args.stateId, notif.args.stateArgs);
            },

            notif_UndoPrivateState(notif) {
                const stateName = this.gamedatas.gamestates[notif.args.stateId].name;
                const functionName = this.toCamelCase('ON_UNDO_' + stateName);
                if (functionName in this) {
                    debug('notif_UndoPrivateState: ' + stateName + ' (calling ' + functionName + ')');
                    this[functionName]();
                } else {
                    debug('notif_UndoPrivateState: ' + stateName + ' (no function named ' + functionName + ')');
                }
            },

            setPrivateState(stateId, privateStateArgs) {
                const privateState = this.gamedatas.gamestates[stateId];
                const privateArgs = dojo.clone(privateState);
                // Switch from 'privateState' to 'activeplayer' so that the player is considered active
                privateArgs.type = 'activeplayer';
                if (!privateArgs.descriptionmyturn) {
                    privateArgs.descriptionmyturn = privateArgs.description;
                }
                privateArgs.args = dojo.clone(privateStateArgs);
                delete privateArgs.args.privateStateId;
                privateArgs.id = stateId;
                // Setup for "${you}" parameter (Code copied from updatePageTitle())
                const playerColor = this.gamedatas.players[this.player_id].color;
                let playerBack = ''
                if (this.gamedatas.players[this.player_id].color_back) {
                    playerBack = "background-color:#" + this.gamedatas.players[this.player_id].color_back + ";";
                }
                privateArgs.args['you'] = '<span style="font-weight:bold;color:#' + playerColor + ";" + playerBack + '">' + __("lang_mainsite", "You") + "</span>";
                this.setClientState(privateArgs.name, privateArgs);
            },

            serverAction(action, args, reEnterStateOnError = false) {
                if (!args) {
                    args = [];
                }
                args = dojo.clone(args);
                delete args.action;
                if (!args.hasOwnProperty('lock') || args.lock) {
                    args.lock = true;
                } else {
                    delete args.lock;
                }
                if (args.skipCheckInterfaceLocked !== false) {
                    if (this.isInterfaceLocked()) {
                        const errorMsg = _('Please wait, an action is already in progress');
                        this.showMessage(errorMsg, 'error');
                        return new Promise((resolve, reject) => {
                            reject(errorMsg);
                        })
                    }
                }
                delete args.skipCheckInterfaceLocked;
                // Please wait, an action is already in progress
                const name = this.game_name;
                const promise = new Promise((resolve, reject) => {
                    this.ajaxcall(
                        "/" + name + "/" + name + "/" + action + ".html",
                        args,
                        this,
                        (data) => resolve(data),
                        (isError, msg, code) => {
                            if (isError) {
                                reject(msg, code);
                            }
                        }
                    );
                });

                if (reEnterStateOnError) {
                    promise.catch(() => this.onEnteringState(this.gamedatas.gamestate.name, this.gamedatas.gamestate));
                }

                return promise;
            },
        });
    });