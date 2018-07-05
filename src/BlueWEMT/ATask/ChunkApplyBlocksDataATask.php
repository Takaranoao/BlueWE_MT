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
//use pocketmine\nbt\NBT;
use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;
use pocketmine\level\format\Chunk;
use BlueWEMT\scheduler\PasteCacheBlockScheduler;
//use pocketmine\entity\Entity;
class ChunkApplyBlocksDataATask extends AsyncTask{
	/** @var string */
	private $error;
	/** @var string */
	private $TaskID;
	/** @var string */
	private $BlocksData;
	/** @var string */
	private $Chunk;
    /** @var string */
    private $EntityList;
    /** @var string */
    private $TileList;
    /** @var string */
    private $SubTaskID;
	public function __construct(string $TaskID,Chunk $Chunk,array $BlocksData,string $SubTaskID){
		$this->TaskID = $TaskID;
		$this->Chunk = $Chunk->fastSerialize();
		$this->BlocksData = serialize($BlocksData);
		$this->SubTaskID = $SubTaskID;
	}

	public function onRun(){
		$this->error = "";
		$Chunk = Chunk::fastDeserialize($this->Chunk);
		//$SubChunks = $Chunk->getSubChunks();
		$BlocksData = unserialize($this->BlocksData);
		$EntityList = array();
		$TileList = array();
		foreach($BlocksData as $x => $_dataYZ){
		    if(!is_array($_dataYZ))continue;
			foreach($_dataYZ as $y => $_dataZ){
                if(!is_array($_dataZ))continue;
				foreach($_dataZ as $z => $_data){
                    if(is_array($_data)){
                        if(isset($_data['EXD'])){
                            $Chunk->setBlockExtraData($x,$y,$z,ord($_data['EXD']));
                        }else{
                            $Chunk->setBlockExtraData($x,$y,$z,0);
                        }
                        if(isset($_data['D'])){
                            $Chunk->setBlock($x, $y, $z, ord($_data['D']{0}), ord($_data['D']{1}));
                            $Chunk->setBlockLight($x, $y, $z, ord($_data['D']{2}));
                        }

                        if(isset($_data['E'])){
                            $EntityList [] = $_data['E'];
                        }
                        if(isset($_data['T'])){
                            $TileList [] = $_data['T'];
                            echo('T:('.$x .','.$y.','.$z.')'."\n");
                        }
                    }elseif(strlen($_data) == 3){
						$Chunk->setBlock($x, $y, $z, ord($_data{0}), ord($_data{1}));
						$Chunk->setBlockLight($x, $y, $z, ord($_data{2}));
					}
				}
			}
		}
		
		
		$Chunk->recalculateHeightMap();
		$Chunk->populateSkyLight();
		$Chunk->setLightPopulated();
		$this->EntityList = serialize($EntityList);
        $this->TileList = serialize($TileList);
		$this->Chunk = $Chunk->fastSerialize();
		unset($BlocksData);
	}
	
	public function onCompletion(Server $server){
		if($this->error !== ""){
			$server->getLogger()->debug("[BlueWE] Async task failed due to \"$this->error\"");
		}else{
            PasteCacheBlockScheduler::ChunkApplyBlocksDataCallBack($this->TaskID,Chunk::fastDeserialize($this->Chunk),unserialize($this->EntityList),unserialize($this->TileList),$this->SubTaskID);
		}
	}
}