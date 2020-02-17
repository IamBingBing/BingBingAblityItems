<?php 
namespace ablity_item;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;


class main extends PluginBase implements Listener{
    public $DATA = [];
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->cooltime = new Config($this->getDataFolder()."DATA.json" ,Config::JSON ,[]);
        $this->cool = $this->cooltime->getAll();
        
    }
    public static function getInstance() : main{
        return self::$instance;
    }
    
    public function join(PlayerJoinEvent $event){
        $name = $event->getPlayer()->getName();
        array_push($this->DATA , [$name => []]);
        $this->DATA[$name]["undeadcool"] = true;
        $this->DATA[$name]["hideonbushcool"] = true;
        $this->DATA[$name]["ablity"] = "";
    }
    public function onCommand(CommandSender $sender, Command$command, string $label, array $args):bool{
        $item = $sender->getInventory()->getItemInHand();
        if ($command == "원소스킬" ){
            if ($args[0] == "추가"){
                switch ($args[1]){
                    case "무적":
                        $item->setCustomName("무적");
                        if (is_numeric($args[2]) && 0 <= $args[2] && $args[2]<= 60){
                            $sender->getInventory()->setItemInHand($item);
                            $this->cool["undead"] = $args[2]*20;
                            $sender->sendMessage("완료");
                        }
                        else {
                            $this->cool["undead"] = 80;
                            $sender->sendMessage("완료");
                        }
                        return true;
                        break;
                    case "그림자":
                        $item->setCustomName("그림자");
                        if (is_numeric($args[2]) && 0 <= $args[2] && $args[2] <= 60){
                            $sender->getInventory()->setItemInHand($item);
                            $this->cool["hideonbush"] = $args[2]*20;
                            $sender->sendMessage("완료");
                        }
                        else {
                            $this->cool["hideonbush"] = 80;
                            $sender->sendMessage("완료");
                        }
                        return true;
                        break;
                    default:
                        $sender->sendMessage("야 잘못쳤잖아");
                        return true;
                        break;
            
                }
                return true;
            }
            else {
                $sender->sendMessage("야 잘못쳤잖아");
                return true;
            }
            return true;
        }
    }
    public function touch (PlayerInteractEvent $event){
         $player = $event->getPlayer();
         $item = $player->getInventory()->getItemInHand()->getCustomName();
        
        switch ($item){
            case "무적":
                if ($this->DATA[$player->getName()]["undeadcool"] ){
                    $this->undead($player);
                    $this->DATA[$player->getName()]["undeadcool"] = false;
                    $this->getScheduler()->scheduleDelayedTask(new undeadcooltime($player), $this->cool["undead"]);
                }
                else {
                    $player->sendMessage("쿨타임입니다.");
                }
                break;
            case "그림자":
                if ($this->DATA[$player->getName()]["hideonbushcool"] ){
                    $this->hideonbush($player);
                    $this->DATA[$player->getName()]["hideonbushcool"] = false;
                    $this->getScheduler()->scheduleDelayedTask(new hideonbushcooltime($player), $this->cool["hideonbush"]);
                }
                else {
                    $player->sendMessage("쿨타임입니다.");
                }
               
                break;
            default:
                break;
                
        }
    }
    private function undead (Player $player ) :void { 
        $this->DATA[$player->getName()]["ablity"] = "무적";
        $this->getScheduler()->scheduleDelayedTask(new time($player), 80);
        $player->sendMessage("무적이다 !");
    }
    private function hideonbush( Player $player) :void{
        foreach ( $this->getServer()->getOnlinePlayers() as $online){
            $onlinepos = $online->getPosition();
            $playerpos = $player->getPosition();
            if(    ( ( $playerpos->getFloorX() - $onlinepos->getFloorX())^2 +($playerpos->getFloorY() - $onlinepos->getFloorY())^2 + ($playerpos->getFloorZ() - $onlinepos->getFloorZ())^2 ) ^1/2 <= 7  ){
                $player->teleport($onlinepos);
            }
        }
    }
    public function fight(EntityDamageEvent $event){
        if($event->getEntity() instanceof Player){
            if($this->DATA[$event->getEntity()->getName()]["ablity"] == "무적"){
                $event->setBaseDamage(0);
            }
            else {
                return;
            }
        }
    }
    public function onDisable(){
        $this->cooltime->setAll($this->cool);
        $this->cooltime->save();
        $this->saveConfig();
    }
    
}
class time extends Task {
    public function __construct(Player $player){
        $this->player = $player;
    }
    public function onRun(int $currentTick)
    {
        main::getInstance()->DATA[$this->player->getName()]["ablity"] = "";
        $this->player->sendMessage("무적 끝");
    }
}
class undeadcooltime extends Task{
    public function __construct(Player $player){
        $this->player = $player;
    }
   
    public function onRun(int $currentTick){
        main::getInstance()->DATA[$this->player->getName()]["undeadcool"] = true;
        
    }
}
class hideonbushcooltime extends Task{
    
    public function __construct(Player $player){
        $this->player = $player;
    }
    public function onRun(int $currentTick){
        main::getInstance()->DATA[$this->player->getName()]["hideonbushcool"] = true;
    }
}


?>