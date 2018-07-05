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

use BlueWEMT\API;
use BlueWEMT\scheduler\AWordEditorScheduler;
use pocketmine\level\format\Chunk;
//use pocketmine\level\SimpleChunkManager;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
//use pocketmine\utils\Utils;
//use pocketmine\math\Vector2;

use pocketmine\math\Vector3;
use pocketmine\level\Level;
use pocketmine\level\format\EmptySubChunk;
use pocketmine\level\format\SubChunk;
class chunkWorkerATask extends AsyncTask{
	/** @var string */
	private $Chunk;
	/** @var string */
	private $WorkerCommand;
	/** @var string */
	private $error;
	/** @var int */
	private $LevelID;
    /** @var string */
    private $TaskID;
    /** @var string */
    private $SubtaskID;
	public function __construct(Level $level,Chunk $Chunk,array $WorkerCommand,string $TaskID,string $SubtaskID){
		$this->LevelID = $level->getId();
		$this->Chunk = $Chunk->fastSerialize();
		$this->WorkerCommand = serialize($WorkerCommand);
        $this->TaskID = $TaskID;
        $this->SubtaskID = $SubtaskID;
	}

	public function onRun(){
		$this->error = "";
		$commands = unserialize($this->WorkerCommand);
		$Chunk = Chunk::fastDeserialize($this->Chunk);
		$SubChunks = $Chunk->getSubChunks();
		$ChangedSubChunks = array();
		$SameSubChunks = array ();
		//[0]起点 [1]终点 [2] 操作类型 [附加1(放置/置换的方块id)][附加2(放置/置换的方块data)]
		//开始对point进行整理，从立方体的六个点整理出y最小，x最小，z最小的点作为起始
		if($commands[0]->y < 0)$commands[0]->y = 0;
		if($commands[1]->y < 0)$commands[1]->y = 0;
		$commands[0]->y = $commands[0]->y & Level::Y_MASK;
		$commands[1]->y = $commands[1]->y & Level::Y_MASK;
		
		$StartPoint = new Vector3(
			min($commands[1]->x,$commands[0]->x),
			min($commands[1]->y,$commands[0]->y),
			min($commands[1]->z,$commands[0]->z));
		$EndPoint = new Vector3(
			max($commands[1]->x,$commands[0]->x),
			max($commands[1]->y,$commands[0]->y),
			max($commands[1]->z,$commands[0]->z));
		//echo("Y:".$StartPoint->y."|".$EndPoint->y."|".$EndPoint->y - $StartPoint->y."\n");
		//echo("高度差:".($EndPoint->y - $StartPoint->y)."\n");
		$cmd = strtoupper($commands[2]);
		switch ($cmd){
			case 'C'://清除

			case 'S'://置换:将非空气方块替换
			case 'P'://放置
			case 'R'://替换
			case 'F'://填充:将空气方块替换
			if($cmd == 'C')$commands[3] = 0;
			if(!isset($commands[3]))$commands[3] = 0;//初始id
			if(!isset($commands[4]))$commands[4] = 0;//初始data
			if($cmd == 'R'){
				if(!isset($commands[5]))$commands[5] = 0;//初始id
				if(!isset($commands[6]))$commands[6] = 0;//初始data
			}
			if($cmd == 'C'){//生成预加载子区块 目前来看仅对放置和清除有效
				$PreloadSubchunk = new SubChunk();
			}else{
				$PreloadSubchunk = new Subchunk(str_repeat(chr($commands[3] % 0xff), 4096),str_repeat(chr($commands[4] % 0xf), 2048));
			}
			//var_dump($PreloadSubchunk);
			//unset($PreloadSubchunk);
			//$SubChunks = $Chunk->getSubChunks();
			foreach ($SubChunks as $key => $SubChunk) {
				if($key <= ($EndPoint->y >> 4) && $key >= ($StartPoint->y >> 4)){//筛选
					if($cmd == 'P'){
						if($SubChunk instanceof EmptySubChunk){
							$ChangedSubChunks[$key] = new SubChunk();
						}else{
							$ChangedSubChunks[$key] = $SubChunk;
						}
						//$ChangedSubChunks[$key] = new PreloadSubchunk();
						
						
					}else{
						if(method_exists($SubChunk,"isEmpty") && !$SubChunk->isEmpty())$ChangedSubChunks[$key] = $SubChunk;//预先删除空SubChunks以加快速度
					}
				}
			}
			if($cmd == 'C' or $cmd == 'S' or $cmd == 'F' or $cmd == 'R'){
				//SFR的处理不知道耶
				//var_dump($SubChunks);
				
				if($cmd == 'C' && $StartPoint->x == 0 && $StartPoint->z == 0 && $EndPoint->z == 0x0f && $EndPoint->x == 0x0f){
					//范围横向全覆盖区块
					if($StartPoint->y == 0 && $EndPoint->y == 0x0f){
						//全区块都是空的
						foreach($ChangedSubChunks as $y => $ChangedSubChunk){
							$ChangedSubChunks[$y] = $PreloadSubchunk;
						}
						//清除整个区块
						break;
					}else{
						//不一定全区块都是空的
						foreach($ChangedSubChunks as $y => $ChangedSubChunk){
							$starty = $y << 4;
							$endy = (($y+1)<<4)-1;
							if($StartPoint->y <= $starty && $EndPoint->y >= $endy)
								$ChangedSubChunks[$y] = $PreloadSubchunk;
						}
						
					}
				}
			}elseif(isset($PreloadSubchunk) && isset($commands[4])){//只有data和id都设置的才能快速复制
				
				if($StartPoint->x == 0 && $StartPoint->z == 0 && $EndPoint->z == 0x0f && $EndPoint->x == 0x0f){
					$SameSubChunks[] = $PreloadSubchunk;
					if($StartPoint->y == 0 && $EndPoint->y == 0x0f){
						foreach($ChangedSubChunks as $y => $ChangedSubChunk){
							$ChangedSubChunks[$y] = $PreloadSubchunk;
						}
					}else{
						foreach($ChangedSubChunks as $y => $ChangedSubChunk){
							$starty = $y << 4;
							$endy = (($y+1)<<4)-1;
							if($StartPoint->y <= $starty && $EndPoint->y >= $endy)
								$ChangedSubChunks[$y] = $PreloadSubchunk;
						}
					}
					//范围横向全覆盖区块
				}else{
					//更加快速 预先排除相同SubChunks，并在结束时予以复制(非覆盖全区块)(TODO)
				}
			}
			$num = 0;
			for($y=$StartPoint->y;$y<=$EndPoint->y;$y++){
				//开始循环辣>w<
				if(isset($ChangedSubChunks[$y >> 4])){
					$tmpbool = true;
					if($cmd == 'P'){
						foreach($SameSubChunks as $SameSubChunk){
							if(!$tmpbool)break;
							$tmpbool = $ChangedSubChunks[$y >> 4] != $SameSubChunk;
						}
					}else{
						if(isset($PreloadSubchunk))
							$tmpbool = $ChangedSubChunks[$y >> 4] != $PreloadSubchunk;
					}
					if($tmpbool){
						for($x=$StartPoint->x;$x<=$EndPoint->x;$x++){
							for($z=$StartPoint->z;$z<=$EndPoint->z;$z++){//秘技:无限嵌套
								$tmpbool = false;
								if($cmd == 'C' or $cmd == 'P'){
									$tmpbool = true;
								}elseif($cmd == 'S'){
									if($ChangedSubChunks[$y >> 4]->getBlockId($x, $y & 0x0f, $z) != 0){
										$tmpbool = true;
									}
								}elseif($cmd == 'R'){
									if($ChangedSubChunks[$y >> 4]->getBlockId($x, $y & 0x0f, $z) == $commands[5]){
										if($ChangedSubChunks[$y >> 4]->getBlockData($x, $y & 0x0f, $z) == $commands[6])
											$tmpbool = true;
									}
								}elseif($cmd == 'F'){
									if($ChangedSubChunks[$y >> 4]->getBlockId($x, $y & 0x0f, $z) == 0){
										$tmpbool = true;
									}
								}
								if($tmpbool){

									$ChangedSubChunks[$y >> 4]->setBlockId($x, $y & 0x0f, $z, $commands[3]);
									if(isset($commands[4])){
										$ChangedSubChunks[$y >> 4]->setBlockData($x, $y & 0x0f, $z, $commands[4]);
									}
                                    $Chunk->setBlockExtraData($x, $y & 0x0f, $z, 0);
									$num++;
								}
							}
						}
					}
				}
			}
			//echo($num);
			foreach ($ChangedSubChunks as $key => $ChangedSubChunk) {
				$Chunk->setSubChunk($key,$ChangedSubChunk);
				//$updateblockPoss[] = new Vector3(($this->ChunkX << 4)+1,($key*0x0f)+1,($this->ChunkZ << 4)+1);
			}
			$Chunk->recalculateHeightMap();
			$Chunk->populateSkyLight();
			$Chunk->setLightPopulated();
			$this->Chunk = $Chunk->fastSerialize();
			break;
		}
	}

	public function onCompletion(Server $server){
		if($this->error !== ""){
			$server->getLogger()->debug("[BlueWE] Async task failed due to \"$this->error\"");
		}else{
			$Chunk = Chunk::fastDeserialize($this->Chunk);//TODO 弄个 callback 到 Scheduler
            AWordEditorScheduler::RunTaskCallback($Chunk,$server->getLevel($this->LevelID),$this->TaskID,$this->SubtaskID);
		}
	}
}
?>

