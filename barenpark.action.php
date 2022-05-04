<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * barenpark implementation : © Guillaume Benny bennygui@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * barenpark.action.php
 *
 * barenpark main action entry point
 *
 */

require_once("modules/php/BP/Globals.php");

class action_barenpark extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if (self::isArg('notifwindow')) {
      $this->view = "common_notifwindow";
      $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
    } else {
      $this->view = "barenpark_barenpark";
      self::trace("Complete reinitialization of board game");
    }
  }

  public function undoLast()
  {
    self::setAjaxMode();
    $this->game->undoLast();
    self::ajaxResponse();
  }

  public function undoAll()
  {
    self::setAjaxMode();
    $this->game->undoAll();
    self::ajaxResponse();
  }

  public function enterPlayLoop()
  {
    self::setAjaxMode();
    $this->game->enterPlayLoop();
    self::ajaxResponse();
  }

  public function chooseTileFromPlayerSupply()
  {
    self::setAjaxMode();
    $shapeId = self::getArg("shapeId", AT_posint, true);

    $this->game->chooseTileFromPlayerSupply($shapeId);

    self::ajaxResponse();
  }

  public function changeChooseTileFromPlayerSupply()
  {
    self::setAjaxMode();
    $shapeId = self::getArg("shapeId", AT_posint, true);

    $this->game->changeChooseTileFromPlayerSupply($shapeId);

    self::ajaxResponse();
  }

  public function placeTileInPark()
  {
    self::setAjaxMode();
    $parkId = self::getArg("parkId", AT_posint, true);
    $parkTopX = self::getArg("parkTopX", AT_int, true);
    $parkTopY = self::getArg("parkTopY", AT_posint, true);
    $parkRotation = self::getArg("parkRotation", AT_enum, true, null, VALID_ROTATIONS);
    $parkHorizontalFlip = self::getArg("parkHorizontalFlip", AT_bool, true);
    $parkVerticalFlip = self::getArg("parkVerticalFlip", AT_bool, true);

    $this->game->placeTileInPark($parkId, $parkTopX, $parkTopY, $parkRotation, $parkHorizontalFlip, $parkVerticalFlip);

    self::ajaxResponse();
  }

  public function chooseShapeFromSupplyBoard()
  {
    self::setAjaxMode();
    $shapeId = self::getArg("shapeId", AT_posint, true);

    $this->game->chooseShapeFromSupplyBoard($shapeId);

    self::ajaxResponse();

  }

  public function chooseShapeFromSupplyBoardAndPass()
  {
    self::setAjaxMode();
    $shapeId = self::getArg("shapeId", AT_posint, true);

    $this->game->chooseShapeFromSupplyBoardAndPass($shapeId);

    self::ajaxResponse();

  }

  public function chooseParkFromSupplyBoard()
  {
    self::setAjaxMode();
    $parkId = self::getArg("parkId", AT_posint, true);

    $this->game->chooseParkFromSupplyBoard($parkId);

    self::ajaxResponse();
  }

  public function changeChooseParkFromSupplyBoard()
  {
    self::setAjaxMode();
    $parkId = self::getArg("parkId", AT_posint, true);

    $this->game->changeChooseParkFromSupplyBoard($parkId);

    self::ajaxResponse();
  }

  public function changeChooseShapeFromSupplyBoard()
  {
    self::setAjaxMode();
    $shapeId = self::getArg("shapeId", AT_posint, true);

    $this->game->changeChooseShapeFromSupplyBoard($shapeId);

    self::ajaxResponse();

  }

  public function placePlayerPark()
  {
    self::setAjaxMode();
    $posX = self::getArg("posX", AT_int, true);
    $posY = self::getArg("posY", AT_int, true);

    $this->game->placePlayerPark($posX, $posY);

    self::ajaxResponse();

  }

  public function confirmTurn()
  {
    self::setAjaxMode();

    $this->game->confirmTurn();

    self::ajaxResponse();
  }

  public function passTurn()
  {
    self::setAjaxMode();

    $this->game->passTurn();

    self::ajaxResponse();
  }

  public function enterTryMode()
  {
    self::setAjaxMode();
    $this->game->enterTryMode();
    self::ajaxResponse();
  }

  public function exitTryMode()
  {
    self::setAjaxMode();
    $this->game->exitTryMode();
    self::ajaxResponse();
  }

  public function tryModeChooseTile()
  {
    self::setAjaxMode();
    $shapeId = self::getArg("shapeId", AT_posint, true);

    $this->game->tryModeChooseTile($shapeId);

    self::ajaxResponse();
  }

  public function tryModeChangeChooseTile()
  {
    self::setAjaxMode();
    $shapeId = self::getArg("shapeId", AT_posint, true);

    $this->game->tryModeChangeChooseTile($shapeId);

    self::ajaxResponse();
  }
  
  public function tryModePlaceTile()
  {
    self::setAjaxMode();
    $parkId = self::getArg("parkId", AT_posint, true);
    $parkTopX = self::getArg("parkTopX", AT_int, true);
    $parkTopY = self::getArg("parkTopY", AT_posint, true);
    $parkRotation = self::getArg("parkRotation", AT_enum, true, null, VALID_ROTATIONS);
    $parkHorizontalFlip = self::getArg("parkHorizontalFlip", AT_bool, true);
    $parkVerticalFlip = self::getArg("parkVerticalFlip", AT_bool, true);

    $this->game->tryModePlaceTile($parkId, $parkTopX, $parkTopY, $parkRotation, $parkHorizontalFlip, $parkVerticalFlip);

    self::ajaxResponse();
  }

  public function tryModeChoosePark()
  {
    self::setAjaxMode();
    $parkId = self::getArg("parkId", AT_posint, true);

    $this->game->tryModeChoosePark($parkId);

    self::ajaxResponse();

  }

  public function tryModeChangeChoosePark()
  {
    self::setAjaxMode();
    $parkId = self::getArg("parkId", AT_posint, true);

    $this->game->tryModeChangeChoosePark($parkId);

    self::ajaxResponse();
  }

  public function tryModePlacePark()
  {
    self::setAjaxMode();
    $posX = self::getArg("posX", AT_int, true);
    $posY = self::getArg("posY", AT_int, true);

    $this->game->tryModePlacePark($posX, $posY);

    self::ajaxResponse();

  }

}
