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
        return declare("bx.Animation", null, {
            GAME_PLAY_AREA_ID: 'game_play_area',

            fixAbsolutePositionInPlace(element) {
                element.style.left = element.offsetLeft + 'px';
                element.style.top = element.offsetTop + 'px';
                element.style.position = 'absolute';
            },

            // Based on displayScoring from BGA
            displayBigNumberOnElement(element, number, options = {}) { //color, number, displayDuration = 500, fadeDuration = 500) {
                const config = Object.assign({
                    color: '000000',
                    displayDuration: 500,
                    fadeDuration: 500,
                    changeParent: false,
                }, options);

                const numberElemString = gameui.format_string(
                    '<div class="scorenumber" style="z-index:1000">' + (number >= 0 ? "+" : "-") + "${number}</div>", {
                    number: Math.abs(number)
                });
                const numberElem = dojo.place(numberElemString, element);
                numberElem.style.color = '#' + config.color;
                gameui.placeOnObject(numberElem, element);
                if (config.changeParent) {
                    this.changeParent(numberElem, this.GAME_PLAY_AREA_ID);
                }
                numberElem.classList.add('scorenumber_anim');
                gameui.fadeOutAndDestroy(numberElem, config.fadeDuration, config.displayDuration);
            },

            // From https://github.com/bga-devs/tisaac-boilerplate with some modifications
            isFastMode() {
                return this.instantaneousMode;
            },
            // Slide with pos option is buggy, watch out when using it
            slide(mobileElt, targetElt, options = {}) {
                let config = Object.assign({
                    duration: 500,
                    delay: 0,
                    destroy: false,
                    attach: true,
                    changeParent: true, // Change parent during sliding to avoid zIndex issue
                    pos: null,
                    className: 'bx-moving',
                    from: null,
                    clearPos: true,
                    beforeBrother: null,
                    phantom: false,
                    lockId: null,
                    isInstantaneous: false,
                },
                    options,
                );
                config.phantomStart = config.phantomStart || config.phantom;
                config.phantomEnd = config.phantomEnd || config.phantom;
                if (this.isInterfaceLocked()) {
                    config.lockId = null;
                }

                // Mobile elt
                mobileElt = $(mobileElt);
                let mobile = mobileElt;
                // Target elt
                targetElt = $(targetElt);
                let targetId = targetElt;
                const newParent = config.attach ? targetId : $(mobile).parentNode;

                // Handle fast mode
                if ((this.isFastMode() || config.isInstantaneous) && (config.destroy || config.clearPos)) {
                    if (config.destroy) dojo.destroy(mobile);
                    else dojo.place(mobile, targetElt);

                    return new Promise((resolve, reject) => {
                        resolve();
                    });
                }

                // Do nothing if movement is not needed
                if (!config.destroy && config.pos === null && mobile.parentNode == targetElt) {
                    if (config.clearPos) this.clearPos(mobile);
                    return new Promise((resolve, reject) => {
                        resolve();
                    });
                }

                // Handle phantom at start
                if (config.phantomStart) {
                    mobile = dojo.clone(mobileElt);
                    dojo.attr(mobile, 'id', mobileElt.id + '_animated');
                    dojo.place(mobile, this.GAME_PLAY_AREA_ID);
                    this.placeOnObject(mobile, mobileElt);
                    dojo.addClass(mobileElt, 'bx-phantom');
                    config.from = mobileElt;
                }

                // Handle phantom at end
                if (config.phantomEnd) {
                    targetId = dojo.clone(mobileElt);
                    dojo.attr(targetId, 'id', mobileElt.id + '_afterSlide');
                    dojo.addClass(targetId, 'bx-phantomm');
                    if (config.beforeBrother != null) {
                        dojo.place(targetId, config.beforeBrother, 'before');
                    } else {
                        dojo.place(targetId, targetElt);
                    }
                }

                dojo.style(mobile, 'zIndex', 5000);
                dojo.addClass(mobile, config.className);
                if (config.changeParent) this.changeParent(mobile, this.GAME_PLAY_AREA_ID);
                if (config.from != null) this.placeOnObject(mobile, config.from);
                return new Promise((resolve, reject) => {
                    if (config.lockId) {
                        this.lockInterface(config.lockId);
                    }
                    const animation =
                        config.pos == null ?
                            this.slideToObject(mobile, targetId, config.duration, config.delay) :
                            this.slideToObjectPos(mobile, targetId, config.pos.x, config.pos.y, config.duration, config.delay);

                    dojo.connect(animation, 'onEnd', () => {
                        if (config.lockId) {
                            this.unlockInterface(config.lockId);
                        }
                        dojo.style(mobile, 'zIndex', null);
                        dojo.removeClass(mobile, config.className);
                        if (config.phantomStart) {
                            dojo.place(mobileElt, mobile, 'replace');
                            dojo.removeClass(mobileElt, 'bx-phantom');
                            mobile = mobileElt;
                        }
                        if (config.changeParent) {
                            if (config.phantomEnd) dojo.place(mobile, targetId, 'replace');
                            else this.changeParent(mobile, newParent);
                        }
                        if (config.destroy) dojo.destroy(mobile);
                        if (config.clearPos && !config.destroy) this.clearPos(mobile);
                        // Correct end position if other elements have moved
                        if (!config.clearPos && !config.destroy && config.pos !== null) {
                            mobile.style.left = config.pos.x + 'px';
                            mobile.style.top = config.pos.y + 'px';
                        }
                        resolve();
                    });
                    animation.play();
                });
            },
            clearPos(element) {
                dojo.style(element, { top: null, left: null, position: null });
            },
            changeParent(mobile, new_parent, relation) {
                if (mobile === null) {
                    console.error('attachToNewParent: mobile obj is null');
                    return;
                }
                if (new_parent === null) {
                    console.error('attachToNewParent: new_parent is null');
                    return;
                }
                if (typeof mobile == 'string') {
                    mobile = $(mobile);
                }
                if (typeof new_parent == 'string') {
                    new_parent = $(new_parent);
                }
                if (typeof relation == 'undefined') {
                    relation = 'last';
                }
                const boundingClientRectZoomScale = this.getBoundingClientRectZoomScale(new_parent);
                let zoom = this.interface_autoscale === true ? (this.gameinterface_zoomFactor || 1) : 1;
                if (zoom < 1 && boundingClientRectZoomScale == 1) {
                    // in case the browser doesn't handle correctly the zoom scale on dojo.position, we consider the zoom is not set
                    zoom = 1;
                }
                if (zoom <= 0) {
                    zoom = 1;
                }

                var src = dojo.position(mobile);
                dojo.style(mobile, 'position', 'absolute');
                dojo.place(mobile, new_parent, relation);
                var tgt = dojo.position(mobile);
                var box = dojo.marginBox(mobile);
                var cbox = dojo.contentBox(mobile);
                var left = box.l + src.x - tgt.x;
                var top = box.t + src.y - tgt.y;
                this.positionObjectDirectly(mobile, left / zoom, top / zoom);
                box.l += box.w - cbox.w;
                box.t += box.h - cbox.h;
                return box;
            },
            positionObjectDirectly(mobileObj, x, y) {
                // do not remove this "dead" code some-how it makes difference
                dojo.style(mobileObj, 'left'); // bug? re-compute style
                dojo.style(mobileObj, {
                    left: x + 'px',
                    top: y + 'px',
                });
                dojo.style(mobileObj, 'left'); // bug? re-compute style
            },
            // check the zoom scale applied on an element.
            // on old browsers (Chrome < 128), the zoom factor isn't applied to dojo.position (=getBoundingClientRect)
            getBoundingClientRectZoomScale(obj) {
                const zoom = Math.round((this.interface_autoscale === true ? (this.gameinterface_zoomFactor || 1) : 1) * 1000) / 1000;

                const object = obj ? $(obj) : document.getElementById('page-content');
                const position = dojo.position(object);
                if (position.w > 0) {
                    const zoomScale = Math.round(position.w / object.offsetWidth * 1000) / 1000;
                    return zoomScale > zoom ? 1 : zoomScale;
                } else if (position.h > 0) {
                    const zoomScale = Math.round(position.h / object.offsetHeight * 1000) / 1000;
                    return zoomScale > zoom ? 1 : zoomScale;
                } else {
                    return 1;
                }
            },
            /*
             * Wrap a node inside a flip container to trigger a flip animation before replacing with another node
             */
            flipAndReplace(target, newNode) {
                // To be able to change the duration, would need to change the css
                const duration = 1000;
                // Fast replay mode
                if (this.isFastMode()) {
                    dojo.place(newNode, target, 'replace');
                    return;
                }

                return new Promise((resolve, reject) => {
                    // Wrap everything inside a flip container
                    let container = dojo.place(
                        `<div class="bx-flip-container bx-flipped">
                            <div class="bx-flip-inner">
                               <div class="bx-flip-front"></div>
                               <div class="bx-flip-back"></div>
                            </div>
                         </div>`,
                        target,
                        'after',
                    );
                    dojo.place(target, container.querySelector('.bx-flip-back'));
                    dojo.place(newNode, container.querySelector('.bx-flip-front'));
                    container.style.width = target.offsetWidth + 'px';
                    container.style.height = target.offsetHeight + 'px';

                    // Trigget flip animation
                    container.offsetWidth;
                    dojo.removeClass(container, 'bx-flipped');

                    // Clean everything once it's done
                    setTimeout(() => {
                        dojo.place(newNode, container, 'replace');
                        resolve();
                    }, duration);
                });
            },

            wait(delay) {
                return new Promise((resolve, reject) => {
                    setTimeout(() => resolve(), delay);
                });
            },

            zoomFocus(element, duration = 1000, zoomWait = 50) {
                const id = element.id + '_zoom_focus'
                if (document.getElementById(id) !== null) {
                    return;
                }
                const animElem = dojo.clone(element);
                element.classList.add('bx-phantom');

                animElem.id = id;
                animElem.style.transition = 'transform ' + (duration/2) + 'ms ease-in-out';
                element.parentNode.insertBefore(animElem, element);
                animElem.offsetWidth; // Needed to trigger the transition
                let prevTransform = getComputedStyle(animElem).transform;
                if (prevTransform == 'none') {
                    prevTransform = '';
                }

                return this.wait(1)
                    .then(() => animElem.style.transform = prevTransform + ' scale(1.5)')
                    .then(() => this.wait(duration/2 + zoomWait))
                    .then(() => animElem.style.removeProperty('transform'))
                    .then(() => this.wait(duration/2))
                    .then(() => {
                    element.classList.remove('bx-phantom');
                    dojo.destroy(animElem);
                });
            }
        });
    });