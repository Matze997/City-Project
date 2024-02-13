<?php

namespace platz1de\EasyEdit\utils;

use platz1de\EasyEdit\convert\BlockStateConvertor;
use platz1de\EasyEdit\convert\TileConvertor;
use platz1de\EasyEdit\session\Session;
use pocketmine\block\Block;
use pocketmine\block\tile\Tile;
use pocketmine\data\bedrock\block\BlockStateData;

class BlockInfoTool
{
	/**
	 * @param Session $session
	 * @param Block   $block
	 */
	public static function display(Session $session, Block $block): void
	{
		$state = BlockParser::blockToStateString($block);
		$java = $state;
		if (($t = $block->getPosition()->getWorld()->getTile($block->getPosition())) instanceof Tile) {
			$tile = $t->saveNBT();
			TileConvertor::toJava($tile, $java);
		}
		$java = BlockParser::toStateString(BlockStateConvertor::bedrockToJava(BlockParser::fromStateString($java, BlockStateData::CURRENT_VERSION)));
		$session->sendMessage("block-info", [
			"{state}" => $state,
			"{id}" => (string) $block->getTypeId(),
			"{meta}" => (string) ($block->getStateId() & Block::INTERNAL_STATE_DATA_MASK),
			"{name}" => $block->getName(),
			"{x}" => (string) $block->getPosition()->getX(),
			"{y}" => (string) $block->getPosition()->getY(),
			"{z}" => (string) $block->getPosition()->getZ(),
			"{java_state}" => $java
		]);
	}
}