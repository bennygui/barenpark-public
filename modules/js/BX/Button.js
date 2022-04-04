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
        return declare("bx.Button", null, {
            addTopButtonPrimary(id, title, callback) {
                this.addActionButton(id, title, callback);
            },
            addTopButtonSecondary(id, title, callback) {
                this.addActionButton(id, title, callback, null, false, 'gray');
            },
            addTopButtonImportant(id, title, callback) {
                this.addActionButton(id, title, callback, null, false, 'red');
            },
            addTopButtonPrimaryWithValid(id, title, errorMsg, callback) {
                this.addActionButton(id, title, () => {
                    if (this.isTopButtonValid(id)) {
                        callback();
                    } else {
                        this.showMessage(errorMsg, 'error');
                    }
                });
            },
            setTopButtonValid(id, isValid = true) {
                const button = document.getElementById(id);
                if (button === null) {
                    debug('setTopButtonValid cannot change button that does not exist id=' + id);
                    return;
                }
                if (isValid) {
                    button.classList.add('bgabutton_blue');
                    button.classList.remove('bx-top-button-invalid');
                } else {
                    button.classList.remove('bgabutton_blue');
                    button.classList.add('bx-top-button-invalid');
                }
            },
            isTopButtonValid(id) {
                const button = document.getElementById(id);
                return !button.classList.contains('bx-top-button-invalid');
            },
            addTopUndoButton(args) {
                let undoLevel = 0
                if (args && args.undoLevel !== undefined && args.undoLevel !== null) {
                    undoLevel = args.undoLevel;
                } else if (args && args._private && args._private.undoLevel !== undefined && args._private.undoLevel !== null) {
                    undoLevel = args._private.undoLevel;
                }
                if (undoLevel >= 1) {
                    this.addTopButtonSecondary('bx-button-undo-last', _('Undo'), () => this.serverAction('undoLast'));
                }
                if (undoLevel >= 2) {
                    this.addTopButtonSecondary('bx-button-undo-all', _('Undo All'), () => this.serverAction('undoAll'));
                }
            },
        });
    });