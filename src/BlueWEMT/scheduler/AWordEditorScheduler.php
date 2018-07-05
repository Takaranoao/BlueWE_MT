<?php
declare(strict_types = 1);
namespace BlueWEMT\scheduler;
use pocketmine\math\Vector3;
use pocketmine\Server;
class AWordEditorScheduler{
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
	protected $ChunkPosList;
	public function __construct(int $LevelID,Vector3 $StartPoint,Vector3 $EndPoint,string $WorkMode = "C",int $WorkID = 0,int $WorkData = 0,int $WorkID2 = 0,int $WorkData2 = 0){
		$this->LevelID = $LevelID;
		$this->WorkMode = $WorkMode;
		$this->WorkID = $WorkID;
		$this->WorkData = $WorkData;
		$this->WorkID2 = $WorkID2;
		$this->WorkData2 = $WorkData2;
		$this->ChunkSplit($StartPoint,$EndPoint);
		
	}
	protected function ChunkSplit(Vector3 $StartPoint,Vector3 $EndPoint){
		$this->ChunkPosList = array();
		$StartPoint = new Vector3(min($StartPoint->x,$EndPoint->x),min($StartPoint->y,$EndPoint->y),min($StartPoint->z,$EndPoint->z));
		$EndPoint = new Vector3(max($StartPoint->x,$EndPoint->x),max($StartPoint->y,$EndPoint->y),max($StartPoint->z,$EndPoint->z));
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
	public function RunTask(){
		//var_dump($this->ChunkPosList);
		foreach($this->ChunkPosList as $ChunkPos){
			$level = Server::getInstance()->getLevel($this->LevelID);
			$Chunk = $level->getChunk($ChunkPos[0],$ChunkPos[1],true);
			Server::getInstance()->getScheduler()->scheduleAsyncTask(new \BlueWEMT\ATask\ChunkWorkerATask($level,$Chunk,array($ChunkPos[2],$ChunkPos[3],$this->WorkMode,$this->WorkID,$this->WorkData,$this->WorkID2,$this->WorkData2)));
		}
		return true;
	}
}
?>