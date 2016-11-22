<?php

namespace MoneyTag;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\Server;
use onebone\economyapi\EconomyAPI;
use onebone\economyapi\event\EconomyAPIEvent;
use MoneyTag\MoneyChangedEvent;
use pocketmine\IPlayer;
use LogLevel;

class Main extends PluginBase Implements Listener {
    public $economyAPI = null;
	public $damagetracker = [ ];
	
	public function onEnable(){
 		$this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function getMoneyPlugin() {
		return $this->money;
	}
	
	public function onMoneyChange(MoneyChangedEvent $event){
		$player = $event->getUsername();
		$name = $player->getName();
		$money = EconomyAPI::getInstance()->myMoney($player);
		$player->setNameTag(TF::DARK_GRAY."[ ".TF::YELLOW.$money. TF::DARK_GRAY." ] ".TF::GOLD .$name."");
	}
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		      switch($command->getName()){
                      case "coins":
                      $money = EconomyAPI::getInstance()->myMoney($sender);
                      $all = $this->getServer()->getOnlinePlayers();
                      $name = $sender->getName();
                      $sender->sendMessage(TF::GOLD."[".TF::YELLOW." $ ".TF::GOLD."] ".TF::GREEN. $name.", you have ".TF::AQUA.$money. TF::GREEN." coins");
              }
    }
    
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $money = EconomyAPI::getInstance()->myMoney($player);
        $name = $player->getName();
        $all = $this->getServer()->getOnlinePlayers();
        $player->setNameTag(TF::DARK_GRAY."[ ".TF::YELLOW.$money. TF::DARK_GRAY." ] ".TF::GOLD .$name."");
        //$player->setDisplayName(TF::DARK_GRAY."[ ".TF::YELLOW.$money. TF::DARK_GRAY." ] ".TF::GOLD .$name."");
    }
    
    public function onDamage(EntityDamageEvent $event) {
		if($event instanceof EntityDamageByEntityEvent or $event instanceof EntityDamageByChildEntityEvent){
			if($event->getDamager() instanceof Player and $event->getEntity() instanceof Player){
                $this->damagetracker [$event->getEntity()->getName()] = $event->getDamager()->getName();
            }
        }
    }
    
    /*public function onMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        $money = EconomyAPI::getInstance()->myMoney($player);
        $name = $player->getName();
        $all = $this->getServer()->getOnlinePlayers();
        $player->setNameTag(TF::DARK_GRAY."[ ".TF::YELLOW.$money. TF::DARK_GRAY." ] ".TF::GOLD .$name."");
        //$player->setDisplayName(TF::DARK_GRAY."[ ".TF::YELLOW.$money. TF::DARK_GRAY." ] ".TF::GOLD .$name."");
    }*/
    
    public function onDeath(PlayerDeathEvent $event){
        $player = $event->getPlayer();
        $money = EconomyAPI::getInstance()->myMoney($player);
        $name = $player->getName();
        $all = $this->getServer()->getOnlinePlayers();
        if(isset ($this->damagetracker [$event->getEntity()->getName()])){
			$damager = $this->getServer()->getPlayerExact($this->damagetracker [$event->getEntity()->getName()]);
			if(! $damager instanceof Player) return;
            $damager->setNameTag(TF::DARK_GRAY."[ ".TF::YELLOW.$money. TF::DARK_GRAY." ] ".TF::GOLD .$name."");
            //$damager->setDisplayName(TF::DARK_GRAY."[ ".TF::YELLOW.$money. TF::DARK_GRAY." ] ".TF::GOLD .$name."");
			unset ($this->damagetracker [$event->getEntity()->getName()]);
        }
    }
}

/**
 * This class allows you to use a number of miscellaneous Economy
 * plugins.
 */
