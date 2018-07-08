<?php
declare(strict_types = 1);
namespace BlueWEMT\scheduler;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\level\format\Chunk;
use BlueWEMT\API;
use pocketmine\tile\Tile;
use pocketmine\entity\Entity;
class AWordEditorScheduler extends WordEditorScheduler{
	/** @var int */
	private $LevelID;
	/** @var String */
	private $WorkMode;
	/** @var int */
	private $WorkID;
	/** @var int */
	private $WorkData;
	/** @var int */
	private $WorkID2;
	/** @var int */
	private $WorkData2;
    /** @var array */
    private static $ChunkDataCache = array();
    /** @var array */
    private static $ChunkDataList = array();

	public function __construct(string $TaskID,int $LevelID,Vector3 $StartPoint,Vector3 $EndPoint,string $WorkMode = "C",int $WorkID = 0,int $WorkData = 0,int $WorkID2 = 0,int $WorkData2 = 0){
        self::$ChunkDataCache[$TaskID] = array();
	    $this->TaskID = $TaskID;
	    $this->LevelID = $LevelID;
		$this->WorkMode = $WorkMode;
		$this->WorkID = $WorkID;
		$this->WorkData = $WorkData;
		$this->WorkID2 = $WorkID2;
		$this->WorkData2 = $WorkData2;
		$this->ChunkSplit($StartPoint,$EndPoint);
		
	}
    public static function RunTaskCallback(Chunk $Chunk,Level $Level,string $TaskID,string $SubtaskID){
        self::ReaddChunkTileAndEntity($Chunk,self::$ChunkDataCache[$TaskID][$SubtaskID]['Tile'],self::$ChunkDataCache[$TaskID][$SubtaskID]['Entity']);
        unset(self::$ChunkDataCache[$TaskID][$SubtaskID]);

        if(self::$ChunkDataCache[$TaskID] === array()){
            unset(self::$ChunkDataCache[$TaskID]);
        }


        self::DoSetChunk($Level,$Chunk);
        self::$ChunkDataList[$TaskID]['ChunkLeft']--;
        if(self::$ChunkDataList[$TaskID]['ChunkLeft'] === 0){
            self::CallBack($TaskID,'AWordEditor',array());
            unset(self::$ChunkDataList[$TaskID]);
        }


    }
	public function RunTask($Async = false){
		//var_dump($this->ChunkPosList);
		foreach($this->ChunkPosList as $ChunkPos){
			$level = Server::getInstance()->getLevel($this->LevelID);
			$Chunk = $level->getChunk($ChunkPos[0],$ChunkPos[1],true);
			$_SubTaskID = $this->AssignNewSubTaskID();
			if(!$Async){
                self::$ChunkDataCache[$this->TaskID][$_SubTaskID]['Entity'] = array();
                self::$ChunkDataCache[$this->TaskID][$_SubTaskID]['Tile'] = array();
                self::$ChunkDataCache[$this->TaskID][$_SubTaskID]['Tile'] = $Chunk->getTiles();
                self::$ChunkDataCache[$this->TaskID][$_SubTaskID]['Entity'] = $Chunk->getEntities();
            }
            if(!isset(self::$ChunkDataList[$this->TaskID]['ChunkLeft'])){
                self::$ChunkDataList[$this->TaskID]['ChunkLeft'] = 0;
            }
            self::$ChunkDataList[$this->TaskID]['ChunkLeft']++;
			Server::getInstance()->getScheduler()->scheduleAsyncTask(new \BlueWEMT\ATask\ChunkWorkerATask($level,$Chunk,array($ChunkPos[2],$ChunkPos[3],$this->WorkMode,$this->WorkID,$this->WorkData,$this->WorkID2,$this->WorkData2),$this->TaskID,$_SubTaskID));
		}
		return true;
	}
}
?>