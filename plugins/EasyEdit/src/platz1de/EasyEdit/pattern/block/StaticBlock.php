<?php

namespace platz1de\EasyEdit\pattern\block;

use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;
use pocketmine\block\Block;

class StaticBlock extends BlockType
{
	/**
	 * @param int $id
	 */
	public function __construct(private int $id)
	{
		parent::__construct();
	}

	/**
	 * @param Block $block
	 * @return StaticBlock
	 */
	public static function from(Block $block): StaticBlock
	{
		return new self($block->getStateId());
	}

	/**
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param ChunkController $iterator
	 * @param Selection       $current
	 * @return int
	 */
	public function getFor(int $x, int &$y, int $z, ChunkController $iterator, Selection $current): int
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function get(): int
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getTypeId(): int
	{
		return $this->id >> Block::INTERNAL_STATE_DATA_BITS;
	}

	/**
	 * @param int $fullBlock
	 * @return bool
	 */
	public function equals(int $fullBlock): bool
	{
		return $fullBlock === $this->id;
	}

	/**
	 * @param SelectionContext $context
	 */
	public function applySelectionContext(SelectionContext $context): void
	{
		$context->includeWalls()->includeVerticals()->includeFilling();
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->id);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->id = $stream->getInt();
	}
}