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
        return declare("bx.CSSTransition", null, {
            constructor() {
                this.transitionEndIndex = 0;
                this.transitionEndSet = new Set();
                this.transitionEndCallbacks = [];
            },

            addElement(element, maxDelay = 600) {
                const index = this.transitionEndIndex++;
                this.transitionEndSet.add(index);
                let called = false;
                const callback = () => {
                    if (called) {
                        return;
                    }
                    called = true;
                    element.removeEventListener('transitionend', callback);
                    this.transitionEndSet.delete(index);
                    this.onTransitionEnd();
                };
                element.addEventListener('transitionend', callback);
                setTimeout(callback, maxDelay);
            },

            onTransitionEnd() {
                if (this.transitionEndSet.size > 0) {
                    return;
                }
                for (const callback of this.transitionEndCallbacks) {
                    callback();
                }
                this.transitionEndCallbacks = [];
            },

            callOnTransitionEnd(callback) {
                if (this.transitionEndSet.size > 0) {
                    this.transitionEndCallbacks.push(callback);
                } else {
                    callback();
                }
            },
        });
    });