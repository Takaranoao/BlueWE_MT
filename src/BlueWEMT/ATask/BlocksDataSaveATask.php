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
use BlueWEMT\scheduler\CacheGenerateScheduler;
use pocketmine\scheduler\AsyncTask;
use BlueWEMT\API;
class BlocksDataSaveATask extends AsyncTask{
	/** @var string */
	private $error;
	/** @var string */
	private $TaskID;
	/** @var int */
	private $ReturnData;
	/** @var string */
	private $SaveDataToMem;
    /** @var string */
    private $FilePath;
    /** @var string */
    private $ChunkDatas;
	public function __construct(string $TaskID,array $ChunkDatas,string $FilePath){
		$this->FilePath = $FilePath;
		$this->ChunkDatas = serialize($ChunkDatas);
		$this->TaskID = $TaskID;
	}

	public function onRun(){
		$this->error = "";
		$_DataArray = array();
		$ChunkDatas = unserialize($this->ChunkDatas);
		foreach($ChunkDatas as $ChunkX => $ChunkData){
			foreach($ChunkData as $ChunkZ => $Data){
				$_DataArray = array_merge_recursive($_DataArray,$Data);
			}
		}
		if(strtolower($this->FilePath)=='mem'){
			$this->SaveDataToMem = serialize($_DataArray);
			$_ReturnData = strlen($this->SaveDataToMem);
		}else{
			$_ReturnData = file_put_contents($this->FilePath, serialize($_DataArray));
		}
		if($_ReturnData === false){
			$this->ReturnData = -1;
		}else{
			$this->ReturnData = $_ReturnData;
		}
		unset($_DataArray,$_ReturnData,$ChunkDatas);
	}

	public function onCompletion(Server $server){
		if($this->error !== ""){
			$server->getLogger()->debug("[BlueWE] Async task failed due to \"$this->error\"");
		}else{
			if(strtolower($this->FilePath)=='mem'){
				API::WriteBlocksData($this->SaveDataToMem);//写入MEM
			}
			CacheGenerateScheduler::BlocksDataSaveCallback($this->TaskID,$this->ReturnData);
		}
	}
}
?>

