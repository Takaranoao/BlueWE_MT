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
use BlueWEMT\API;
use BlueWEMT\scheduler\PasteCacheBlockScheduler;
class LoadCacheBlockFileATask extends AsyncTask{
	/** @var string */
	private $error;
	/** @var string */
	private $TaskID;
	/** @var string */
	private $FilePath;
	/** @var string */
	private $ReturnData;
	public function __construct(string $TaskID,string $FilePath = 'mem'){
		$this->TaskID = $TaskID;
		$this->FilePath = $FilePath;
		if(strtolower($this->FilePath) == 'mem')$this->ReturnData = API::ReadBlocksData();
	}
	public function onRun(){
		$this->error = "";
		if(strtolower($this->FilePath) == 'mem'){
			//什么都没有的说
		}elseif(is_readable($this->FilePath)){
			$this->ReturnData = file_get_contents($this->FilePath);	
		}else{
			$this->ReturnData = '';
			return;
		}
	}
	public function onCompletion(Server $server){
		if($this->error !== ""){
			$server->getLogger()->debug("[BlueWE] Async task failed due to \"$this->error\"");
		}else{
            PasteCacheBlockScheduler::LoadCacheBlockFileCallBack($this->TaskID,unserialize($this->ReturnData));
		}
	}
}