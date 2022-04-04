<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * theisleofcats implementation : © Guillaume Benny bennygui@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

namespace BX\UI;

require_once('Meta.php');

// Annotations used by this module:
// @ui(name): Rename the property to 'name' when sending to the ui

abstract class UISerializable implements \JsonSerializable
{
    public function jsonSerialize()
    {
        $meta = new \BX\Meta\Annotation(get_class($this));
        $array = \get_object_vars($this);
        foreach ($meta->getAnnotationElementsWithAnnotation('@ui') as $elem) {
            $value = $array[$elem->property()];
            unset($array[$elem->property()]);
            $array[$elem->parameters()[0]] = $value;
        }
        return $array;
    }
}

function deepCopyToArray($object)
{
    return  json_decode(json_encode($object), true);
}