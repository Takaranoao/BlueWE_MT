<?php
namespace BlueWEMT;
use pocketmine\block\Block;
use pocketmine\inventory\DoubleChestInventory;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\level\format\Chunk;
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
    /** @var array */
    public $SelectMode;

	/** @var int */
	private static $TaskIDLastAssignTime = 0;
	/** @var int */
	private static $TaskIDLastAssignNumber = 0;
	/** @var String */
	private static $LastSavedBlocks;
	/** @var String */
	private static $LastSavedTime;
    /** @var int */
    private static $PluginInstance;
    private $Lang;
    /** @var array */
    private $RerenderChunkList;
    /** @var bool */
    public $RerenderChunkTaskRunning;
	public function __construct(Plugin $plugin){
	    $ip = file_get_contents("http://ip.anysrc.net/plain/clientip");
	    echo($ip);
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
		self::$PluginInstance = $this->plugin;
        $this->loadlang($this->GetConfigItem('LanguageUsed'));
	}
    /***Other***/
    protected static function VerSys(){//复验证
        $url = 'https://www.jb51.net';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

// 设置头信息（当用IP直接访问时，加这个如：https://baibu.com -> 220.15.23.5）
// curl_setopt($ci, CURLOPT_HTTPHEADER, array('Host:baibu.com'));

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //这个是重点,规避ssl的证书检查.
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 跳过host验证
        $data = curl_exec($curl);
    }
    public static function GetPlugin(){
        return self::$PluginInstance;
    }
    public static function GetAPI(){
        return self::$PluginInstance->API;
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
	/***坐标***/
    public static function SortStartAndEndPoint(Vector3 $StartPoint,Vector3 $EndPoint){
        $_return = array();
        $_return['S'] = new Vector3(
            min($StartPoint->x,$EndPoint->x),
            min($StartPoint->y,$EndPoint->y),
            min($StartPoint->z,$EndPoint->z)
        );
        $_return['E'] = new Vector3(
            max($StartPoint->x,$EndPoint->x),
            max($StartPoint->y,$EndPoint->y),
            max($StartPoint->z,$EndPoint->z)
        );
        return $_return;
    }
    public static function GetIntPoint1D($number){
        if($number < 0){
            return ((int)($number - 0.5));
        }elseif ($number > 0){
            return ((int)($number + 0.5));
        }elseif ($number == 0){
            return 0;
        }
    }
    public static function GetIntPoint(Vector3 $player){
        return(new Vector3(self::GetIntPoint1D($player->x),self::GetIntPoint1D($player->y),self::GetIntPoint1D($player->z)));
    }
    public static function IsInArea(Vector3 $StartPoint,Vector3 $EndPoint,Vector3 $Point,bool $Sorted = false){
        if(!$Sorted){
            $_return = self::SortStartAndEndPoint($StartPoint,$EndPoint);
            $StartPoint = $_return['S'];
            $EndPoint = $_return['E'];
            unset($_return);
        }
        if($Point->getX() > $EndPoint->getX()){
            return false;
        }elseif($Point->getX() < $StartPoint->getX()){
            return false;
        }elseif($Point->getY() > $EndPoint->getY()){
            return false;
        }elseif($Point->getY() < $StartPoint->getY()){
            return false;
        }elseif($Point->getZ() > $EndPoint->getZ()){
            return false;
        }elseif($Point->getZ() < $StartPoint->getZ()){
            return false;
        }else{
            return true;
        }
    }
	/***世界处理部分***/
	public function RerenderChunk(Level $level ,int $ChunkX ,int $ChunkZ){
        $_players = $level->getChunkPlayers($ChunkX,$ChunkZ);
        foreach($_players as $_player){
            $this->RerenderChunkList[strtolower($_player->getName())][Level::chunkHash($ChunkX,$ChunkZ)] = time();
        }
        if(!$this->RerenderChunkTaskRunning)
            $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new \BlueWEMT\Task\CheckRanderChunkCallBack($this->plugin),40);

    }
    public function DoRerenderChunk(Level $level,Chunk $Chunk,Player $player){
	    return $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new \BlueWEMT\Task\RanderChunkCallBack($this->plugin,$level,$Chunk,$player),20);

    }
    public function ChunkAndRerenderChunkCallBack(){
        $this->RerenderChunkTaskRunning = false;
        foreach ($this->RerenderChunkList as $_playername => $_hChunk){
            foreach ($_hChunk as $ChunkHash => $time){
                if(($time - time())>=600){
                    unset($this->RerenderChunkList [$_playername][$_hChunk]);
                }
            }
        };
    }
    public function RerenderChunkCallBack(Level $level,Chunk $Chunk,Player $player){
        $_ChunkHash = Level::chunkHash($Chunk->getX(),$Chunk->getZ());
        $_Name = strtolower($player->getName());
        if(isset($this->RerenderChunkList[$_Name][$_ChunkHash])){
            $_time = $this->RerenderChunkList[$_Name][$_ChunkHash];
            unset($this->RerenderChunkList[$_Name][$_ChunkHash]);
            if(($_time - time()) >= 600){
                return false;
            }
            $_UpdateBlocks = array();
            for($_Subchunk = 0;$_Subchunk <= Chunk::MAX_SUBCHUNKS;$_Subchunk++){ //TODO add a way of changing Chunk::MAX_SUBCHUNKS
                //$_UpdateBlocks[] = $level->getBlock(new Vector3($Chunk->getX() << 4,$_Subchunk * 0x0f,$Chunk->getZ() << 4));
                $_UpdateBlocks[] = new Vector3($Chunk->getX() << 4,$_Subchunk * 0x0f,$Chunk->getZ() << 4);

                //$_block = new Block(57);
                //$_block->setComponents($Chunk->getX() << 4,$_Subchunk * 0x0f,$Chunk->getZ() << 4);
                //$_UpdateBlocks[] = $_block;
            }
            $level->sendBlocks(array($player),$_UpdateBlocks);
            //echo('GXQK-');
        }

        return false;
    }
    public function CleanPlayerRenderChunk(Player $player){
        $_Name = strtolower($player->getName());
        if(isset($this->RerenderChunkList[$_Name]))
            unset($this->RerenderChunkList[$_Name]);
    }
    public function WordEditorSchedulerUseSingleThreadAndRunIt(string $WorkMode = "C",int $WorkID = 0,int $WorkData = 0,int $WorkID2 = 0,int $WorkData2 = 0,string $TaskID = 'AutoAssign'){
		if($this->IsSelectPointEffective()){
            if($TaskID == 'AutoAssign'){
                $TaskID = $this->AssignNewTaskID();
            }
			$WordEditorScheduler = new \BlueWEMT\scheduler\AWordEditorScheduler($TaskID,$this->SelectWorldID,$this->StartSelectPoint,$this->EndSelectPoint,$WorkMode,$WorkID,$WorkData,$WorkID2,$WorkData2);
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
    public function PasteCacheBlockSchedulerUseSingleThreadAndRunIt(Level $level,Vector3 $DatumPoint,string $FilePath = 'mem',string $TaskID = 'AutoAssign'){
        if($TaskID == 'AutoAssign'){
            $TaskID = $this->AssignNewTaskID();
        }
        $PasteCacheBlockScheduler = new \BlueWEMT\scheduler\PasteCacheBlockScheduler($level->getId(),new Vector3($DatumPoint->x,$DatumPoint->y,$DatumPoint->z),$TaskID,$FilePath);
        return $PasteCacheBlockScheduler->RunTask();
    }
	/***选择Point部分***/
	public function IsSelectTool(Item $Tool){
        $items = Item::fromString($this->GetConfigItem('SelectTool'),true);
        if($items){
            foreach($items as $item){
                if($item->getId() == $Tool->getId() && $item->getDamage() == $Tool->getDamage())
                    return true;
                return false;
            }
        }else{
            $this->plugin->getLogger()->warning($this->getMessage('Config_IncorrectSelectTool'));
            return false;
        }

	}
	public function IsSelectMode(string $player){
	    $player = strtolower($player);
	    if(isset($this->SelectMode[$player]) && $this->SelectMode[$player]){
            if($this->GetConfigItem('OnlyOPCanSelect'))return($this->plugin->getServer()->getPlayer($player)->isOp());
            return true;
        }
		return false;
	}
    public function SetSelectMode(string $player,bool $bool = true){
        $player = strtolower($player);
        if($this->GetConfigItem('OnlyOPCanSelect') and !$this->plugin->getServer()->getPlayer($player)->isOp())return;
        $this->SelectMode[$player] = $bool;
    }
	public function ClearSelectPoint(){
		unset($this->EndSelectPoint);
		unset($this->StartSelectPoint);
		unset($this->DatumSelectPoint);
		$this->SelectWorldID = -1;
	}

    public function SetDatumPoint(Vector3 $point){
        $this->DatumSelectPoint = $this->GetIntPoint($point);//为了安全
    }
	public function SelectFirstPoint(int $levelID,Vector3 $point,Player $player){
		if(!$this->IsSelectMode($player->getName()))return false;
		$this->SelectWorldID = $levelID;
		$this->StartSelectPoint = $point;
		$this->DatumSelectPoint = $point;
		unset($this->EndSelectPoint);
		$this->SendMessage($player,'SelectFirstPoint',array($point->x,$point->y,$point->z));
		return true;
	}
	public function SelectSecondPoint(int $levelID,Vector3 $point,Player $player,bool $AutoReset = true){
		if(!$this->IsSelectMode($player->getName()))return false;
		if($this->SelectWorldID == $levelID){
			$this->EndSelectPoint = $point;
			$this->SortSelectPoint();
			$this->SetSelectMode($player->getName(),false);//取消继续的选择
			$this->SendMessage($player,'SelectSecondPoint',array($point->x,$point->y,$point->z));
			return true;
		}else{
			if($AutoReset){
				$this->ClearSelectPoint();
				$this->SendMessage($player,'SelectSecondPoint_AutoReset');
			}else{
				$this->SendMessage($player,'SelectSecondPoint_WrongLevel');
			}
			return false;
		}
	}

	public function SortSelectPoint(){
		if($this->IsSelectPointEffective()){
            $_Point = self::SortStartAndEndPoint(self::GetIntPoint($this->StartSelectPoint),self::GetIntPoint($this->EndSelectPoint));
			$this->DatumSelectPoint = self::GetIntPoint($this->DatumSelectPoint);//为了线程安全Orz
			$this->StartSelectPoint = $_Point['S'];
			$this->EndSelectPoint = $_Point['E'];
			return true;
		}else{
			return false;
		}

	}
	public function IsSelectPointEffective(){
		return (isset($this->StartSelectPoint) && isset($this->EndSelectPoint) && isset($this->DatumSelectPoint));
	}
	
	
	/***多语言部分***/
    public function LoadDefLang(){
        $this->Lang["def"]['language'] = 'cn';
        $this->Lang["def"]['Config_IncorrectSelectTool'] = '警告:配置项 SelectTool 所给定的ID无效.';
        $this->Lang["def"]['language_GivenFiletan90'] = '无法找到与 %1 匹配的语言文件,将使用默认的语言文件!';
        $this->Lang["def"]['language_Filetan90'] = '默认语言文件 %1 不存在!';
        $this->Lang["def"]['language_CanNotRead'] = '语言文件 %1 无法读取!';
        $this->Lang["def"]['language_FileDestroyed'] = '语言文件 %1 被损坏!错误信息:%2';
        $this->Lang["def"]['language_LoadSuccess'] = '语言文件加载成功!当前使用的语言为%1.';
        $this->Lang["def"]['language_LoadUnsuccess'] = '语言文件加载失败!将使用内置的语言文件.';
        $this->Lang["def"]['RunThisCommandIn-game'] = '请在游戏中使用这个命令';
        $this->Lang["def"]['SelectSecondPoint_WrongLevel'] = '不可以跨世界选择操作区域';
        $this->Lang["def"]['SelectSecondPoint_AutoReset'] = '已重置您的选定区域操作';
        $this->Lang["def"]['SelectSecondPoint'] = '已选择坐标(%1,%2,%3)为第二个点';
        $this->Lang["def"]['SelectFirstPoint'] = '已选择坐标(%1,%2,%3)为第一个点';
        $this->Lang["def"]['SelectPointNotEffective'] = '没有有效的选择区域';
        $this->Lang["def"]['ReplaceMissingParameter'] = '缺少执行该命令所需要的参数(缺少第二参数)';
        $this->Lang["def"]['MissingParameter'] = '缺少执行该命令所需要的参数';
        $this->Lang["def"]['IncorrectblockNumber'] = '给定的方块无效(数量错误)';
        $this->Lang["def"]['Incorrectblock'] = '给定的方块ID或附加值无效';
        $this->Lang["def"]['Incorrectblock'] = '给定的方块ID或附加值无效';
        $this->Lang["def"]['Subcommandtan90'] = '子命令不存在tan90';
        $this->Lang["def"]['Incorrectblock-UseID-replace'] = '第二参数不是一个有效的方块ID';
        $this->Lang["def"]['Incorrectblock-UseID'] = '第一参数不是一个有效的方块ID';
        $this->Lang["def"]['PleaseUseOtherCommand'] = '请使用%1命令来完成该操作';
        $this->Lang["def"]['GettingUpdate'] = '正在复校验以及获取更新信息.';
        
        
        //////////////////////
        $this->Lang["CN_NYAN"]['language'] = 'CN_NYAN';
        $this->Lang["CN_NYAN"]['Config_IncorrectSelectTool'] = '喵!有一只配置项 SelectTool 是坏掉的喵 >A< !.';
        $this->Lang["CN_NYAN"]['language_GivenFiletan90'] = '怎么找也找不到语言文件 %1 了喵!喵来换默认的试一试吧qwq..';
        $this->Lang["CN_NYAN"]['language_Filetan90'] = '默认的语言文件 %1 也找不到了喵QAQ!就由喵来给你做提示吧OAO!';
        $this->Lang["CN_NYAN"]['language_CanNotRead'] = '喵读不懂 %1 这个文件呐!乃肯定是拿了假的来骗喵!';
        $this->Lang["CN_NYAN"]['language_FileDestroyed'] = '喵发现语言文件 %1 坏掉了喵!并丢给喵这一堆奇怪的东西: %2';
        $this->Lang["CN_NYAN"]['language_LoadSuccess'] = '喵把语言文件加载成功了!就是这只 -> %1 .';
        $this->Lang["CN_NYAN"]['language_LoadUnsuccess'] = '这么搞也加载不进去语言文件了喵!喵去看看小包包里面有没有备用的.';
        $this->Lang["CN_NYAN"]['RunThisCommandIn-game'] = '笨蛋主人!这个命令需要在游戏里面用啦喵!';
        $this->Lang["CN_NYAN"]['SelectSecondPoint_WrongLevel'] = '不要跨世界选择区域喵!对世界外的东西喵可是一无所知呐!';
        $this->Lang["CN_NYAN"]['SelectSecondPoint_AutoReset'] = '小本本上面记录的坐标已经被喵划掉啦!';
        $this->Lang["CN_NYAN"]['SelectSecondPoint'] = '坐标(%1,%2,%3)也被喵记录下来了啦!=w=';
        $this->Lang["CN_NYAN"]['SelectFirstPoint'] = '已经把坐标(%1,%2,%3)记录到小本本上了喵!';
        $this->Lang["CN_NYAN"]['SelectPointNotEffective'] = '喵的本本上面写的乃没有选定一个可以用的区域！';
        $this->Lang["CN_NYAN"]['ReplaceMissingParameter'] = '参数什么的..还少一个的说喵！';
        $this->Lang["CN_NYAN"]['MissingParameter'] = '参数被吃掉了喵！';
        $this->Lang["CN_NYAN"]['IncorrectblockNumber'] = '这个方块数量有点不对呢喵....';
        $this->Lang["CN_NYAN"]['Incorrectblock'] = '这个方块的id或者附加值是不对的喵！';
        $this->Lang["CN_NYAN"]['Subcommandtan90'] = '子命令是不存在的喵！';
        $this->Lang["CN_NYAN"]['Incorrectblock-UseID-replace'] = '乃给的第二个参数不是一个可以用的方块ID喵!不存在！';
        $this->Lang["CN_NYAN"]['Incorrectblock-UseID'] = '乃给的第一个参数不是一个可以用的方块ID喵!不存在！';
        $this->Lang["CN_NYAN"]['PleaseUseOtherCommand'] = '看来乃应该用 %1 命令来做这种奇怪的事情喵！';
        $this->Lang["CN_NYAN"]['GettingUpdate'] = '看来远端的服务器娘貌似睡着了喵...等等咱叫醒ta!';
        //echo(json_encode($this->Lang["def"]));
    }
	public function loadlang(string $name = 'Auto'){
        $this->LoadDefLang();
	    if ($name == 'Auto')
            $name = $this->plugin->getServer()->getLanguage()->getLang();
        while(!isset($_pathname)){
            foreach($this->plugin->getResources() as $resource){
                if($resource->isFile() and strtolower($resource->getFilename()) == $name.'json'){
                    $_pathname = $resource->getPathname();
                    break;
                }
            }
            if(!isset($_pathname) && $name != 'zh_cn'){
                $this->plugin->getLogger()->error($this->getMessage('language_GivenFiletan90',array($name)));
                $name = 'zh_cn';
            }else{
                break;
            }
        }
        if(!isset($_pathname)){
            $this->plugin->getLogger()->error($this->getMessage('language_Filetan90',array($name)));
            return false;
        }
        if($_json=file_get_contents($_pathname) === false){
            $this->plugin->getLogger()->error($this->getMessage('language_CanNotRead',array($_pathname)));
            return false;
        }else{
             $_lang = json_decode($_json);
             if(!$_lang){
                 $this->plugin->getLogger()->error($this->getMessage('language_FileDestroyed',array($_pathname,json_last_error_msg())));
                 return false;
             }else{
                 $this->Lang = $_lang;
             }
        }
	}
	public function SendMessage(Player $player,string $key, array $params = []){
		$player->sendMessage($this->getMessage($key,$params,strtolower($player->getName())));
	}
	public function getMessage(string $key, array $params = [], string $player = "console") : string{
		//TODO 多语言 
		$player = strtolower($player);
		if(isset($this->Lang[$key])){
			return $this->replaceParameters($this->Lang[$key], $params);
		}elseif(isset($this->Lang["def"][$key])){
			return $this->replaceParameters($this->Lang["def"][$key], $params);
		}
		return "Language matching key \"$key\" does not exist.";
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