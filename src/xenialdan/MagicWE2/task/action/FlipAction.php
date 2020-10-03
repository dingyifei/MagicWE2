<?php

declare(strict_types=1);

namespace xenialdan\MagicWE2\task\action;

use Exception;
use Generator;
use InvalidArgumentException;
use pocketmine\block\BlockFactory;
use xenialdan\MagicWE2\clipboard\SingleClipboard;
use xenialdan\MagicWE2\helper\BlockEntry;
use xenialdan\MagicWE2\helper\BlockStatesParser;
use xenialdan\MagicWE2\helper\Progress;
use xenialdan\MagicWE2\selection\Selection;

class FlipAction extends ClipboardAction
{
	const AXIS_X = "x";
	const AXIS_Y = "y";
	const AXIS_Z = "z";
	const AXIS_XZ = "xz";
	/** @var bool */
	public bool $addClipboard = true;
	/** @var string */
	public string $completionString = '{%name} succeed, took {%took}, flipped {%changed} blocks out of {%total}';
	/** @var string */
	private string $axis;

	public function __construct(string $axis)
	{
		if ($axis !== self::AXIS_X && $axis !== self::AXIS_Y && $axis !== self::AXIS_Z && $axis !== self::AXIS_XZ) throw new InvalidArgumentException("Invalid axis $axis given");
		$this->axis = $axis;
	}

	public static function getName(): string
	{
		return "Flip";
    }

    /**
     * @param string $sessionUUID
     * @param Selection $selection
     * @param null|int $changed
     * @param SingleClipboard $clipboard
     * @param string[] $messages
     * @return Generator|Progress[]
     * @throws Exception
     */
    public function execute(string $sessionUUID, Selection $selection, ?int &$changed, SingleClipboard $clipboard, array &$messages = []): Generator
	{
		//TODO modify position. For now, just flip the blocks around their own axis
		$changed = 0;
		#$oldBlocks = [];
		$count = $selection->getShape()->getTotalCount();
		$lastProgress = new Progress(0, "");
		BlockFactory::getInstance();
		if (!BlockStatesParser::isInit()) {
			var_dump("reinit BlockStatesParser AGAIN");
			BlockStatesParser::init();
        }
        $clonedClipboard = clone $clipboard;
        $x = $y = $z = null;
        $maxX = $clipboard->selection->getSizeX() - 1;
        $maxY = $clipboard->selection->getSizeY() - 1;
        $maxZ = $clipboard->selection->getSizeZ() - 1;
        foreach ($clipboard->iterateEntries($x, $y, $z) as $blockEntry) {
            #var_dump("$x $y $z");
            if ($this->axis === self::AXIS_Z || $this->axis === self::AXIS_XZ)
                $x = $maxX - $x;
            if ($this->axis === self::AXIS_X || $this->axis === self::AXIS_XZ)
                $z = $maxZ - $z;
            if ($this->axis === self::AXIS_Y)
                $y = $maxY - $y;
            #var_dump("$x $y $z");
            $block1 = $blockEntry->toBlock();var_dump($block1);
            $blockStatesEntry = BlockStatesParser::getStateByBlock($block1);
            $mirrored = $blockStatesEntry->mirror($this->axis);
            $block = $mirrored->toBlock();
            $entry = BlockEntry::fromBlock($block);
            var_dump($blockStatesEntry->__toString(), $mirrored->__toString(), $block);
            /** @var int $x */
            /** @var int $y */
            /** @var int $z */
            $clonedClipboard->addEntry($x, $y, $z, $entry);
            $changed++;
            $progress = new Progress($changed / $count, "$changed/$count");
            if (floor($progress->progress * 100) > floor($lastProgress->progress * 100)) {
                yield $progress;
                $lastProgress = $progress;
            }
        }
        $clipboard = $clonedClipboard;
    }
}