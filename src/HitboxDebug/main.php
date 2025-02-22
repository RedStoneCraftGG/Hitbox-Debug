<?php

namespace HitboxDebug;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as TF;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\event\player\PlayerMoveEvent;

class Main extends PluginBase implements Listener {

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        
        $this->getScheduler()->scheduleRepeatingTask(new class($this, $player) extends Task {
            private Main $plugin;
            private Player $player;
            
            public function __construct(Main $plugin, Player $player) {
                $this->plugin = $plugin;
                $this->player = $player;
            }
            
            public function onRun(): void {
                if (!$this->player->isOnline()) return;
                
                $bb = $this->player->getBoundingBox();
                $width = round($bb->maxX - $bb->minX, 2);
                $height = round($bb->maxY - $bb->minY, 2);
                
                $isSwimming = $this->plugin->isSwim($this->player) ? "True" : "False";
                $isGliding = $this->plugin->isGlide($this->player) ? "True" : "False";

                $message = TF::YELLOW . "Hitbox: " . TF::WHITE . "Width: $width, Height: $height \nSwimming: $isSwimming, Gliding: $isGliding";

                $pk = new TextPacket();
                $pk->type = TextPacket::TYPE_TIP;
                $pk->message = $message;
                $this->player->getNetworkSession()->sendDataPacket($pk);
            }
        }, 20);
    }

    public function isSwim(Player $player): bool {
        $bb = $player->getBoundingBox();
        return $bb->maxY - $bb->minY < 1 && $player->isUnderwater();
    }

    public function isGlide(Player $player): bool {
        $bb = $player->getBoundingBox();
        $motion = $player->getMotion();

        return $motion->y < -0.3 && abs($motion->x) > 0.2 && abs($motion->z) > 0.2;
    }
}