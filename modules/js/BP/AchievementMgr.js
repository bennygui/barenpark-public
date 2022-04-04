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
        return declare("bp.AchievementMgr", null, {
            setup(gamedatas) {
                this.setupAchievements(gamedatas);
                this.setupAchievementViewer(gamedatas);
            },

            setupAchievements(gamedatas) {
                const supplyBoardElem = document.querySelector('.bp-supply-board-achievements');
                const elemCreationElem = gameui.getElementCreationElement();
                for (const achievementId in gamedatas.achievements) {
                    // Show this part of the supply board if there are achievements
                    supplyBoardElem.classList.remove('bx-hidden');
                    const achievement = gamedatas.achievements[achievementId];
                    const achievementElem = this.createAchievementElement(achievement, true);
                    elemCreationElem.appendChild(achievementElem);
                    if (achievement.playerId === null) {
                        this.moveAchievementIdToSupplyBoard(achievementId, true);
                    } else {
                        this.moveAchievementIdToPlayer(achievementId, achievement.playerId, true);
                    }
                }
            },

            setupAchievementViewer(gamedatas) {
                const preventEvent = (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    return false;
                };
                for (const supplyPile in gamedatas.supplyAchievementCount) {
                    const button = this.getSupplyBoardAchievementViewButtonElem(supplyPile);
                    if (button === null) continue;
                    const achievementPileElem = this.getSupplyBoardAchievementPileElem(supplyPile);
                    const buttonDown = () => achievementPileElem.classList.add('bp-achievement-spread');
                    const buttonUp = () => achievementPileElem.classList.remove('bp-achievement-spread');
                    dojo.connect(button, 'touchstart', buttonDown);
                    dojo.connect(button, 'mousedown', buttonDown);
                    dojo.connect(button, 'touchend', buttonDown);
                    dojo.connect(button, 'mouseup', buttonUp);
                    dojo.connect(button, 'mouseleave', buttonUp);
                    dojo.connect(button, 'oncontextmenu', preventEvent);
                }
                this.updateAchievementViewerCounts(gamedatas.supplyAchievementCount);
            },

            updateAchievementViewerCounts(supplyAchievementCount) {
                for (const supplyPile in supplyAchievementCount) {
                    const button = this.getSupplyBoardAchievementViewButtonElem(supplyPile);
                    if (button === null) continue;
                    button.querySelector('span').innerText = supplyAchievementCount[supplyPile] + 'x';
                }
            },

            createAchievementElementFromAchievementId(achievementId) {
                const element = document.createElement('div');
                element.classList.add('bp-achievement');
                element.dataset.achievementId = achievementId;
                return element;
            },

            createAchievementElement(achievement, setId = false) {
                const element = this.createAchievementElementFromAchievementId(achievement.achievementId);
                element.dataset.supplyPile = achievement.supplyPile;
                element.style.setProperty('--bp-supply-board-order', parseInt(achievement.supplyPileOrder) + 1);
                if (setId) {
                    element.id = 'bp-achievement-id-' + achievement.achievementId;
                    this.updateTooltip(element);
                }
                return element;
            },

            getAchievementElementById(achievementId) {
                return document.getElementById('bp-achievement-id-' + achievementId);
            },

            getSupplyBoardAchievementPileElem(supplyPile) {
                return document.getElementById('bp-supply-board-achievement-' + supplyPile);
            },

            getSupplyBoardAchievementViewButtonElem(supplyPile) {
                return document.querySelector('#bp-supply-board-achievement-' + supplyPile + ' .bp-supply-board-view-button');
            },

            getPlayerAreaAchievementElem(playerId) {
                return document.querySelector('#bp-player-area-' + playerId + ' .bp-player-area-achievement');
            },

            moveAchievementIdToSupplyBoard(achievementId, isInstantaneous = false) {
                const achievementElem = this.getAchievementElementById(achievementId);
                const supplyElem = this.getSupplyBoardAchievementPileElem(achievementElem.dataset.supplyPile);
                return gameui.slide(achievementElem, supplyElem, {
                    lockId: 'moveAchievementIdToSupplyBoard',
                    phantom: true,
                    isInstantaneous: isInstantaneous,
                }).then(() => this.updateTooltip(achievementElem));
            },

            moveAchievementIdToPlayer(achievementId, playerId, isInstantaneous = false) {
                const achievementElem = this.getAchievementElementById(achievementId);
                const supplyElem = this.getPlayerAreaAchievementElem(playerId);
                return gameui.slide(achievementElem, supplyElem, {
                    lockId: 'moveAchievementIdToPlayer',
                    phantom: true,
                    isInstantaneous: isInstantaneous,
                }).then(() => this.updateTooltip(achievementElem));
            },

            updateTooltip(element) {
                gameui.addTooltip(
                    element.id,
                    _(gameui.gamedatas.achievements[element.dataset.achievementId].description),
                    ''
                );
            },
        });
    });