<?php
declare(strict_types = 1);
namespace BlueWEMT\ATask;
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
use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;
use pocketmine\level\format\Chunk;
use BlueWEMT\scheduler\PasteCacheBlockScheduler;
class ChunkApplyBlocksDataATask extends AsyncTask{
	/** @var string */
	private $error;
	/** @var string */
	private $TaskID;
	/** @var string */
	private $BlocksData;
	/** @var string */
	private $Chunk;
	public function __construct(string $TaskID,Chunk $Chunk,array $BlocksData){
		$this->TaskID = $TaskID;
		$this->Chunk = $Chunk->fastSerialize();
		$this->BlocksData = serialize($BlocksData);
	}

	public function onRun(){
		$this->error = "";
		$Chunk = Chunk::fastDeserialize($this->Chunk);
		//$SubChunks = $Chunk->getSubChunks();
		$BlocksData = unserialize($this->BlocksData);
		foreach($BlocksData as $x => $_dataYZ){
			foreach($_dataYZ as $y => $_dataZ){
				foreach($_dataZ as $z => $_data){
					if(strlen($_data) == 3){
						$Chunk->setBlock($x, $y, $z, ord($_data{0}), ord($_data{1}));
						$Chunk->setBlockLight($x, $y, $z, ord($_data{2}));
					}
				}
			}
		}
		
		
		$Chunk->recalculateHeightMap();
		$Chunk->populateSkyLight();
		$Chunk->setLightPopulated();
		$this->Chunk = $Chunk->fastSerialize();
		unset($BlocksData);
	}
	
	public function onCompletion(Server $server){
		if($this->error !== ""){
			$server->getLogger()->debug("[BlueWE] Async task failed due to \"$this->error\"");
		}else{
            PasteCacheBlockScheduler::ChunkApplyBlocksDataCallBack($this->TaskID,Chunk::fastDeserialize($this->Chunk));
		}
	}
}