<?php
namespace BlueWEMT;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
class API{
	/** @var string */
	public static $Path;
	/** @var string */
	public static $SavedBlocksPath;
	/** @var string */
	public static $CachePath;
	/** @var Plugin */
	private $plugin;
	/** @var Vector3 */
	public $StartSelectPoint;
	/** @var Vector3 */
	public $EndSelectPoint;
	/** @var Vector3 */
	public $DatumSelectPoint;
	/** @var int */
	public $SelectWorldID;
	/** @var int */
	private static $TaskIDLastAssignTime = 0;
	/** @var int */
	private static $TaskIDLastAssignNumber = 0;
	/** @var String */
	private static $LastSavedBlocks;
	/** @var String */
	private static $LastSavedTime;
	public function __construct(Plugin $plugin){
		$this->plugin = $plugin;
		self::$Path = $this->plugin->getDataFolder();
		self::$SavedBlocksPath = self::$Path.'/SavedBlocks';
		self::$CachePath = self::$Path.'/Cache';
		if(!file_exists(self::$Path)){
			mkdir(self::$Path,0777,true);
		}
		if(!file_exists(self::$SavedBlocksPath)){
			mkdir(self::$SavedBlocksPath,0777,true);
		}
		if(!file_exists(self::$CachePath)){
			mkdir(self::$CachePath,0777,true);
		}
		$this->plugin->getConfig();
	}
	/***配置文件***/
	public function SaveConfig(){
        return $this->plugin->saveConfig();
    }
    public function SaveDefaultConfig(){
        return $this->plugin->saveDefaultConfig();
    }
    public function GetConfigItem($key = 'DEFAULT'){
        return $this->GetConfig()->get($key);
    }
    public function SetConfigItem($key = 'DEFAULT',$v = true){
        $this->GetConfig()->set($key,$v);
    }
    protected function GetConfig(){
        return $this->plugin->getConfig();
    }
	/***方块剪贴板***/
	public static function WriteBlocksData(string $BlocksData){
		self::$LastSavedBlocks = $BlocksData;
		self::$LastSavedTime = time();
	}
	public static function ReadBlocksData(){
		return(self::$LastSavedBlocks);
	}
	public static function ReadLastBlocksSavedTime(){
		return(self::$LastSavedTime);
	}
	/***TASKID***/
	
	public static function AssignNewTaskID(){
		$_nowtime = time();
		$_nowtime -= 920649600;
		if(self::$TaskIDLastAssignTime < $_nowtime){
			self::$TaskIDLastAssignNumber = 0;
            self::$TaskIDLastAssignTime = $_nowtime;
		}else{
			self::$TaskIDLastAssignNumber++;
		}
		return(self::$TaskIDLastAssignNumber.$_nowtime);
	}
	/***世界处理部分***/
	public function WordEditorSchedulerUseSingleThreadAndRunIt(string $WorkMode = "C",int $WorkID = 0,int $WorkData = 0,int $WorkID2 = 0,int $WorkData2 = 0){
		if($this->IsSelectPointEffective()){
			$WordEditorScheduler = new \BlueWEMT\scheduler\AWordEditorScheduler($this->SelectWorldID,$this->StartSelectPoint,$this->EndSelectPoint,$WorkMode,$WorkID,$WorkData,$WorkID2,$WorkData2);
			return $WordEditorScheduler->RunTask();
		}else{
			return false;
		}

	}

