<?php

namespace morskoi\CheatChecker\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use CortexPE\Commando\exception\ArgumentOrderException;
use morskoi\CheatChecker\CheatChecker;
use pocketmine\command\CommandSender;

class ListCommand extends BaseSubCommand {
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
		$this->addConstraint(new InGameRequiredConstraint($this));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		$cfg = $this->plugin->getConfig();
        $activeChecks = $this->plugin->getSessionManager()->getActiveChecks();
        if (empty($activeChecks)) {
            $sender->sendMessage("§cNot found active checks!");
            return; 
        }
        foreach($activeChecks as $playerName => $data) {
            $sender->sendMessage(str_replace(["{STAFF}", "{PLAYER}", "{DATE}"], [$data["staff"], $playerName, $data["date"]], $cfg->get("format-list-checks")));
        }
    }
}