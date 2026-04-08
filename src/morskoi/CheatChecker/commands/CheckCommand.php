<?php

namespace morskoi\CheatChecker\commands;

use CortexPE\Commando\BaseCommand;
use morskoi\CheatChecker\CheatChecker;
use morskoi\CheatChecker\commands\subcommands\ListCommand;
use morskoi\CheatChecker\commands\subcommands\StartCommand;
use morskoi\CheatChecker\commands\subcommands\StopCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CheckCommand extends BaseCommand {
	/** @var CheatChecker */
    protected $plugin;

    public function __construct(CheatChecker $plugin, string $name, string $description = "", array $aliases = []) {
        $this->plugin = $plugin;
        parent::__construct($plugin, $name, $description, $aliases);
    }

	protected function prepare() : void {
        $this->setPermission("cheatchecker.check");
        $this->registerSubCommand(new StartCommand($this->plugin, "start", "start check"));
        $this->registerSubCommand(new StopCommand($this->plugin, "stop", "stop check"));
        $this->registerSubCommand(new ListCommand($this->plugin, "list", "send list all checks"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cOnly in game");
            return;
        }
        $cfg = $this->plugin->getConfig();
        $sender->sendMessage($cfg->get("command-use"));
    }
}