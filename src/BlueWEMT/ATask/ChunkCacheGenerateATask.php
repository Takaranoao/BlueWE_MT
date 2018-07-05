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
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\scheduler\AsyncTask;
use BlueWEMT\scheduler\CacheGenerateScheduler;
use pocketmine\math\Vector3;
use pocketmine\level\format\Chunk;
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
	/** @var bool */
	private $AutoOffset;
	public function __construct(string $TaskID,Chunk $Chunk,Vector3 $StartPoint,Vector3 $EndPoint,Vector3 $DatumPoint,$AutoOffset = true){
		$this->Chunk = $Chunk->fastSerialize();
		$this->TaskID = $TaskID;
		$this->StartPoint = serialize($StartPoint);
		$this->EndPoint = serialize($EndPoint);
		$this->DatumPoint = serialize($DatumPoint);
		$this->AutoOffset = $AutoOffset;
	}

	public function onRun(){
		$this->error = "";
		$Chunk = Chunk::fastDeserialize($this->Chunk);
		$SubChunks = $Chunk->getSubChunks();
		$StartPoint = unserialize($this->StartPoint);
		$EndPoint = unserialize($this->EndPoint);
		$DatumPoint = unserialize($this->DatumPoint);
		$SavedBlocks = array();
		//开始对point进行整理，从立方体的六个点整理出y最小，x最小，z最小的点作为起始
		$_StartPoint = new Vector3(
			min($StartPoint->x,$EndPoint->x),
			min($StartPoint->y,$EndPoint->y),
			min($StartPoint->z,$EndPoint->z));
		$EndPoint = new Vector3(
			max($StartPoint->x,$EndPoint->x),
			max($StartPoint->y,$EndPoint->y),
			max($StartPoint->z,$EndPoint->z));
		$StartPoint = $_StartPoint;
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
						
					}else{
						$_BlockID = 0;
						$_BlockData = 0;
						$_BlockLight = 0;
					}
					$_SavedBlock = (chr($_BlockID).chr($_BlockData).chr($_BlockLight));
					if($this->AutoOffset){
						$_SaveX = ($Chunk->getX() << 4) + $x;
						$_SaveZ = ($Chunk->getZ() << 4) + $z;
						$_SaveY = $y - $DatumPoint->y;
						$_SaveX = $_SaveX - $DatumPoint->x;
						$_SaveZ = $_SaveZ - $DatumPoint->z;
						$SavedBlocks[$_SaveX][$_SaveY][$_SaveZ] = $_SavedBlock;
					}else{
						$SavedBlocks[$x][$y][$z] = $_SavedBlock;
					}
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

