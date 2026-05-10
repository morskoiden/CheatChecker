<?php

namespace morskoi\CheatChecker\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use CortexPE\Commando\exception\ArgumentOrderException;
use morskoi\CheatChecker\CheatChecker;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use morskoi\CheatChecker\commands\arguments\PlayerArgument;
use pocketmine\Server;

class StartCommand extends BaseSubCommand {
 	/** @var CheatChecker */
    protected $plugin;

	/**
	 * @throws ArgumentOrderException
	 */
    public function __construct(CheatChecker $plugin, string $name, string $description = "", array $aliases = []) {
        $this->plugin = $plugin;
        parent::__construct($name, $description, $aliases);
    }
	protected function prepare(): void {
		$this->registerArgument(0, new PlayerArgument("target", true));
		$this->addConstraint(new InGameRequiredConstraint($this));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		$cfg = $this->plugin->getConfig();
        if (!isset($args["target"])) {
            $sender->sendMessage($cfg->get("player-null"));
            return;
        }
        $targetName = $args["target"];
        $target = Server::getInstance()->getPlayerExact($targetName);
        if ($target === null) {
            $sender->sendMessage(str_replace("{PLAYER}", $targetName, $cfg->get("player-offline")));
            return;
        }
        $this->plugin->getSessionManager()->onStart($target, $sender);
    }
}