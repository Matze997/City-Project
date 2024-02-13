<?php

namespace platz1de\EasyEdit\thread\output;

use platz1de\EasyEdit\thread\chunk\ChunkRequest;
use platz1de\EasyEdit\thread\chunk\ChunkRequestExecutor;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class ChunkRequestData extends OutputData
{
	/**
	 * @param ChunkRequest $request
	 */
	public function __construct(private ChunkRequest $request) {}

	public function handle(): void
	{
		ChunkRequestExecutor::getInstance()->addRequest($this->request);
	}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$this->request->putData($stream);
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->request = ChunkRequest::readFrom($stream);
	}
}