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
        return declare("bx.UI", null, {
            addClickable(element, callback, options = {}) {
                const config = Object.assign({
                    border: true,
                    outline: false,
                    childEventSelector: null,
                }, options);

                element.classList.add('bx-clickable');
                if (config.border) {
                    element.classList.add('bx-border');
                }
                if (config.outline) {
                    element.classList.add('bx-outline');
                }
                if (config.childEventSelector === null) {
                    this.connect(element, 'onclick', callback);
                } else {
                    for (const child of element.querySelectorAll(config.childEventSelector)) {
                        this.connect(child, 'onclick', callback);
                    }
                }
            },
            removeAllClickable() {
                this.disconnectAll();
                const elements = document.querySelectorAll('.bx-clickable');
                for (const e of elements) {
                    e.classList.remove('bx-clickable');
                    e.classList.remove('bx-border');
                    e.classList.remove('bx-outline');
                }
            },
            addSelected(element, options = {}) {
                const config = Object.assign({
                    border: true,
                    outline: false,
                }, options);

                element.classList.add('bx-selected');
                if (config.border) {
                    element.classList.add('bx-border');
                }
                if (config.outline) {
                    element.classList.add('bx-outline');
                }
            },
            removeAllSelected() {
                const elements = document.querySelectorAll('.bx-selected');
                for (const e of elements) {
                    e.classList.remove('bx-selected');
                    e.classList.remove('bx-border');
                    e.classList.remove('bx-outline');
                }
            },
        });
    });