<?php

declare(strict_types = 1);

namespace BlockHorizons\BlockSniper\brush\types;

use BlockHorizons\BlockSniper\brush\BaseType;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\ChunkManager;
use pocketmine\math\Vector3;
use pocketmine\Player;

class DrainType extends BaseType {

	const ID = self::TYPE_DRAIN;

	/*
	 * Removes all liquid blocks within the brush radius.
	 */
	public function __construct(Player $player, ChunkManager $level, array $blocks) {
		parent::__construct($player, $level, $blocks);
	}

	/**
	 * @return Block[]
	 */
	public function fillSynchronously(): array {
		$undoBlocks = [];
		foreach($this->blocks as $block) {
			$blockId = $block->getId();
			if($blockId === Item::LAVA || $blockId === Item::WATER || $blockId === Item::STILL_LAVA || $blockId === Item::STILL_WATER) {
				$undoBlocks[] = $block;
				$this->putBlock($block, 0);
			}
		}
		return $undoBlocks;
	}

	public function fillAsynchronously(): void {
		foreach($this->blocks as $block) {
			$blockId = $block->getId();
			if($blockId === Item::LAVA || $blockId === Item::WATER || $blockId === Item::STILL_LAVA || $blockId === Item::STILL_WATER) {
				$this->putBlock($block, 0);
			}
		}
	}

	public function getName(): string {
		return "Drain";
	}
}