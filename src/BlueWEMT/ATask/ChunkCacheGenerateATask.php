<?php
declare(strict_types = 1);
/* 
                   _ooOoo_ 
                  o8888888o 
                  88" . "88 
                  (| -_- |) 
                  O\  =  /O 
               ____/`---'\____ 
             .'  \\|     |//  `. 
            /  \\|||  :  |||//  \ 
           /  _||||| -:- |||||-  \ 
           |   | \\\  -  /// |   | 
           | \_|  ''\---/''  |   | 
           \  .-\__  `-`  ___/-. / 
         ___`. .'  /--.--\  `. . __ 
      ."" '<  `.___\_<|>_/___.'  >'"". 
     | | :  `- \`.;`\ _ /`;.`/ - ` : | | 
     \  \ `-.   \_ __\ /__ _/   .-` /  / 
======`-.____`-.___\_____/___.-`____.-'====== 
                   `=---=' 
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ 
         佛祖保佑       永无BUG 
*/  
namespace BlueWEMT\ATask;
use pocketmine\entity\Human;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\tile\Tile;
use pocketmine\nbt\NBTStream;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\scheduler\AsyncTask;
use BlueWEMT\scheduler\CacheGenerateScheduler;
use pocketmine\math\Vector3;
use pocketmine\level\format\Chunk;
use BlueWEMT\API;
class ChunkCacheGenerateATask extends AsyncTask{
	/** @var string */
	private $ReturnData;
	/** @var string */
	private $Chunk;
	/** @var string */
	private $error;
	/** @var string */
	private $TaskID;
	/** @var string */
	private $StartPoint;
	/** @var string */
	private $EndPoint;
	/** @var string */
	private $DatumPoint;
    /** @var string */
	private $EntityNBT;
    /** @var string */
    private $TileNBT;
	public function __construct(string $TaskID,Chunk $Chunk,Vector3 $StartPoint,Vector3 $EndPoint,Vector3 $DatumPoint,array $ChunkEntity = array(),array $ChunkTile = array()){
		$this->Chunk = $Chunk->fastSerialize();
		$this->TaskID = $TaskID;
        $_return = API::SortStartAndEndPoint($StartPoint,$EndPoint);
        $StartPoint = $_return['S'];
        $EndPoint = $_return['E'];
		$this->StartPoint =  serialize($StartPoint);
		$this->EndPoint = serialize($EndPoint);
		$DatumPoint = API::GetIntPoint($DatumPoint);//预整理
		$this->DatumPoint = serialize($DatumPoint);
		//$this->EntityNBT = array();
        //$this->TileNBT = array();

        $EntityNBT = array();
        $TileNBT = array();

        /*
        foreach($ChunkEntity as $_EntityIndex => $entity){
			$_NBT = new CompoundTag();
            if(API::IsInArea(
            	new Vector3($StartPoint->x + ($Chunk->getX() << 4),$StartPoint->y,$StartPoint->z + ($Chunk->getZ() << 4)),
				new Vector3($EndPoint->x + ($Chunk->getX() << 4),$EndPoint->y,$EndPoint->z + ($Chunk->getZ() << 4)),
				$entity,true) and !($entity instanceof Human) and !$entity->closed){
                $entity->saveNBT($_NBT);
                $_EntityX = (int)($entity->getX());
                $_EntityY = (int)($entity->getY());
                $_EntityZ = (int)($entity->getZ());
                $nbt = $entity->namedtag;
                $nbt["Pos"][0] = (double)($nbt["Pos"][0] - $DatumPoint->x);
				$nbt["Pos"][1] = (double)($nbt["Pos"][1] - $DatumPoint->y);
				$nbt["Pos"][2] = (double)($nbt["Pos"][2] - $DatumPoint->z);
                $_NBT->setData($nbt);
                $EntityNBT [$_EntityX][$_EntityY][$_EntityZ] = $_NBT->write();
            }
        }
        */
        foreach($ChunkTile as $_TileIndex => $tile){
			
            if(API::IsInArea(new Vector3($StartPoint->x + ($Chunk->getX() << 4),$StartPoint->y,$StartPoint->z + ($Chunk->getZ() << 4)),
				new Vector3($EndPoint->x + ($Chunk->getX() << 4),$EndPoint->y,$EndPoint->z + ($Chunk->getZ() << 4))
				,$tile,true)){
				
				$NBTtag = new CompoundTag();
                $tile->saveNBT($NBTtag);//保存到CompoundTag

                $_TileX = (int)($tile->getX());
                $_TileY = (int)($tile->getY());
                $_TileZ = (int)($tile->getZ());
                echo('T:('.$_TileX .','.$_TileY.','.$_TileZ.')'."\n");
                //$nbt = $tile->namedtag;
                $NBTtag[Tile::TAG_X] = (int)($NBTtag[Tile::TAG_X] - $DatumPoint->x);
				$NBTtag[Tile::TAG_Y] = (int)($NBTtag[Tile::TAG_Y] - $DatumPoint->y);
				$NBTtag[Tile::TAG_Z] = (int)($NBTtag[Tile::TAG_Z] - $DatumPoint->z);
				//坐标减基准点
                $TileNBT [$_TileX][$_TileY][$_TileZ] = NBTStream::toArray($NBTtag);;
			}

        }
		//TODO
        $this->EntityNBT = serialize($EntityNBT);
        $this->TileNBT = serialize($TileNBT);
		//构造: array(D=>Data(优先级最低),ID=>方块ID,SkyL=>天空亮度,=>BlockL=>方块亮度,EXD=>EXDATA,T=>Tile,E=>Entity)
	}

