<?php

/*
 *  ___            __  __
 * |_ _|_ ____   _|  \/  | ___ _ __  _   _
 *  | || '_ \ \ / / |\/| |/ _ \ '_ \| | | |
 *  | || | | \ V /| |  | |  __/ | | | |_| |
 * |___|_| |_|\_/ |_|  |_|\___|_| |_|\__,_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Muqsit
 * @link http://github.com/Muqsit
 *
*/

declare(strict_types=1);

namespace muqsit\invmenu\metadata;

use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\session\MenuExtradata;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

abstract class MenuMetadata {

	protected string $identifier;
	protected int $size;
	protected int $window_type;

	public function __construct(string $identifier, int $size, int $window_type) {
		$this->identifier = $identifier;
		$this->size = $size;
		$this->window_type = $window_type;
	}

	public function getIdentifier(): string {
		return $this->identifier;
	}

	public function getWindowType(): int {
		return $this->window_type;
	}

	public function createInventory(): InvMenuInventory {
		return new InvMenuInventory($this->getSize());
	}

	public function getSize(): int {
		return $this->size;
	}

	public function calculateGraphicPosition(Player $player): Vector3 {
		return $player->getPosition()->addVector($this->calculateGraphicOffset($player))->floor();
	}

	protected function calculateGraphicOffset(Player $player): Vector3 {
		$offset = $player->getDirectionVector();
		$size = $player->size;
		$offset->x *= -(1 + $size->getWidth());
		$offset->y *= -(1 + $size->getHeight());
		$offset->z *= -(1 + $size->getWidth());
		return $offset;
	}

	abstract public function sendGraphic(Player $player, MenuExtradata $metadata): bool;

	abstract public function removeGraphic(Player $player, MenuExtradata $extradata): void;
}