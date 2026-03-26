<?php

namespace morskoi\CheatChecker\task;

use morskoi\CheatChecker\CheatChecker;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class CheckTask extends Task {
    
    private CheatChecker $plugin;
    private int $ticks = 0;
    
    public function __construct(CheatChecker $plugin) {
        $this->plugin = $plugin;
    }
    
    public function onRun(): void {
        $this->ticks++;
        $cfg = $this->plugin->getConfig();
        $interval = $cfg->get("title-interval", 10);
        if ($this->ticks % ($interval * 20) !== 0) {
            return;
        }
        
        $sessionManager = $this->plugin->getSessionManager();
        $activeChecks = $sessionManager->getActiveChecks();
        
        foreach ($activeChecks as $targetName => $data) {
            $target = $this->plugin->getServer()->getPlayerExact($targetName);
            
            if ($target !== null && $target->isOnline()) {
                $target->sendTitle($cfg->get("title-text"), $cfg->get("subtitle-text"), 10, 30, 10);
            }
        }
    }
}