	public function onRun(){
		$this->error = "";
		$Chunk = Chunk::fastDeserialize($this->Chunk);
        $EntityNBT = unserialize($this->EntityNBT);
        $TileNBT = unserialize($this->TileNBT);
		$ChunkX = $Chunk->getX();
        $ChunkZ = $Chunk->getZ();
        //echo('('.$ChunkX.','.$ChunkZ.')');
		$SubChunks = $Chunk->getSubChunks();
		$StartPoint = API::GetIntPoint(unserialize($this->StartPoint));
		$EndPoint = API::GetIntPoint(unserialize($this->EndPoint));
		$DatumPoint = unserialize($this->DatumPoint);
		$SavedBlocks = array();
		/*开始对point进行整理，从立方体的六个点整理出y最小，x最小，z最小的点作为起始
		$_StartPoint = new Vector3(
			min($StartPoint->x,$EndPoint->x),
			min($StartPoint->y,$EndPoint->y),
			min($StartPoint->z,$EndPoint->z));
		$EndPoint = new Vector3(
			max($StartPoint->x,$EndPoint->x),
			max($StartPoint->y,$EndPoint->y),
			max($StartPoint->z,$EndPoint->z));
		$StartPoint = $_StartPoint;*/
		unset($_StartPoint);
		if($StartPoint->y < 0)$StartPoint->y = 0;
		if($EndPoint->y < 0)$EndPoint->y = 0;
		$StartPoint->y = $StartPoint->y & Level::Y_MASK;
		$EndPoint->y = $EndPoint->y & Level::Y_MASK;
		//整理完毕喵w
		for($y=$StartPoint->y;$y<=$EndPoint->y;$y++){
			$_SubChunk = $SubChunks[$y >> 4];
			for($x=$StartPoint->x;$x<=$EndPoint->x;$x++){
				for($z=$StartPoint->z;$z<=$EndPoint->z;$z++){
					if(method_exists($_SubChunk,"isEmpty") && !$_SubChunk->isEmpty()){
						$_BlockID = $_SubChunk->getBlockId($x, $y & 0x0f, $z);
                        $_BlockData = $_SubChunk->getBlockData($x, $y & 0x0f, $z);
						$_BlockLight = $_SubChunk->getBlockLight($x, $y & 0x0f, $z);
                        $_ExtraData = 0;
                        //$_ExtraData = $Chunk->getBlockExtraData($x, $y, $z);
					}else {
                        $_BlockID = 0;
                        $_BlockData = 0;
                        $_BlockLight = 0;
                        $_ExtraData = 0;
                    }

                    //echo('加1喵>w<');
					$_SavedBlock = (chr($_BlockID).chr($_BlockData).chr($_BlockLight));
                    $_SaveX = ($ChunkX << 4) + $x;
                    $_SaveY = $y;
                    $_SaveZ = ($ChunkZ << 4) + $z;
                    //echo('('.$_SaveX.','.$_SaveY.','.$_SaveZ.')');
                    //if($_SaveX >> 4 != $ChunkX or $_SaveZ >> 4 != $ChunkZ)echo('eeeeeRRRRR');
                    $_TSaveX = (int)($_SaveX - $DatumPoint->x);
                    $_TSaveY = (int)($_SaveY - $DatumPoint->y);
                    $_TSaveZ = (int)($_SaveZ - $DatumPoint->z);
                    //echo('('.$_SaveX.','.$_SaveY.','.$_SaveZ.')');
                    //var_dump($DatumPoint);
                    if($_ExtraData !== 0){
                        $SavedBlocks[$_TSaveX][$_TSaveY][$_TSaveZ]['EXD'] = chr($_ExtraData);
                    }
                    $SavedBlocks[$_TSaveX][$_TSaveY][$_TSaveZ]['D'] = $_SavedBlock;
                    if(isset($EntityNBT[$_SaveX][$_SaveY][$_SaveZ]))
                        $SavedBlocks[$_TSaveX][$_TSaveY][$_TSaveZ]['E'] = $EntityNBT[$_SaveX][$_SaveY][$_SaveZ];
                    if(isset($TileNBT[$_SaveX][$_SaveY][$_SaveZ]))
                        $SavedBlocks[$_TSaveX][$_TSaveY][$_TSaveZ]['T'] = $TileNBT[$_SaveX][$_SaveY][$_SaveZ];
				}
			}
		}

		$this->ReturnData = serialize($SavedBlocks);
		unset($SavedBlocks);
	}

	public function onCompletion(Server $server){
		if($this->error !== ""){
			$server->getLogger()->debug("[BlueWE] Async task failed due to \"$this->error\"");
		}else{
			$Chunk = Chunk::fastDeserialize($this->Chunk);
			CacheGenerateScheduler::ChunkReadCallback($this->TaskID,$Chunk->getX(),$Chunk->getZ(),unserialize($this->ReturnData));
		}
	}
}
?>

