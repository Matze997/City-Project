<?php

namespace platz1de\EasyEdit\pattern\block;

use BadMethodCallException;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\selection\SelectionContext;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\world\ChunkController;

/** Ignores Damage */
class BlockGroup extends BlockType
{
	/**
	 * @param int[] $ids
	 */
	public function __construct(private array $ids)
	{
		parent::__construct();
	}

	public function equals(int $fullBlock): bool
	{
		throw new BadMethodCallException("Can't use BlockGroup for checking");
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
		return array_rand(array_flip($this->ids));
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
		$stream->putInt(count($this->ids));
		foreach ($this->ids as $id) {
			$stream->putInt($id);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->ids = [];
		for ($i = $stream->getInt(); $i > 0; $i--) {
			$this->ids[] = $stream->getInt();
		}
	}
}