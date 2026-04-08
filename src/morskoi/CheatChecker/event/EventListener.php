<?php

namespace morskoi\CheatChecker\event;

use pocketmine\event\{Listener, player\PlayerMoveEvent, player\PlayerDropItemEvent, server\CommandEvent};
use morskoi\CheatChecker\CheatChecker;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\Player;

class EventListener implements Listener {
    private CheatChecker $plugin;

    public function __construct(CheatChecker $plugin) {
        $this->plugin = $plugin;
    }

    public function OnMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        if ($this->plugin->getSessionManager()->isChecked($player)) {
            $event->cancel();
        }
    }

    public function onDrop(PlayerDropItemEvent $event) {
        $player = $event->getPlayer();
        if ($this->plugin->getSessionManager()->isChecked($player)) {
            $event->cancel();
        }
    }
    
    public function CommandEvent(CommandEvent $event) {
        $sender = $event->getSender();
        if (!$sender instanceof Player) {
            return;
        }
        if ($this->plugin->getSessionManager()->isChecked($sender)) {
            $event->cancel();
            $cfg = $this->plugin->getConfig();
            $sender->sendMessage($cfg->get("command-no-usage"));
        }
    }
    public function onChat(PlayerChatEvent $event) {
        $cfg = $this->plugin->getConfig();
        $player = $event->getPlayer();
        $message = $event->getMessage();
        if ($cfg->get("special-chat-enable") === "on") {
            if ($this->plugin->getSessionManager()->isChecked($player)) {
                $event->cancel();
                $staff = $this->plugin->getServer()->getPlayerExact($this->plugin->getSessionManager()->getStaff($player));
                $chatFormat = str_replace(["{PLAYER}", "{MESSAGE}"], [$player->getName(), $message], $cfg->get("chat-format"));
                $player->sendMessage($chatFormat);
                if ($staff !== null && $staff->isOnline()) {
                    $staff->sendMessage($chatFormat);
                }
            }
        }
    }
}