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
    public function onStart(Player $target, Player $staff): void {
        $targetName = $target->getName();
        $cfg = $this->plugin->getConfig();
        $staffName = $staff->getName();
        $target->sendMessage(str_replace("{STAFF}", $staffName, $cfg->get("start-check")));
        $this->noCheckPos[$targetName] = $target->getPosition();
        $data = [
            "player" => $targetName,
            "staff" => $staffName,
            "date" => date("d.m.Y H:i")
        ];
        $this->activeChecks[$targetName] = $data;
        $config = $this->plugin->getConfig();
        $pos = $this->plugin->getConfig()->get("teleport-pos");
        $worldName = $pos["world"];
        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
        if ($config->get("teleport-enable") === "on") {
            if ($world !== null) {
                $pos = new Position(
                    (float)$pos["x"], 
                    (float)$pos["y"], 
                    (float)$pos["z"], 
                    $world
                );
                $target->teleport($pos);
            } else {
                $errorWorld = str_replace("{WORLD}", $worldName, $cfg->get("error-world"));
                $this->plugin->getLogger()->error($errorWorld);
            }
        }
    }
    
    public function onStop(Player $target): void {
        $targetName = $target->getName();
        $cfg = $this->plugin->getConfig();
        if ($target->isOnline()) {
            $target->sendMessage($cfg->get("stop-check"));
        }
        $this->cleanupSession($targetName);
    }

    public function getSession(Player $target): ?array {
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
    }

    public function getStaff(Player $player): string {
        return $this->activeChecks[$player->getName()]["staff"] ?? "Unknown";
    }
}