<?php
declare(strict_types = 1);
namespace BlueWEMT\scheduler;
use pocketmine\math\Vector3;
use pocketmine\Server;
use BlueWEMT\API;
class CacheGenerateScheduler extends WordEditorScheduler{
	/** @var int */
	private $LevelID;
	/** @var array */
	private static $ChunkDataList=array();
	/** @var Vector3 */
	private $DatumPoint;
	/** @var String */
	private $FilePath;
	//@override
    public function __construct(int $LevelID, Vector3 $StartPoint, Vector3 $EndPoint, Vector3 $DatumPoint, string $TaskID, string $FilePath = 'mem'){
		$this->LevelID = $LevelID;
		$this->TaskID = $TaskID;
		$this->DatumPoint = API::GetIntPoint($DatumPoint);
		if(isset($FilePath)){
			$this->FilePath = $FilePath;
		}else{
			return false;
		}
		return($this->ChunkSplit($StartPoint,$EndPoint));
	}
	//根据指令从不同区块中读入数据(异步)并在完成后整合在一起
	public static function ChunkReadCallback(string $TaskID,int $ChunkX,int $ChunkZ,array $data){
		if(isset(self::$ChunkDataList[$TaskID]['ChunkData'][$ChunkX][$ChunkZ])){
			self::$ChunkDataList[$TaskID]['ChunkData'][$ChunkX][$ChunkZ] = $data;
			self::$ChunkDataList[$TaskID]['ChunkLeft']--;
			if(self::$ChunkDataList[$TaskID]['ChunkLeft'] == 0){
				Server::getInstance()->getScheduler()->scheduleAsyncTask(new \BlueWEMT\ATask\BlocksDataSaveATask($TaskID,self::$ChunkDataList[$TaskID]['ChunkData'],self::$ChunkDataList[$TaskID]['FilePath']));
				unset(self::$ChunkDataList[$TaskID]);
			}
			return true;
		}else{
			return false;
		}
		
	}
	public static function BlocksDataSaveCallback(string $TaskID,int $Status){
		//TODO 什么鬼的处理
        self::CallBack($TaskID,'CacheGenerate',array());
		echo('完事');
	}
	//@override
	public function RunTask($Async = false){
		self::$ChunkDataList[$this->TaskID]['LevelID'] = $this->LevelID;
		self::$ChunkDataList[$this->TaskID]['ChunkLeft'] = 0;
		self::$ChunkDataList[$this->TaskID]['FilePath'] = $this->FilePath;
		foreach($this->ChunkPosList as $ChunkPos){
            $level = Server::getInstance()->getLevel($this->LevelID);
            $Chunk = $level->getChunk($ChunkPos[0],$ChunkPos[1],true);
			self::$ChunkDataList[$this->TaskID]['ChunkData'][$ChunkPos[0]][$ChunkPos[1]] = array();
			self::$ChunkDataList[$this->TaskID]['ChunkLeft']++;

			Server::getInstance()->getScheduler()->scheduleAsyncTask(new \BlueWEMT\ATask\ChunkCacheGenerateATask($this->TaskID,$Chunk,$ChunkPos[2],$ChunkPos[3],$this->DatumPoint,$Chunk->getEntities(),$Chunk->getTiles()));
		}
	}
}
?>