	public function CacheGenerateSchedulerUseSingleThreadAndRunIt(string $FilePath = 'mem',string $TaskID = 'AutoAssign'){
		if($this->IsSelectPointEffective()){
			if($TaskID == 'AutoAssign'){
				$TaskID = $this->AssignNewTaskID();
			}
			$CacheGenerateScheduler = new \BlueWEMT\scheduler\CacheGenerateScheduler($this->SelectWorldID,$this->StartSelectPoint,$this->EndSelectPoint,$this->DatumSelectPoint,$TaskID,$FilePath);
			return $CacheGenerateScheduler->RunTask();
		}else{
			return false;
		}

	}
    public function PasteCacheBlockSchedulerUseSingleThreadAndRunIt(Vector3 $DatumPoint,string $FilePath = 'mem',string $TaskID = 'AutoAssign'){
        if($TaskID == 'AutoAssign'){
            $TaskID = $this->AssignNewTaskID();
        }
        $CacheGenerateScheduler = new \BlueWEMT\scheduler\PasteCacheBlockScheduler($this->SelectWorldID,new Vector3($DatumPoint->x,$DatumPoint->y,$DatumPoint->z),$TaskID,$FilePath);
        return $CacheGenerateScheduler->RunTask();
    }
	/***选择Point部分***/
	public function IsSelectTool(Item $Tool){
		return true;//TODO
	}
	public function IsSelectMode(){
		return true;//TODO
	}
	public function ClearSelectPoint(){
		unset($this->EndSelectPoint);
		unset($this->StartSelectPoint);
		unset($this->DatumSelectPoint);
		$this->SelectWorldID = -1;
	}
	public function SelectFirstPoint(int $levelID,Vector3 $point,Player $player){
		if(!$this->IsSelectMode())return false;
		$this->SelectWorldID = $levelID;
		$this->StartSelectPoint = $point;
		$this->DatumSelectPoint = $point;
		unset($this->EndSelectPoint);
		$this->SendMessage($player,'SelectFirstPoint',array($point->x,$point->y,$point->z));//TODO
		return true;
	}
	public function SelectSecondPoint(int $levelID,Vector3 $point,Player $player,bool $AutoReset = true){
		if(!$this->IsSelectMode())return false;
		if($this->SelectWorldID == $levelID){
			$this->EndSelectPoint = $point;
			$this->SortSelectPoint();
			$this->SendMessage($player,'SelectSecondPoint',array($point->x,$point->y,$point->z));//TODO
			return true;
		}else{
			if($AutoReset){
				$this->ClearSelectPoint();
				$this->SendMessage($player,'SelectSecondPoint_AutoReset');//TODO
			}else{
				$this->SendMessage($player,'SelectSecondPoint_WrongLevel');//TODO
			}
			return false;
		}
	}
	public function SortSelectPoint(){
		if($this->IsSelectPointEffective()){
			$StartSelectPoint = new Vector3(
			min($this->StartSelectPoint->x,$this->EndSelectPoint->x),
			min($this->StartSelectPoint->y,$this->EndSelectPoint->y),
			min($this->StartSelectPoint->z,$this->EndSelectPoint->z));
			$EndSelectPoint = new Vector3(
			max($this->StartSelectPoint->x,$this->EndSelectPoint->x),
			max($this->StartSelectPoint->y,$this->EndSelectPoint->y),
			max($this->StartSelectPoint->z,$this->EndSelectPoint->z));
			$this->DatumSelectPoint = new Vector3($this->DatumSelectPoint->x,$this->DatumSelectPoint->y,$this->DatumSelectPoint->z);//为了线程安全Orz
			$this->StartSelectPoint = $StartSelectPoint;
			$this->EndSelectPoint = $EndSelectPoint;
			return true;
		}else{
			return false;
		}

	}
	public function IsSelectPointEffective(){
		return (isset($this->StartSelectPoint) && isset($this->EndSelectPoint) && isset($this->DatumSelectPoint));
	}
	
	
	/***多语言部分***/
	public function SendMessage(Player $player,string $key, array $params = []){
		$player->sendMessage($this->getMessage($key,$params,strtolower($player->getName())));
	}
	public function getMessage(string $key, array $params = [], string $player = "console") : string{
		return $key.implode('-',$params);
		
		//TODO 多语言 
		$player = strtolower($player);
		/*if(isset($this->lang[$this->playerLang[$player]][$key])){
			return $this->replaceParameters($this->lang[$this->playerLang[$player]][$key], $params);
		}elseif(isset($this->lang["def"][$key])){
			return $this->replaceParameters($this->lang["def"][$key], $params);
		}
		return "Language matching key \"$key\" does not exist.";
		*/
	}
	private function replaceParameters($message, $params = []){
		$search = array();
		$replace = array();
		/*
		$search = ["%MONETARY_UNIT%"];
		$replace = [$this->getMonetaryUnit()];
		*/
		for($i = 0; $i < count($params); $i++){
			$search[] = "%".($i + 1);
			$replace[] = $params[$i];
		}
		$colors = [
			"0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d", "e", "f", "k", "l", "m", "n", "o", "r"
		];
		foreach($colors as $code){
			$search[] = "&".$code;
			$replace[] = TextFormat::ESCAPE.$code;
		}
		return str_replace($search, $replace, $message);
	}
}
?>