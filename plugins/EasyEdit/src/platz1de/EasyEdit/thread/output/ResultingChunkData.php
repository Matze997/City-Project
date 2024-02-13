<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\thread\EditThread;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;
use platz1de\EasyEdit\utils\LoaderManager;
use platz1de\EasyEdit\world\ChunkInformation;
use platz1de\EasyEdit\world\ReferencedWorldHolder;
use pocketmine\world\World;
use Throwable;

class ResultingChunkData extends OutputData
{
	use ReferencedWorldHolder;

	/**
	 * @param string             $world
	 * @param ChunkInformation[] $chunkData
	 * @param string[]           $injections UpdateSubChunkBlocksPacket data
	 */
	public function __construct(string $world, private array $chunkData, private array $injections = [])
	{
		$this->world = $world;
	}

	public function checkSend(): bool
	{
		if ($this->chunkData === [] && $this->injections === []) {
			EditThread::getInstance()->debug("No chunks modified");
			return false;
		}
		return true;
	}

	public function handle(): void
	{
		try {
			LoaderManager::setChunks($this->getWorld(), $this->getChunks(), $this->getInjections());
		} catch (Throwable $e) {
			EasyEdit::getInstance()->getLogger()->error("Error while setting chunks: " . $e->getMessage());
		}
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->world);

		$chunks = new ExtendedBinaryStream();
		$count = 0;
		foreach ($this->chunkData as $hash => $chunk) {
			World::getXZ($hash, $x, $z);
			$chunks->putInt($x);
			$chunks->putInt($z);
			$chunk->putData($chunks);
			$count++;
		}
		$stream->putInt($count);
		$stream->put($chunks->getBuffer());

		$stream->putInt(count($this->injections));
		foreach ($this->injections as $hash => $injection) {
			$stream->putLong($hash);
			$stream->putString($injection);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->world = $stream->getString();

		$this->chunkData = [];
		$count = $stream->getInt();
		$this->chunkData = [];
		for ($i = 0; $i < $count; $i++) {
			$this->chunkData[World::chunkHash($stream->getInt(), $stream->getInt())] = ChunkInformation::readFrom($stream);
		}

		$this->injections = [];
		$count = $stream->getInt();
		for ($i = 0; $i < $count; $i++) {
			$this->injections[$stream->getLong()] = $stream->getString();
		}
	}

	/**
	 * @return ChunkInformation[]
	 */
	public function getChunks(): array
	{
		return $this->chunkData;
	}

	/**
	 * @return string[]
	 */
	public function getInjections(): array
	{
		return $this->injections;
	}
}