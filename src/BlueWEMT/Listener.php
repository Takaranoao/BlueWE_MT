<?php
namespace BlueWEMT;
use pocketmine\Server;
use pocketmine\plugin\Plugin;


use pocketmine\event\player\PlayerInteractEvent;

class Listener implements \pocketmine\event\Listener{
	/** @var Plugin */
	private $plugin;
	public function __construct(Plugin $plugin){
		$this->plugin = $plugin;
		Server::getInstance()->getPluginManager()->registerEvents($this, $plugin);//注册事件> <
	}
	
	public function onPlayerTouch(PlayerInteractEvent $event){
		//var_dump($event->getAction());
		if($event->getAction() == PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			if($this->plugin->API->IsSelectMode() && $this->plugin->API->IsSelectTool($event->getItem())){
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