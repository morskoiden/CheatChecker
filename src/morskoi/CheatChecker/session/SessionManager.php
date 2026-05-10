<?php

namespace morskoi\CheatChecker\session;

use morskoi\CheatChecker\CheatChecker;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\world\sound\FireworkLaunchSound;
use pocketmine\world\sound\FizzSound;

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
        if ($targetName === $staffName) {
            $staff->sendMessage($cfg->get("self-check-start"));
            return;
        }
        if ($this->isChecked($target)) {
            $staff->sendMessage(str_replace("{PLAYER}", $targetName, $cfg->get("player-in-check")));
            return;
        }
        $target->sendMessage(str_replace("{STAFF}", $staffName, $cfg->get("start-check")));
        $staff->sendMessage(str_replace("{PLAYER}", $targetName, $cfg->get("staff-message-start")));
        $this->plugin->getServer()->getLogger()->info(str_replace(["{STAFF}", "{PLAYER}"], [$staffName, $targetName], $cfg->get("start-check-logg")));
        $this->noCheckPos[$targetName] = $target->getPosition();
        $data = [
            "player" => $targetName,
            "staff" => $staffName,
            "date" => date("d.m.Y H:i")
        ];
        $this->activeChecks[$targetName] = $data;
        $pos = $cfg->get("teleport-pos");
        $worldName = $pos["world"];
        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
        if ($cfg->get("teleport-enable") === "on") {
            if ($world !== null) {
                $pos = new Position(
                    (float)$pos["x"],
                    (float)$pos["y"], 
                    (float)$pos["z"], 
                    $world
                );
                $target->teleport($pos);
                $target->getWorld()->addSound($target->getPosition(), new FireworkLaunchSound(), [$target, $staff]);
                $staff->getWorld()->addSound($staff->getPosition(), new FireworkLaunchSound(), [$target, $staff]);
            } else {
                $this->plugin->getLogger()->error(str_replace("{WORLD}", $worldName, $cfg->get("error-world")));
            }
        }
    }
    
    public function onStop(Player $target): void {
        $targetName = $target->getName();
        $staffName = $this->getStaff($target);
        $staff = $this->plugin->getServer()->getPlayerExact($staffName);
        $cfg = $this->plugin->getConfig();
        if ($targetName === $staffName) {
			$staff->sendMessage($cfg->get("self-check-stop"));
			return;
		}
        if ($target->isOnline()) {
            $this->plugin->getServer()->getLogger()->info(str_replace(["{STAFF}", "{PLAYER}"], [$staffName, $targetName], $cfg->get("stop-check-logg")));
            $target->sendMessage($cfg->get("stop-check"));
            $target->getWorld()->addSound($target->getPosition(), new FizzSound(), [$target, $staff]);
            $staff->getWorld()->addSound($staff->getPosition(), new FizzSound(), [$target, $staff]);
            $target->teleport($this->noCheckPos[$targetName]);
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