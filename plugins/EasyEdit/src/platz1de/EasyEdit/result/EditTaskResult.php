<?php

namespace platz1de\EasyEdit\result;

use platz1de\EasyEdit\selection\identifier\BlockListSelectionIdentifier;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\utils\ExtendedBinaryStream;

class EditTaskResult extends TaskResult
{
	/**
	 * @param int                 $affected
	 * @param BlockListSelectionIdentifier $selection Might be invalid, can be history or clipboard depending on the task
	 */
	public function __construct(private int $affected, private BlockListSelectionIdentifier $selection) {}

	public function putData(ExtendedBinaryStream $stream): void
	{
		$stream->putInt($this->affected);
		$this->selection = $this->selection->toIdentifier();
		if ($this->selection->isValid()) {
			$stream->putBool(true);
			$stream->putString($this->selection->fastSerialize());
		} else {
			$stream->putBool(false);
		}
	}

	public function parseData(ExtendedBinaryStream $stream): void
	{
		$this->affected = $stream->getInt();
		if ($stream->getBool()) {
			$this->selection = StoredSelectionIdentifier::fastDeserialize($stream->getString());
		} else {
			$this->selection = StoredSelectionIdentifier::invalid();
		}
	}

	/**
	 * @return int
	 */
	public function getAffected(): int
	{
		return $this->affected;
	}

	/**
	 * @return BlockListSelectionIdentifier
	 */
	public function getSelection(): BlockListSelectionIdentifier
	{
		return $this->selection;
	}
}