abstract class MoneyAPI {
	/**
	 * Show a warning when the money API is missing
	 *
	 * @param PluginBase $plugin - current plugin
	 * @param LogLevel $level - optional log level
	 */
	static public function noMoney(PluginBase $plugin,$level = LogLevel::WARNING) {
		if (class_exists(__NAMESPACE__."\\mc",false)) {
			$plugin->getLogger()->error($level,TextFormat::RED.
											  mc::_("! MISSING MONEY API PLUGIN"));
			$plugin->getLogger()->error(TextFormat::BLUE.
											  mc::_(". Please install one of the following:"));
			$plugin->getLogger()->error(TextFormat::WHITE.
											  mc::_("* GoldStd"));
			$plugin->getLogger()->error(TextFormat::WHITE.
											  mc::_("* PocketMoney"));
			$plugin->getLogger()->error(TextFormat::WHITE.
											  mc::_("* EconomyAPI or"));
			$plugin->getLogger()->error(TextFormat::WHITE.
											  mc::_("* MassiveEconomy"));
		} else {
			$plugin->getLogger()->error($level,TextFormat::RED.
											  "! MISSING MONEY API PLUGIN");
			$plugin->getLogger()->error(TextFormat::BLUE.
											  ". Please install one of the following:");
			$plugin->getLogger()->error(TextFormat::WHITE.
											  "* GoldStd");
			$plugin->getLogger()->error(TextFormat::WHITE.
											  "* PocketMoney");
			$plugin->getLogger()->error(TextFormat::WHITE.
											  "* EconomyAPI or");
			$plugin->getLogger()->error(TextFormat::WHITE.
											  "* MassiveEconomy");
		}
	}
	/**
	 * Show a notice when the money API is found
	 *
	 * @param PluginBase $plugin - current plugin
	 * @param PluginBase $api - found plugin
	 * @param LogLevel $level - optional log level
	 */
	static public function foundMoney(PluginBase $plugin,$api,$level = LogLevel::INFO) {
		if (class_exists(__NAMESPACE__."\\mc",false)) {
			$plugin->getLogger()->log($level,TextFormat::BLUE.
											  mc::_("Using money API from %1%",
													  $api->getFullName()));
		} else {
			$plugin->getLogger()->log($level,TextFormat::BLUE.
											  "Using money API from ".$api->getFullName());
		}
	}
	/**
	 * Find a supported *money* plugin
	 *
	 * @param var obj - Server or Plugin object
	 * @return null|Plugin
	 */
	static public function moneyPlugin($obj) {
		if ($obj instanceof Server) {
			$server = $obj;
		} else {
			$server = $obj->getServer();
		}
		$pm = $server->getPluginManager();
		if(!($money = $pm->getPlugin("PocketMoney"))
			&& !($money = $pm->getPlugin("GoldStd"))
			&& !($money = $pm->getPlugin("EconomyAPI"))
			&& !($money = $pm->getPlugin("MassiveEconomy"))){
			return null;
		}
		return $money;
	}
	/**
	 * Gives money to a player.
	 *
	 * @param Plugin api Economy plugin (from moneyPlugin)
	 * @param str|IPlayer p Player to pay
	 * @param int money Amount of money to play (can be negative)
	 *
	 * @return bool
	 */
	static public function grantMoney($api,$p,$money) {
		if(!$api) return false;
		switch($api->getName()){
			case "GoldStd": // takes IPlayer|str
				$api->grantMoney($p, $money);
				break;
			case "PocketMoney": // takes str
			  if ($p instanceof IPlayer) $p = $p->getName();
				$api->grantMoney($p, $money);
				break;
			case "EconomyAPI": // Takes str
				if ($p instanceof IPlayer) $p = $p->getName();
				$api->setMoney($p,$api->mymoney($p)+$money);
				break;
			case "MassiveEconomy": // Takes str
				if ($p instanceof IPlayer) $p = $p->getName();
				$api->payPlayer($p->getName(),$money);
				break;
			default:
				return false;
		}
		return true;
	}
	/**
	 * Gets player balance
	 *
	 * @param Plugin $api Economy plugin (from moneyPlugin)
	 * @param str|IPlayer $player Player to lookup
	 *
	 * @return int
	 */
	static public function getMoney($api,$player) {
		if(!$api) return false;
		switch($api->getName()){
			case "GoldStd":
				return $api->getMoney($player);
				break;
			case "PocketMoney":
			case "MassiveEconomy":
				if ($player instanceof IPlayer) $player = $player->getName();
				return $api->getMoney($player);
			case "EconomyAPI":
				if ($player instanceof IPlayer) $player = $player->getName();
				return $api->mymoney($player);
			default:
				return false;
				break;
		}
	}
}

class MoneyChangedEvent extends EconomyAPIEvent{
	private $username, $money;
	public static $handlerList;
	public function __construct(EconomyAPI $plugin, $username, $money, $issuer){
		parent::__construct($plugin, $issuer);
		$this->username = $username;
		$this->money = $money;
	}
	/**
	 * @return string
	 */
	public function getUsername(){
		return $this->username;
	}
	/**
	 * @return float
	 */
	public function getMoney(){
		return $this->money;
	}
}
