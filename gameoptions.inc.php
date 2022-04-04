<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * barenpark implementation : © Guillaume Benny bennygui@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameoptions.inc.php
 *
 * barenpark game options description
 *
 */

 require_once('modules/php/BP/Globals.php');

$game_options = [
    GAME_OPTION_ACHIEVEMENT_ID => [
        'name' => totranslate('Use Achievements'),
        'values' => [
            GAME_OPTION_ACHIEVEMENT_VALUE_OFF => [
                'name' => totranslate('Off'),
                'tmdisplay' => totranslate('Do not use achievements'),
                'description' => totranslate('Do not use achievements for a simpler game'),
            ],
            GAME_OPTION_ACHIEVEMENT_VALUE_ON => [
                'name' => totranslate('On'),
                'tmdisplay' => totranslate('Use 3 random achievements'),
                'description' => totranslate('Use 3 random achievements'),
                'nobeginner' => true,
            ],
        ],
    ],
    GAME_OPTION_VARIANT_PIT_ID => [
        'name' => totranslate('Pit Variant'),
        'values' => [
            GAME_OPTION_VARIANT_PIT_VALUE_OFF => [
                'name' => totranslate('Off'),
                'tmdisplay' => totranslate('Do not use the Pit Variant'),
                'description' => totranslate('Do not use the Pit Variant'),
            ],
            GAME_OPTION_VARIANT_PIT_VALUE_ON => [
                'name' => totranslate('On'),
                'tmdisplay' => totranslate('Use the Pit Variant for experts'),
                'description' => totranslate('Use the Pit Variant for experts'),
                'nobeginner' => true,
            ],
        ],
    ],
];

$game_preferences = [
    USER_PREF_MODE_WARNING_ID => [
        'name' => totranslate('Warn for Try and Prepare Mode'),
        'values' => [
            USER_PREF_MODE_WARNING_VALUE_ENABLED => ['name' => totranslate('Enabled')],
            USER_PREF_MODE_WARNING_VALUE_DISABLED => ['name' => totranslate('Disabled')],
        ],
        'default' => USER_PREF_MODE_WARNING_VALUE_ENABLED,
    ],
];