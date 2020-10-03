<?php

namespace xenialdan\MagicWE2\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\uuid\UUID;
use xenialdan\MagicWE2\exception\SessionException;
use xenialdan\MagicWE2\helper\Progress;
use xenialdan\MagicWE2\helper\SessionHelper;
use xenialdan\MagicWE2\session\UserSession;

abstract class MWEAsyncTask extends AsyncTask
{
	/** @var string */
	public string $sessionUUID;
	/** @var float */
	public float $start;

	public function onProgressUpdate($progress): void
	{
		if (!$progress instanceof Progress) {//TODO Temp fix until all async tasks are modified
			$progress = new Progress($progress[0] / 100, $progress[1]);
		}
		try {
			$session = SessionHelper::getSessionByUUID(UUID::fromString($this->sessionUUID));
			/** @var Progress $progress */
			if ($session instanceof UserSession) $session->getBossBar()->setPercentage($progress->progress)->setSubTitle(str_replace("%", "%%%%", $progress->string . " | " . floor($progress->progress * 100) . "%"));
			else $session->sendMessage($progress->string . " | " . floor($progress->progress * 100) . "%");//TODO remove, debug
		} catch (SessionException $e) {
            //TODO log?
        }
    }

    public function generateTookString(): string
    {
        return date("i:s:", (int)(microtime(true) - $this->start)) . strval(round(microtime(true) - $this->start, 1, PHP_ROUND_HALF_DOWN));
    }
}