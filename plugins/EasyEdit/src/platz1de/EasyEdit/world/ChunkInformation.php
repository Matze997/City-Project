<?php

namespace platz1de\EasyEdit\world;

use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use pocketmine\block\tile\Tile;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use pocketmine\world\World;

class ChunkInformation
{
	/**
	 * @var CompoundTag[]
	 */
	private array $tiles = [];
	private bool $used = false;

	/**
	 * @param Chunk         $chunk
	 * @param CompoundTag[] $tiles
	 */
	public function __construct(private Chunk $chunk, array $tiles)
	{
		foreach ($tiles as $tile) {
			$this->tiles[World::blockHash($tile->getInt(Tile::TAG_X) & 0x0f, $tile->getInt(Tile::TAG_Y), $tile->getInt(Tile::TAG_Z) & 0x0f)] = $tile;
		}
	}

	/**
	 * @return ChunkInformation
	 */
	public static function empty(): ChunkInformation
	{
		$subChunks = [];
		for ($y = 0; $y < Chunk::MAX_SUBCHUNKS; $y++) {
			$subChunks[$y] = new SubChunk(0, [], new PalettedBlockArray(BiomeIds::OCEAN));
		}
		return new self(new Chunk($subChunks, true), []);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 */
	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString(FastChunkSerializer::serializeTerrain($this->chunk));
		$stream->putCompounds($this->tiles);
	}

	/**
	 * @param ExtendedBinaryStream $stream
	 * @return ChunkInformation
	 */
	public static function readFrom(ExtendedBinaryStream $stream): ChunkInformation
	{
		return new self(FastChunkSerializer::deserializeTerrain($stream->getString()), $stream->getCompounds());
	}

	/**
	 * @param int              $x
	 * @param int              $y
	 * @param int              $z
	 * @param CompoundTag|null $tile
	 * @return void
	 */
	public function setTile(int $x, int $y, int $z, ?CompoundTag $tile): void
	{
		if ($tile !== null) {
			$this->tiles[World::blockHash($x, $y, $z)] = $tile;
		} else {
			unset($this->tiles[World::blockHash($x, $y, $z)]);
		}
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return CompoundTag|null
	 */
	public function getTile(int $x, int $y, int $z): ?CompoundTag
	{
		return $this->tiles[World::blockHash($x, $y, $z)] ?? null;
	}

	/**
	 * @return Chunk
	 */
	public function getChunk(): Chunk
	{
		return $this->chunk;
	}

	/**
	 * @return CompoundTag[]
	 */
	public function getTiles(): array
	{
		return $this->tiles;
	}

	public function use(): void
	{
		$this->used = true;
	}

	/**
	 * @return bool
	 */
	public function wasUsed(): bool
	{
		return $this->used;
	}

	public function __clone(): void
	{
		$this->chunk = clone $this->chunk;
		foreach ($this->tiles as $key => $tile) {
			$this->tiles[$key] = clone $tile;
		}
		$this->used = false;
	}

	public function resetFlags(): void
	{
		$this->used = false;
	}

	/**
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		foreach ($this->chunk->getSubChunks() as $subChunk) {
			if (!$subChunk->isEmptyFast()) {
				return false;
			}
		}
		return $this->tiles === [];
	}
}