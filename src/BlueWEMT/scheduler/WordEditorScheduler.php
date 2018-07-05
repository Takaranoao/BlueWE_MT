<?php
namespace BlueWEMT\scheduler;
use pocketmine\entity\Human;
use pocketmine\math\Vector3;
//use pocketmine\Server;
use pocketmine\tile\Tile;
use BlueWEMT\API;
use pocketmine\level\level;
use pocketmine\entity\Entity;
use pocketmine\level\format\Chunk;
abstract class WordEditorScheduler{
    /** @var array */
    protected $ChunkPosList;
    /** @var string */
    protected $TaskID;
    /** @var int */
    private static $SubTaskNumber = 0;
    abstract public function RunTask($Async = false);
    protected function AssignNewSubTaskID(){
        if(!isset($this->TaskID))$this->TaskID = time().mt_rand(1,10000);
        return self::StaticAssignNewSubTaskID($this->TaskID);
    }
    protected static function StaticAssignNewSubTaskID($TaskID){
        if(!isset($TaskID))$TaskID = time().mt_rand(1,10000);
        if(!isset(self::$SubTaskNumber))self::$SubTaskNumber = 0;
        self::$SubTaskNumber++;
        return $TaskID.self::$SubTaskNumber;
    }
    protected function EmptyRangeTileInChunk(array $tiles,Vector3 $StartPoint,Vector3 $EndPoint){
        $StartPoint->x = $StartPoint->x & 0x0f;
        $StartPoint->y = $StartPoint->y & Level::Y_MASK;
        $StartPoint->z = $StartPoint->z & 0x0f;
        $EndPoint->x = $EndPoint->x & 0x0f;
        $EndPoint->y = $EndPoint->y & Level::Y_MASK;
        $EndPoint->z = $EndPoint->z & 0x0f;
        $_Point = API::SortStartAndEndPoint($StartPoint,$EndPoint);
        $StartPoint = $_Point['S'];
        $EndPoint = $_Point['E'];
        $_return = array();
        foreach($tiles as $index => $tile){
            $_bool = ($tile instanceof Tile);
            if ($_bool){
                $_TileX = $tile->getX() & 0x0f;
                $_TileY = $tile->getY() & Level::Y_MASK;
                $_TileZ = $tile->getZ() & 0x0f;

                if(!API::IsInArea($StartPoint,$EndPoint,new Vector3($_TileX,$_TileY, $_TileZ),true))
                    $_return[$index] = $tile;
            }
        }
        return $_return;
    }
    protected static function DoSetChunk(level $Level,Chunk $Chunk){
        API::GetAPI()->RerenderChunk($Level,$Chunk->getX(),$Chunk->getZ());
        $Level->setChunk($Chunk->getX(), $Chunk->getZ(),$Chunk);
        $Level->cancelUnloadChunkRequest($Chunk->getX(), $Chunk->getZ());
        $Level->populateChunk($Chunk->getX(), $Chunk->getZ());
        $Level->clearChunkCache($Chunk->getX(), $Chunk->getZ());
    }
    protected static function ReaddChunkTileAndEntity(Chunk $Chunk,array $TileData,array $EntityData,Level $level = null){
        if(isset ($TileData)) {
            foreach ($TileData as $_TID => $_Tile) {
                if ($_Tile instanceof Tile){
                    $_Tile_C = clone $_Tile;
                    //$_Tile->close();
                    $Chunk->addTile($_Tile_C);
                    unset($_Tile,$_Tile_C);
                }

            }
        }
        if(isset ($EntityData)){
            foreach($EntityData as $_EID => $_Entity){
                if(($_Entity instanceof Entity) and (!$_Entity instanceof Human)){
                    $_Entity_C = clone $_Entity;
                    $Chunk->addEntity($_Entity_C);
                    if(isset($level) and $level instanceof Level)
                        $level->addEntity($_Entity_C);
                    unset($_Entity,$_Entity_C);
                }

            }
        }
    }
    protected function ChunkSplit(Vector3 $StartPoint,Vector3 $EndPoint){
        $this->ChunkPosList = array();
        $StartPoint = API::GetIntPoint($StartPoint);
        $EndPoint = API::GetIntPoint($EndPoint);
        $_Point = API::SortStartAndEndPoint($StartPoint,$EndPoint);
        $StartPoint = $_Point['S'];
        $EndPoint = $_Point['E'];
        $StartChunkX = ($StartPoint->x >> 4);
        $StartChunkZ = ($StartPoint->z >> 4);
        $EndChunkX = ($EndPoint->x >> 4);
        $EndChunkZ = ($EndPoint->z >> 4);
        for($ChunkX = $StartChunkX;$ChunkX <= $EndChunkX;$ChunkX++){
            for($ChunkZ = $StartChunkZ;$ChunkZ <= $EndChunkZ;$ChunkZ++){
                //echo($ChunkX.'|'.$ChunkZ."\n");
                $t_StartPointX = 0;
                $t_StartPointZ = 0;
                $t_EndPointX = 0x0f;
                $t_EndPointZ = 0x0f;

                if($ChunkX == $StartChunkX){
                    $t_StartPointX = $StartPoint->x & 0x0f;
                }
                if($ChunkZ == $StartChunkZ){
                    $t_StartPointZ = $StartPoint->z & 0x0f;
                }
                if($ChunkX == $EndChunkX){
                    $t_EndPointX = $EndPoint->x & 0x0f;
                }
                if($ChunkZ == $EndChunkZ){
                    $t_EndPointZ = $EndPoint->z & 0x0f;
                }
                $this->ChunkPosList[] = array($ChunkX,$ChunkZ,new Vector3($t_StartPointX,$StartPoint->y,$t_StartPointZ),new Vector3($t_EndPointX,$EndPoint->y,$t_EndPointZ));
            }
        }
        return true;
    }
}