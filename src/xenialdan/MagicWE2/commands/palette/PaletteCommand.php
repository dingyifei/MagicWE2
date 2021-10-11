<?php

declare(strict_types=1);

namespace xenialdan\MagicWE2\commands\palette;

use CortexPE\Commando\BaseCommand;
use Exception;
use InvalidArgumentException;
use jojoe77777\FormAPI\SimpleForm;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Ramsey\Uuid\Uuid;
use xenialdan\MagicWE2\exception\PaletteException;
use xenialdan\MagicWE2\exception\SessionException;
use xenialdan\MagicWE2\helper\BlockPalette;
use xenialdan\MagicWE2\helper\SessionHelper;
use xenialdan\MagicWE2\Loader;
use xenialdan\MagicWE2\session\UserSession;
use function var_dump;

class PaletteCommand extends BaseCommand
{
	/**
	 * This is where all the arguments, permissions, sub-commands, etc would be registered
	 * @throws InvalidArgumentException
	 */
	protected function prepare(): void
	{
		#$this->registerSubCommand(new PaletteNameCommand("name", "Get name or rename a palette"));
		$this->setPermission("we.command.palette");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
	{
		$lang = Loader::getInstance()->getLanguage();
		if ($sender instanceof Player && SessionHelper::hasSession($sender)) {
			try {
				$lang = SessionHelper::getUserSession($sender)->getLanguage();
			} catch (SessionException $e) {
			}
		}
		if (!$sender instanceof Player) {
			$sender->sendMessage(TF::RED . $lang->translateString('error.runingame'));
			return;
		}
		/** @var Player $sender */
		try {
			$session = SessionHelper::getUserSession($sender);
			if (!$session instanceof UserSession) {
				throw new SessionException($lang->translateString('error.nosession', [Loader::getInstance()->getName()]));
			}
			$form = (new SimpleForm(function (Player $player, $data) use ($lang, $session) {
				try {
					switch ($data) {
						case 'ui.palette.get':
						{
							$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
							foreach ($session->getPalettes()->getAll() as $id => $palette) {
								$menu->getInventory()->addItem($palette->toItem($id));
							}
							$menu->send($player, "Session palettes");
							break;
						}
						case 'ui.palette.fromhotbar':
						{
							/** @var Block[] $blocks */
							$blocks = [];
							for ($i = 0; $i <= $player->getInventory()->getHotbarSize(); $i++) {
								$item = $player->getInventory()->getHotbarSlotItem($i);
								if (!$item->isNull() && ($block = $item->getBlock()) instanceof Block) $blocks[] = $block;
							}
							$palette = BlockPalette::fromBlocks($blocks);
							$id = Uuid::uuid4()->toString();
							$session->getPalettes()->palettes[$id] = $palette;
							$session->sendMessage(TF::GREEN . $lang->translateString('Created palette from hotbar'));
							$player->getInventory()->addItem($palette->toItem($id));
							break;
						}
						case 'ui.palette.frominventory':
						{
							/** @var Block[] $blocks */
							$blocks = [];
							foreach ($player->getInventory()->getContents() as $item) {
								$block = $item->getBlock();
								if ($block !== VanillaBlocks::AIR()) $blocks[] = $block;
							}
							$palette = BlockPalette::fromBlocks($blocks);
							$id = Uuid::uuid4()->toString();
							$session->getPalettes()->palettes[$id] = $palette;
							$session->sendMessage(TF::GREEN . $lang->translateString('Created palette from inventory'));
							$player->getInventory()->addItem($palette->toItem($id));
							break;
						}
						case 'ui.palette.modifyblocks':
						{
							$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
							foreach ($session->getPalettes()->getAll() as $id => $palette) {
								$menu->getInventory()->addItem($palette->toItem($id));
							}
							$menu->setListener(function (InvMenuTransaction $transaction) use ($session): InvMenuTransactionResult {
								//todo functionality
								$player = $transaction->getPlayer();
								$itemClicked = $transaction->getItemClicked();
								$itemClickedWith = $transaction->getItemClickedWith();
								$action = $transaction->getAction();
								$inv_transaction = $transaction->getTransaction();
								try {
									$palette = $session->getPalettes()->getPaletteFromItem($itemClicked);
									var_dump($player, $itemClicked, $itemClickedWith, $action, $inv_transaction, $itemClicked->getLore(), $palette);
								} catch (PaletteException $e) {
									$session->sendMessage($e->getMessage());
									Loader::getInstance()->getLogger()->logException($e);
								}
								return $transaction->continue();
							});
							$menu->send($player, "Select a palette to modify");
							break;
						}
						case 'ui.palette.modifyweights':
						{
							$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
							foreach ($session->getPalettes()->getAll() as $id => $palette) {
								$menu->getInventory()->addItem($palette->toItem($id));
							}
							$menu->setListener(function (InvMenuTransaction $transaction) use ($session): InvMenuTransactionResult {
								//todo functionality
								$player = $transaction->getPlayer();
								$itemClicked = $transaction->getItemClicked();
								$itemClickedWith = $transaction->getItemClickedWith();
								$action = $transaction->getAction();
								$inv_transaction = $transaction->getTransaction();
								try {
									$palette = $session->getPalettes()->getPaletteFromItem($itemClicked);
									var_dump($player, $itemClicked, $itemClickedWith, $action, $inv_transaction, $itemClicked->getLore(), $palette);
								} catch (PaletteException $e) {
									$session->sendMessage($e->getMessage());
									Loader::getInstance()->getLogger()->logException($e);
								}
								return $transaction->continue();
							});
							$menu->send($player, "Select a palette to modify");
							break;
						}
					}
					return null;
				} catch (Exception $error) {
					$session->sendMessage(TF::RED . $lang->translateString('error'));
					$session->sendMessage(TF::RED . $error->getMessage());
				}
			}))
				->setTitle(Loader::PREFIX_FORM . TF::BOLD . TF::DARK_PURPLE . $lang->translateString('ui.palette.title'))
				->setContent($lang->translateString('ui.palette.content'))
				->addButton($lang->translateString('ui.palette.fromhotbar'), -1, "", 'ui.palette.fromhotbar')
				->addButton($lang->translateString('ui.palette.frominventory'), -1, "", 'ui.palette.frominventory')
				#->addButton($lang->translateString('ui.palette.fromselection'), -1, "", 'ui.palette.fromselection')
				->addButton($lang->translateString('ui.palette.get'), -1, "", 'ui.palette.get')
				->addButton($lang->translateString('ui.palette.modifyblocks'), -1, "", 'ui.palette.modifyblocks')
				->addButton($lang->translateString('ui.palette.modifyweights'), -1, "", 'ui.palette.modifyweights');
			$sender->sendForm($form);
		} catch (Exception $error) {
			$sender->sendMessage(Loader::PREFIX . TF::RED . $lang->translateString('error.command-error'));
			$sender->sendMessage(Loader::PREFIX . TF::RED . $error->getMessage());
			$sender->sendMessage($this->getUsage());
		}
	}
}
