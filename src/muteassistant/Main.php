<?php

declare(strict_types = 1);

namespace muteassistant;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use muteassistant\command\MainCommand;

/**
 * Main class
 */
class Main extends PluginBase implements Listener {

  /**
   * Consts and Variables
   */
  public const INFO = TF::BOLD . TF::YELLOW . "INFO " . TF::RESET . TF::YELLOW;
  public const ERROR = TF::BOLD . TF::RED . "ERROR " . TF::RESET . TF::RED;
  public $db;
  public $target = [];

  /**
   * Call when plugin is loading
   */
  public function onLoad(){
    $this->getLogger()->info(self::INFO . "Plugin is loading...");
    @mkdir($this->getDataFolder());
    $this->db = new \SQLite3($this->getDataFolder() . "MuteListData.db");
    $this->db->exec("CREATE TABLE IF NOT EXISTS mutelist(player TEXT PRIMARY KEY, time INT, reason TEXT, staff TEXT);");
  }

  /**
   * Call when plugin enables
   */
  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->getCommand("mute")->setExecutor(new MainCommand($this));
  }

  /**
  * Call when plugin disables
  */
  public function onDisable(){
    $this->getLogger()->info(self::INFO . "Plugin disabled.");
  }

  public function onPlayerChat(PlayerChatEvent $ev){
    $player = $ev->getPlayer();
    $pn = $player->getName();
    $db = $this->db->query("SELECT * FROM mutelist WHERE player = '$pn';");
    $data = $db->fetchArray(SQLITE3_ASSOC);
    if(!empty($data)){
      if($data["time"] == -1){
        $ev->setCancelled();
        $player->sendMessage(self::INFO . "You have been muted premently. If we made a mistake contact us to review.");
      } else {
        if($data["time"] > time()){
          $ev->setCancelled();
          $rt = $data["time"] - time();
          $rtd = floor($rt / 86400);
          $rths = $rt % 86400;
          $rth = floor($rths / 3600);
          $rtms = $rths % 3600;
          $rtm = floor($rtms / 60);
          $rtss = $rtms % 60;
          $rts = ceil($rtss);
          $player->sendMessage(self::INFO . "You have been muted for " . TF::LIGHT_PURPLE . $rtd . " days, " . $rth . " hours, " . $rtm . " minutes, " . $rts . " seconds" . TF::YELLOW . ". Please avoid chatting!");
        } else {
          $this->db->query("DELETE FROM mutelist WHERE player = '$pn';");
        }
      }
    }
  }

  public function mute(Player $staff, Player $target, string $reason, $day = 0, $hour = 0, $minute = 0){
    if($day == "N/A"){
      $db = $this->db->prepare("INSERT OR REPLACE INTO mutelist (player, time, reason, staff) VALUES (:player, :time, :reason, :staff);");
      $db->bindValue(":player", $target->getName());
      $db->bindValue(":time", -1);
      $db->bindValue(":reason", $reason);
      $db->bindValue(":staff", $staff->getName());
      $db->execute();
      $staff->sendMessage(self::INFO . "Player " . TF::LIGHT_PURPLE . $target->getName() . TF::YELLOW . " muted for " . TF::RED . "EVER");
      $target->sendMessage(self::INFO . "You have been muted from chat for " . TF::RED . "EVER" . TF::YELLOW . "! It was a mistake? Contact us to review.");
      return true;
    }
    $d = $day * 86400;
    $h = $hour * 3600;
    $m = $minute * 60;
    $mt = time() + $d + $h + $m;
    $db = $this->db->prepare("INSERT OR REPLACE INTO mutelist (player, time, reason, staff) VALUES (:player, :time, :reason, :staff);");
    $db->bindValue(":player", $target->getName());
    $db->bindValue(":time", $mt);
    $db->bindValue(":reason", $reason);
    $db->bindValue(":staff", $staff->getName());
    $db->execute();
    $staff->sendMessage(self::INFO . "Player " . TF::LIGHT_PURPLE . $target->getName() . TF::YELLOW . " muted for " . TF::LIGHT_PURPLE . $day . " days, " . $hour . " hours, " . $minute . " minutes");
    $target->sendMessage(self::INFO . "You have been muted from chat for " . TF::LIGHT_PURPLE . $day . " days, " . $hour . " hours, " . $minute . " minutes" . TF::YELLOW . ". Please avoid chatting!");
  }

}