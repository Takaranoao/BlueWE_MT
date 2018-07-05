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
use BlueWEMT\scheduler\PasteCacheBlockScheduler;
use pocketmine\scheduler\AsyncTask;
use pocketmine\math\Vector3;
use pocketmine\level\Level;
//use BlueWEMT\API;
class BlocksDataPartitionATask extends AsyncTask{
	/** @var string */
	private $error;
	/** @var string */
	private $TaskID;
	/** @var string */
	private $BlocksData;
	/** @var string */
	private $DatumPoint;
	/** @var string */
	private $ReturnData;
	public function __construct(string $TaskID,array $BlocksData,Vector3 $DatumPoint){
		
		$this->DatumPoint = serialize($DatumPoint);
		$this->TaskID = $TaskID;
		$this->BlocksData = serialize($BlocksData);
	}

	public function onRun(){
		$this->error = "";
		$BlocksData = unserialize($this->BlocksData);
		$DatumPoint = unserialize($this->DatumPoint);
		$_ReturnArray = array();
		$_ReturnArray['ChunkPos'] = array();
		foreach($BlocksData as $x => $_dataYZ){
			foreach($_dataYZ as $y => $_dataZ){
				foreach($_dataZ as $z => $_data){
					if(strlen($_data) == 3){//应该是三字节表示一个方块的，ID DATA BLOCKLIGHT
						$_x = $x+$DatumPoint->x;
						$_y = $y+$DatumPoint->y;
						$_z = $z+$DatumPoint->z;
						if(!isset($_ReturnArray['ChunkPoint'][$_x >> 4][$_z >> 4])){
							$_ChunkPos['x'] = $_x >> 4;
							$_ChunkPos['z'] = $_z >> 4;
							$_ReturnArray['ChunkPos'][] = $_ChunkPos;
						}
						$_ReturnArray['ChunkPoint'][$_x >> 4][$_z >> 4][$_x & 0x0f][$_y & Level::Y_MASK][$_z & 0x0f] = $_data;
						echo('喜+1');
					}else{
					    echo('坑爹的数据');
                    }
				}
			}
		}
		$this->ReturnData = serialize($_ReturnArray);
		unset($_ReturnArray,$BlocksData);
	}

	public function onCompletion(Server $server){
		if($this->error !== ""){
			$server->getLogger()->debug("[BlueWE] Async task failed due to \"$this->error\"");
		}else{
            PasteCacheBlockScheduler::BlocksDataPartitionCallBack ($this->TaskID,unserialize($this->ReturnData));
		}
	}
}
?>

