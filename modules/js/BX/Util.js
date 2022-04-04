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
        return declare("bx.Util", null, {
            toPascalCase(str) {
                return this.toCamelCase(' ' + str);
            },
            toCamelCase(str) {
                return str.toLowerCase().replace(/[^a-zA-Z0-9]+(.)/g, (match, chr) => {
                    return chr.toUpperCase();
                });
            },
            toDashCase(str) {
                return str.replace(/\.?([A-Z]+)/g, (match, chr) => {
                    return "-" + chr.toLowerCase()
                }).replace(/^-/, '');
            },
            areObjectsEqual(a, b) {
                if (a === b) {
                    return true;
                }

                if (typeof a != 'object' || typeof b != 'object' || a === null || b === null) {
                    return false;
                }

                const keysA = Object.keys(a);
                const keysB = Object.keys(b);

                if (keysA.length != keysB.length) {
                    return false;
                }

                for (const key of keysA) {
                    if (!keysB.includes(key)) {
                        return false;
                    }

                    if (typeof a[key] === 'function' || typeof b[key] === 'function') {
                        if (a[key].toString() != b[key].toString()) {
                            return false;
                        }
                    } else {
                        if (!this.areObjectsEqual(a[key], b[key])) {
                            return false;
                        }
                    }
                }
                return true;
            },
        });
    });