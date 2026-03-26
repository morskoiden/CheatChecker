<?php

declare(strict_types=1);

namespace morskoi\CheatChecker;

use morskoi\CheatChecker\session\SessionManager;
use morskoi\CheatChecker\task\CheckTask;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use morskoi\CheatChecker\event\EventListener;
use morskoi\CheatChecker\commands\CheckCommand;

class CheatChecker extends PluginBase implements Listener {
    private Config $cfg;
    private Config $cfgChecks;
    private SessionManager $sessionManager;
    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->cfgChecks = new Config($this->getDataFolder() . "checks.yml", Config::YAML);

        $this->sessionManager = new SessionManager($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

		$this->getServer()->getCommandMap()->register($this->getDescription()->getName(), new CheckCommand($this, "check", "check in cheat"));
        $this->getScheduler()->scheduleRepeatingTask(new CheckTask($this), 20);
    }
    public function onDisable(): void {
        $this->sessionManager->removeAllChecks();
    }
    public function getConfig(): Config 
    {
        return $this->cfg;
    }
    public function getConfigChecks(): Config 
    {
        return $this->cfgChecks;
    }
    public function getSessionManager(): SessionManager 
    {
        return $this->sessionManager;
    }
}
