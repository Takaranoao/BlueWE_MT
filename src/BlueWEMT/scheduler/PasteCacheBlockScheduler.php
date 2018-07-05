<?php
declare(strict_types = 1);
namespace BlueWEMT\scheduler;
use pocketmine\math\Vector3;
use pocketmine\Server;
use BlueWEMT\ATask\LoadCacheBlockFileATask;
use BlueWEMT\ATask\BlocksDataPartitionATask;
use BlueWEMT\ATask\ChunkApplyBlocksDataATask;
use pocketmine\level\format\Chunk;
class PasteCacheBlockScheduler{
	/** @var String */
	private $TaskID;
	/** @var int */
	private $LevelID;
	/** @var Vector3 */
	private $DatumPoint;
    /** @var array */
    private static $TaskDataList;
	//读入方块数据然后拆分给每个区块处理
	public function __construct(int $LevelID,Vector3 $DatumPoint,string $TaskID,string $FilePath = 'mem'){
		$this->LevelID = $LevelID;
		$this->TaskID = $TaskID;
		$this->DatumPoint = $DatumPoint;
		if(isset($FilePath)){
			$this->FilePath = $FilePath;
			return true;
		}else{
			return false;
		}
	}
    public static function ChunkApplyBlocksDataCallBack(string $TaskID,Chunk $Chunk){
		$level = Server::getInstance()->getLevel(self::$TaskDataList[$TaskID]['LevelID']);
		$level->setChunk($Chunk->getX(),$Chunk->getZ(),$Chunk);
        $level->populateChunk($Chunk->getX(), $Chunk->getZ());
	    //TODO 继续处理
        echo('粘贴完毕');
    }
    public static function BlocksDataPartitionCallBack (string $TaskID,array $PartitionData){
	    //string $TaskID,Chunk $Chunk,array $BlocksData
        if(!isset(self::$TaskDataList[$TaskID]['LevelID'])) return false;
        $level = Server::getInstance()->getLevel(self::$TaskDataList[$TaskID]['LevelID']);
        if(!isset($PartitionData['ChunkPos']))return false;
		var_dump($PartitionData);
        foreach($PartitionData['ChunkPos'] as $ChunkPos) {
            //$ChunkPos->['x']，['z']
            $_Chunk = $level->getChunk($ChunkPos['x'], $ChunkPos['z']);
            $_BlocksData = $PartitionData['ChunkPoint'][$ChunkPos['x']][$ChunkPos['z']];
            Server::getInstance()->getScheduler()->scheduleAsyncTask(new ChunkApplyBlocksDataATask($TaskID,$_Chunk,$_BlocksData));
        }
    }
	public static function LoadCacheBlockFileCallBack (string $TaskID,array $BlocksData){
        if(!isset(self::$TaskDataList[$TaskID])) return false;
        Server::getInstance()->getScheduler()->scheduleAsyncTask(new BlocksDataPartitionATask($TaskID,$BlocksData,self::$TaskDataList[$TaskID]['DatumPoint']));
	    return true;
	}
	public function RunTask(){
        self::$TaskDataList[$this->TaskID]['LevelID'] = $this->LevelID;
        self::$TaskDataList[$this->TaskID]['DatumPoint'] = $this->DatumPoint;
        Server::getInstance()->getScheduler()->scheduleAsyncTask(new LoadCacheBlockFileATask($this->TaskID,$this->FilePath));
    }
}