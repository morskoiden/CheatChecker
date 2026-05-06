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
        $this->plugin->getServer()->getLogger()->info(str_replace(["{STAFF}", "{PLAYER}"], [$staffName, $targetName], $cfg->get("start-check-logg")));
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
            $this->plugin->getServer()->getLogger()->info(str_replace(["{STAFF}", "{PLAYER}"], [$this->getStaff($target), $targetName], $cfg->get("stop-check-logg")));
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

    public function teleportStaff(Player $staff, Player $player) {
        $playerName = $player->getName();
        $staffName = $staff->getName();
        $cfg = $this->plugin->getConfig();
        if ($player === $staff) {
			$staff->sendMessage($cfg->get("self-check-stop"));
			return;
		}
        if (!$this->isChecked($player)) {
            $staff->sendMessage(str_replace("{PLAYER}", $playerName, $cfg->get("not-in-check")));
            return;
        }
        if (!$this->getStaff($player) === $staffName) {
            $staff->sendMessage(str_replace("{PLAYER}", $playerName, $cfg->get("not-checking-player")));
            return;
        }
        $staff->teleport($player->getPosition());
        $staff->sendMessage(str_replace("{PLAYER}", $playerName, $cfg->get("success-teleport")));
        $this->plugin->getServer()->getLogger()->info(str_replace(["{STAFF}", "{PLAYER}"], [$staffName, $playerName], $cfg->get("teleport-check-logg")));
    }
}