<?php
namespace BlueWEMT;
use pocketmine\Server;
use pocketmine\plugin\Plugin;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use BlueWEMT\API;

class Listener implements \pocketmine\event\Listener{
	/** @var Plugin */
	private $plugin;
	public function __construct(Plugin $plugin){
		$this->plugin = $plugin;
		Server::getInstance()->getPluginManager()->registerEvents($this, $plugin);//注册事件> <
	}

    public function onDataPacketReceive(DataPacketReceiveEvent $event){
        if($event->isCancelled())return false;
        return true;//todo 接封包的处理
    }

    public function onDataPacketSend(DataPacketSendEvent $event){
        if($event->isCancelled())return false;
        $pk=$event->getPacket();
        $level=$event->getPlayer()->getLevel();
        //瞎几把改的wcnm
        if($pk->getName() == "FullChunkDataPacket"){
            $this->plugin->API->DoRerenderChunk($level,$level->getChunk($pk->chunkX,$pk->chunkZ,false),$event->getPlayer());
            //echo('UPDATE');
        }else{
            //echo($pk->getName()."\n");
        }

    }


	public function onJoin(PlayerJoinEvent $event){
	    //if($event->isCancelled())return false;
	    $this->plugin->API->CleanPlayerRenderChunk($event->getPlayer());
	    return true;
    }
    public function onQuit(PlayerQuitEvent $event){
        //if($event->isCancelled())return false;
        $this->plugin->API->CleanPlayerRenderChunk($event->getPlayer());
        return true;
    }
	public function onPlayerTouch(PlayerInteractEvent $event){
        if($event->isCancelled())return false;
		//var_dump($event->getAction());
		if($event->getAction() == PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			if($this->plugin->API->IsSelectMode($event->getPlayer()->getName()) && $this->plugin->API->IsSelectTool($event->getItem())){
				$LevelID = $event->getBlock()->getLevel()->getId();
				if(!isset($this->plugin->API->StartSelectPoint) or $this->plugin->API->IsSelectPointEffective()){
					$this->plugin->API->SelectFirstPoint($LevelID,$event->getBlock(),$event->getPlayer());
				}elseif(!isset($this->plugin->API->EndSelectPoint)){
					if($this->plugin->API->SelectSecondPoint($LevelID,$event->getBlock(),$event->getPlayer())){
						echo('选择完毕');
					}else{
						echo('选择出错');
					}
				}
			}
		}
    }
}
?>