<?php
namespace BlueWEMT;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
/*
结构:
$request=[头标识][操作类型][ARR([x][y][z][（ARR?）data])]
操作类型目前有:id替换，清空，完全替换，重置区块
本插件只负责传递事件到BlueVIP核心
*/


class main extends \pocketmine\plugin\PluginBase
{
	/** @var Listener */
	public $Listener;
	/** @var API */
	public $API;
	/** @var Main **/
    public static $instance;

    public static function getInstance(){
        return self::$instance;
    }
    //@override
	public function onLoad()
	{
	
	}
	
	//请一定要按照规范的格式来写
	//@override
	public function onEnable()
	{
        self::$instance = $this;
		$this->getLogger()->info(TextFormat::GREEN.'github:'. TextFormat::GOLD .'https://github.com/baolan-zifu/BlueWE_MT');
		$this->getLogger()->info(TextFormat::AQUA.'觉得好用上去点个星星呗～遵循 LGPLV3 协议开源');
		$this->getLogger()->info(TextFormat::GOLD.'目前版本还不稳定，请多包涵～');
		$this->API = new API($this);
		$this->Listener = new Listener($this);
	}
	
	/**
	 * @param CommandSender $sender
	 * @param Command       $command
	 * @param string        $label
	 * @param array         $args
	 *
	 * @return bool
	 */
	//@override
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args):bool{
		$cmd = strtolower($command->getName());
		$params = $args;
		if(!($sender instanceof Player)){
			$sender->sendMessage($this->API->getMessage('RunThisCommandIn-game'));
			return true;
		}
		if($this->API->GetConfigItem('OnlyOPCanUseCommands') && !$sender->isOp()){
            $sender->sendMessage($this->API->getMessage('Permission_denied-onCommand'));
            return false;
        }
		if($cmd{0} === "/"){
			$cmd = substr($cmd, 1);
		}

		if($cmd != 'select'
            && $cmd != 'paste'
            && !$this->API->IsSelectPointEffective()){
			$this->API->SendMessage($sender,'SelectPointNotEffective');
			return true;
		}
		switch($cmd){
			case 'replace':
			    if(!isset($params[1])){
			    	$this->API->SendMessage($sender,'ReplaceMissingParameter');
			    	return false;
			    }
            case 'set':
			case 'supersede':
			case 'fill':
                if(!isset($params[0])){
                    $this->API->SendMessage($sender,'MissingParameter');
                    return false;
                }
			    $items = Item::fromString($params[0], true);
			    $bcount = count($items) - 1;
			    if($bcount < 0){
				    $this->API->SendMessage($sender,'IncorrectblocksNumber');
				    return false;
			    }
			    if($items){
				    foreach($items as $item){
					    if($item->getID() > 0xff or $item->getDamage() > 0x0f){
						    $this->API->SendMessage($sender,'Incorrectblock');
						    return false;
					    }
				    }
				    $item = $items[mt_rand(0, $bcount)];
				    if($item->getID() != 0){
					    if($cmd == 'supersede'){
                            $this->API->SendMessage($sender,'StartSupersede');
						    $this->API->WordEditorSchedulerUseSingleThreadAndRunIt($sender->getName(),$this->API->getMessage('EndSupersede'),"S",$item->getID(),$item->getDamage());
					    }elseif($cmd == 'fill'){
                            $this->API->SendMessage($sender,'StartFill');
						    $this->API->WordEditorSchedulerUseSingleThreadAndRunIt($sender->getName(),$this->API->getMessage('EndFill'),"F",$item->getID(),$item->getDamage());
					    }elseif($cmd == 'set'){
                            $this->API->SendMessage($sender,'StartSet');
						    $this->API->WordEditorSchedulerUseSingleThreadAndRunIt($sender->getName(),$this->API->getMessage('EndSet'),"P",$item->getID(),$item->getDamage());
					    }elseif($cmd == 'replace'){
						    $items2 = Item::fromString($params[1], true);
						    if($items2){
							    $bcnt = count($items2) - 1;
							    if($bcnt < 0){
								    $this->API->SendMessage($sender,'Incorrectblocks-replace');
								    return false;
							    }
							    foreach($items2 as $item2){
								    if($item2->getID() > 0xff or $item2->getDamage() > 0x0f){
									    $this->API->SendMessage($sender,'Incorrectblock-replace');
									    return false;
							    	}
							    }
							    $item2 = $items2[mt_rand(0, $bcnt)];
                                $this->API->SendMessage($sender,'StartReplace');
							    $this->API->WordEditorSchedulerUseSingleThreadAndRunIt($sender->getName(),$this->API->getMessage('EndReplace'),"R",$item2->getID(),$item2->getDamage(),$item->getID(),$item->getDamage());//倒过来更符合习惯
						    }else{
							    $this->API->SendMessage($sender,'Incorrectblock-UseID-replace');
						    }
					    }else{
						    $this->API->SendMessage($sender,'Subcommandtan90');
					    }
				    }else{
					    if($cmd == 'fill'){
						
					    }elseif($cmd == 'replace'){
						    $this->API->SendMessage($sender,'PleaseUseOtherCommand',array('fill'));//TODO
					    }elseif($cmd == 'supersede' or $cmd == 'set'){
                            $this->API->SendMessage($sender,'StartClean');
						    $this->API->WordEditorSchedulerUseSingleThreadAndRunIt($sender->getName(),$this->API->getMessage('EndClean'),"C",$item->getID(),$item->getDamage());
					    }
					
				    }
			
			    } else {
				    $this->API->SendMessage($sender,'Incorrectblock-UseID');
			    }
			    return true;
			    break;
			case 'copy':
            case 'paste':
			    if(isset($params[0])){
			        $_FileName = $params[0];
                }else{
                    $_FileName = 'mem';
                }
				if(strlen($_FileName) > 32 || strlen($_FileName) < 1){
					$this->API->SendMessage($sender,'FileName_not_match');
					return false;
				}
                if(!ctype_alnum($_FileName)){
                    $this->API->SendMessage($sender,'FileName_not_match');
                    return false;
                }
                if($_FileName != 'mem'){
                    $_FileName = API::$SavedBlocksPath.DIRECTORY_SEPARATOR.$_FileName.'.szb';
                }
			    //API::$SavedBlocksPath.'/Test.szb'
                if($cmd == 'copy'){
                    $this->API->SetDatumPoint($sender);
                    $this->API->SendMessage($sender,'StartCopy');
                    $this->API->CacheGenerateSchedulerUseSingleThreadAndRunIt($sender->getName(),$this->API->getMessage('EndCopy'),$_FileName);
                }elseif($cmd == 'paste'){
                    $this->API->SendMessage($sender,'StartPaste');
                    $this->API->PasteCacheBlockSchedulerUseSingleThreadAndRunIt($sender->getName(),$this->API->getMessage('EndPaste'),$sender->getLevel(),$sender,$_FileName);
                }else{
                    return false;
                }
                return true;
			    break;
            case 'select':
                $this->API->ClearSelectPoint();
                $this->API->SetSelectMode($sender->getName());
                return true;
                break;
            case 'cleanchunk':
                //$sender->getLevel()->getChunk(,)
		}
		return false;
	}
}
