<?php

namespace platz1de\EasyEdit\thread\input;

use platz1de\EasyEdit\EasyEdit;
use platz1de\EasyEdit\thread\chunk\ChunkRequestManager;
use platz1de\EasyEdit\thread\ThreadStats;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ChunkInputData extends InputData
{
	private string $chunkData;
	private ?int $payload;

	/**
	 * @param string   $chunkData
	 * @param int|null $payload
	 */
	public static function from(string $chunkData, ?int $payload): void
	{
		if (!ThreadStats::getInstance()->hasTask()) {
			EasyEdit::getInstance()->getLogger()->debug("Ignored leftover chunk data");
			return;
		}
		$data = new self();
		$data->chunkData = $chunkData;
		$data->payload = $payload;
		$data->send();
	}

	public function handle(): void
	{
		ChunkRequestManager::handleInput($this->chunkData, $this->payload);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putString($this->chunkData);
		$stream->putBool($this->payload !== null);
		if ($this->payload !== null) {
			$stream->putLong($this->payload);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->chunkData = $stream->getString();
		$this->payload = $stream->getBool() ? $stream->getLong() : null;
	}
}