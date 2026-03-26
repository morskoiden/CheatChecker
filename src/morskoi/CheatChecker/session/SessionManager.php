<?php

namespace morskoi\CheatChecker\session;

use morskoi\CheatChecker\CheatChecker;
use pocketmine\player\Player;
use pocketmine\world\Position;

class SessionManager {
    private CheatChecker $plugin;
    private array $activeChecks = [];
    private array $noCheckPos = [];

    public function __construct(CheatChecker $plugin) {
        $this->plugin = $plugin;
    }
    public function onStart(Player $target, Player $staff) {
        $targetName = $target->getName();
        $cfg = $this->plugin->getConfig();
        $staffName = $staff->getName();
        $target->sendMessage(str_replace("{STAFF}", $staffName, $cfg->get("start-check")));
        $this->noCheckPos[$targetName] = $target->getPosition();
        $data = [
            "staff" => $staffName,
            "starttime" => time(),
            "time" => time() + 5 * 60
        ];
        $this->activeChecks[$targetName] = $data;
        $cfg = $this->plugin->getConfigChecks();
        $cfg->setNested("active_checks.$targetName", $data);
        $cfg->save();
        $config = $this->plugin->getConfig()->get("teleport-pos");
        $worldName = $config["world"];
        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
        if ($world !== null) {
            $pos = new Position(
                (float)$config["x"], 
                (float)$config["y"], 
                (float)$config["z"], 
                $world
            );
            $target->teleport($pos);
        } else {

            $errorWorld = str_replace("{WORLD}", $world, $cfg->get("error-world"));
            $this->plugin->getLogger()->error($errorWorld);
        }
    }
    
    public function onStop(Player $target) {
        $targetName = $target->getName();
        $cfg = $this->plugin->getConfig();
        if ($target->isOnline()) {
            $target->sendMessage($cfg->get("stop-check"));
        }
        $this->cleanupSession($targetName);
    }

    public function getSession(Player $target) {
        return $this->activeChecks[$target->getName()] ?? null;
    }

    public function isChecked(Player $target): bool {
        return isset($this->activeChecks[$target->getName()]);
    }
    public function getActiveChecks(): array {
        return $this->activeChecks;
    }

    public function cleanupSession(string $targetName): void {
        unset($this->activeChecks[$targetName]);
        unset($this->noCheckPos[$targetName]);
        $cfg = $this->plugin->getConfigChecks();
        $cfg->removeNested("active_checks.$targetName");
        $cfg->save();
    }

    public function removeAllChecks(): void {
        $this->activeChecks = [];
        $cfg = $this->plugin->getConfigChecks();
        $cfg->remove("active_checks");
        $cfg->save();
    }
}