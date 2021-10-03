<?php

declare(strict_types=1);

namespace xenialdan\MagicWE2\helper;

use InvalidArgumentException;
use pocketmine\block\utils\InvalidBlockStateException;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\nbt\UnexpectedTagTypeException;
use xenialdan\MagicWE2\exception\BlockQueryAlreadyParsedException;

final class BlockQuery
{
	public string $query;
	public ?string $fullBlockQuery = null;
	public ?string $blockId = null;
	public ?string $blockStatesQuery = null;
	public ?string $fullExtraQuery = null;
	public float $weight;//TODO check which are optional
	public ?int $blockFullId = null;

	/**
	 * BlockQuery constructor.
	 * @param string $query
	 * @param string|null $fullBlockQuery
	 * @param string|null $blockId
	 * @param string|null $blockStatesQuery
	 * @param string|null $fullExtraQuery
	 * @param float|null $weight
	 */
	public function __construct(string $query, ?string $fullBlockQuery, ?string $blockId, ?string $blockStatesQuery, ?string $fullExtraQuery, ?float $weight = 100)
	{
		$this->query = $query;
		$this->fullBlockQuery = $fullBlockQuery;
		$this->blockId = $blockId;
		$this->blockStatesQuery = $blockStatesQuery;
		$this->fullExtraQuery = $fullExtraQuery;
		$this->weight = (float)$weight / 100;
	}

	/**
	 * @param bool $update
	 * @return $this
	 * @throws BlockQueryAlreadyParsedException
	 * @throws InvalidArgumentException
	 * @throws InvalidBlockStateException
	 * @throws LegacyStringToItemParserException
	 * @throws UnexpectedTagTypeException
	 * @throws \xenialdan\MagicWE2\exception\InvalidBlockStateException
	 */
	public function parse(bool $update = true): self
	{
		//calling methods should check with hasBlock() before parse()
		if (!$update && $this->hasBlock()) throw new BlockQueryAlreadyParsedException("FullBlockID is already parsed");
		$blockstateParser = BlockStatesParser::getInstance();
		$blockstateParser::fromString($this);//this should already set the blockFullId because it is a reference
		//var_dump($this->hasBlock() ? "Has block, " . $this->blockFullId : "Does not have block");
		//TODO throw BlockQueryParsingFailedException if blockFullId was not set? `if(!$this->hasBlock())`
		return $this;
	}

	public function hasBlockStates(): bool
	{
		return $this->blockStatesQuery !== null;
	}

	public function hasExtraQuery(): bool
	{
		return $this->blockStatesQuery !== null;
	}

	public function hasBlock(): bool
	{
		return $this->blockFullId !== null;
	}

}