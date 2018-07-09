<?php
declare(strict_types = 1);
namespace BlueWEMT\scheduler;
use pocketmine\entity\Entity;
use pocketmine\nbt\LittleEndianNBTStream;
use pocketmine\tile\Tile;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBTStream;
use pocketmine\tile\Chest;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;
use BlueWEMT\ATask\LoadCacheBlockFileATask;
use BlueWEMT\ATask\BlocksDataPartitionATask;
use BlueWEMT\ATask\ChunkApplyBlocksDataATask;
use BlueWEMT\API;
use pocketmine\level\format\Chunk;
class PasteCacheBlockScheduler extends WordEditorScheduler{
	/** @var int */
	private $LevelID;
	/** @var Vector3 */
	private $DatumPoint;
    /** @var array */
    private static $TaskDataList;
    /** @var array */
    private static $ChunkDataCache = array();
	//读入方块数据然后拆分给每个区块处理
	public function __construct(int $LevelID,Vector3 $DatumPoint,string $TaskID,string $FilePath = 'mem'){
	    //TODO Entity,Tile,Subtask
		$this->LevelID = $LevelID;
		$this->TaskID = $TaskID;
		$this->DatumPoint = API::GetIntPoint($DatumPoint);
		if(isset($FilePath)){
			$this->FilePath = $FilePath;
			return true;
		}else{
			return false;
		}
	}
    public static function ChunkApplyBlocksDataCallBack(string $TaskID,Chunk $Chunk,array $EntityList,array $TileList,string $SubTaskID){
        if(!isset(self::$TaskDataList[$TaskID])) return false;
        if(!isset(self::$TaskDataList[$TaskID]['LevelID'])) return false;
        if(!isset(self::$TaskDataList[$TaskID]['DatumPoint'])) return false;
	    $level = Server::getInstance()->getLevel(self::$TaskDataList[$TaskID]['LevelID']);
        $DatumPoint = API::GetIntPoint(self::$TaskDataList[$TaskID]['DatumPoint']);
        /*
		foreach($EntityList as $_EntityNBTData){
		    echo('实体读入');
            $_NBT->read($_EntityNBTData);
            $nbt = $_NBT->getData();
            if($nbt instanceof CompoundTag) {

                //TODO 位置
                if (!isset($nbt->id)){
                    continue;
                }
                echo('实体计算');
                $nbt["Pos"][0] = (double)($nbt["Pos"][0] + $DatumPoint->x);
                $nbt["Pos"][1] = (double)($nbt["Pos"][1] + $DatumPoint->y);
                $nbt["Pos"][2] = (double)($nbt["Pos"][2] + $DatumPoint->z);
                if(($nbt["Pos"][0] >> 4) !== $Chunk->getX() or ($nbt["Pos"][2] >> 4) !== $Chunk->getZ()){
                    continue; //Fixes entities allocated in wrong chunks.
                }
                if (($entity = Entity::createEntity($nbt["id"], $level,$nbt)) instanceof Entity) {
                    echo('实体生成'.$entity);
                    //$Chunk->addEntity($entity);
                }
            }
        }*/
        foreach($TileList as $_TileData){
            $nbtReader = new LittleEndianNBTStream();
            $nbt = $nbtReader->read($_TileData);
                //NBTStream::fromArray($_TileArrData);
            if($nbt instanceof CompoundTag){

                if(!$nbt->hasTag(Tile::TAG_ID)){
                    continue;
                }
                $nbt->setInt(Tile::TAG_X,$nbt->getInt(Tile::TAG_X) + $DatumPoint->x,true);
                $nbt->setInt(Tile::TAG_Y,$nbt->getInt(Tile::TAG_Y) + $DatumPoint->y,true);
                $nbt->setInt(Tile::TAG_Z,$nbt->getInt(Tile::TAG_Z) + $DatumPoint->z,true);
                /*
                $nbt["x"] = (int)($nbt["x"] + $DatumPoint->x);
                $nbt["y"] = (int)($nbt["y"] + $DatumPoint->y);
                $nbt["z"] = (int)($nbt["z"] + $DatumPoint->z);*/
                if(($nbt["x"] >> 4) !== $Chunk->getX() or ($nbt["z"] >> 4) !== $Chunk->getZ()){
                    continue; //Fixes tiles allocated in wrong chunks.
                }

                if($nbt->hasTag(Chest::TAG_PAIRX))$nbt->removeTag(Chest::TAG_PAIRX);
                if($nbt->hasTag(Chest::TAG_PAIRZ))$nbt->removeTag(Chest::TAG_PAIRZ);
                if($nbt->hasTag(Chest::TAG_PAIR_LEAD))$nbt->removeTag(Chest::TAG_PAIR_LEAD);
                //todo

                if(($tile = Tile::createTile($nbt["id"], $level, $nbt)) === null){
                    continue;
                }elseif($tile instanceof Tile){
                    $Chunk->addTile($tile);
                    //$tile->setComponents($nbt["x"],$nbt["y"],$nbt["z"]);
                }
            }
		}
        self::ReaddChunkTileAndEntity($Chunk,self::$ChunkDataCache[$TaskID][$SubTaskID]['Tile'],self::$ChunkDataCache[$TaskID][$SubTaskID]['Entity']);

        self::$TaskDataList[$TaskID]['ChunkLeft']--;
        unset(self::$ChunkDataCache[$TaskID][$SubTaskID]);
        if(self::$ChunkDataCache[$TaskID] === array()){
            unset(self::$ChunkDataCache[$TaskID]);
        }
        self::DoSetChunk($level,$Chunk);
        if(self::$TaskDataList[$TaskID]['ChunkLeft'] == 0){
            unset(self::$TaskDataList[$TaskID]);
            self::CallBack($TaskID,'PasteCacheBlock',array());
        }
        //unset( self::$TaskDataList[$TaskID]);
    }
    public static function BlocksDataPartitionCallBack (string $TaskID,array $PartitionData){
	    //string $TaskID,Chunk $Chunk,array $BlocksData
        if(!isset(self::$TaskDataList[$TaskID]['LevelID'])) return false;
        $level = Server::getInstance()->getLevel(self::$TaskDataList[$TaskID]['LevelID']);
        if(!isset($PartitionData['ChunkPos']))return false;
        //var_dump($PartitionData['ChunkPos']);
        foreach($PartitionData['ChunkPos'] as $ChunkPos) {
            //$ChunkPos->['x']，['z']
            $_ChunkX = $ChunkPos['x'];
            $_ChunkZ = $ChunkPos['z'];

            $_Chunk = $level->getChunk($_ChunkX, $_ChunkZ,true);
            $SubTaskID = self::StaticAssignNewSubTaskID($TaskID);
            self::$ChunkDataCache[$TaskID][$SubTaskID]['Tile'] = $_Chunk->getTiles();
            self::$ChunkDataCache[$TaskID][$SubTaskID]['Entity'] = $_Chunk->getEntities();
            $_BlocksData = $PartitionData['ChunkPoint'][$_ChunkX][$_ChunkZ];

            self::$TaskDataList[$TaskID]['ChunkLeft']++;
            Server::getInstance()->getAsyncPool()->submitTask(new ChunkApplyBlocksDataATask($TaskID,$_Chunk,$_BlocksData,$SubTaskID));
        }
        return true;
    }
	public static function LoadCacheBlockFileCallBack (string $TaskID,array $BlocksData){
        if(!isset(self::$TaskDataList[$TaskID])) return false;
        Server::getInstance()->getAsyncPool()->submitTask(new BlocksDataPartitionATask($TaskID,$BlocksData,self::$TaskDataList[$TaskID]['DatumPoint']));
	    return true;
	}
	public function RunTask($Async = false){
        self::$TaskDataList[$this->TaskID]['LevelID'] = $this->LevelID;
        self::$TaskDataList[$this->TaskID]['DatumPoint'] = API::GetIntPoint($this->DatumPoint);
        self::$TaskDataList[$this->TaskID]['ChunkLeft'] = 0;

        Server::getInstance()->getAsyncPool()->submitTask(new LoadCacheBlockFileATask($this->TaskID,$this->FilePath));
    }
}
