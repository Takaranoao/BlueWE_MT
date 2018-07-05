<?php
namespace BlueWEMT;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
/*
结构:
$request=[头标识][操作类型][ARR([x][y][z][（ARR?）data])]
操作类型目前有:id替换，清空，完全替换，重置区块
本插件只负责传递事件到BlueVIP核心
*/


class Main extends \pocketmine\plugin\PluginBase
{
	/** @var Listener */
	public $Listener;
	/** @var API */
	public $API;
	/** @var Main **/
    public static $instance;

    public static function getInstance(){
        return self::$instance;
    }
    //@override
	public function onLoad()
	{
		/*
		ZXDA::init(609,$this);
		ZXDA::requestCheck();
		*/
	}
	
	//请一定要按照规范的格式来写
	//@override
	public function onEnable()
	{
		/*
		ZXDA::tokenCheck('MTE3OTQwOTY3ODI2NzgzMTU0MjU0MTgzNDI0NTYyMTIxNzUxNzQ2MDU5NTQ1NzA0OTU1NzUyNTU2MjAzNjIxMzA3NDY5MDU1ODE1MjgxOTU2MTA1NzAyOTkwNTI2MDI5ODYwNDk2NjQxODUyMDQ2NDg4MzQxNjQ2MDMxNjM1NzcwOTgyMzc0ODMxNDQyODI4MDgwODI1MTkxNTA4ODkyOTYyNTI1NDI0MDM4NzU0ODU2MTk5OTMxOTMxMTYzMjA5OTc0MjkzNjIzODAxNTMzNjEyMTkzOTAwOTAxNDg0NzcyNzQ5NzgyOQ==');
		$data=ZXDA::getInfo();
		if($data['success'])
		{
			if(version_compare($data['version'],$this->getDescription()->getVersion())>0)
			{
				$this->getLogger()->info(TextFormat::GREEN.'检测到新版本,最新版:'.$data['version'].",更新日志:\n    ".str_resupersede("\n","\n    ",$data['update_info']));
			}
		}
		else
		{
			$this->getLogger()->warning('更新检查失败:'.$data['message']);
		}
		if(ZXDA::isTrialVersion())
		{
			$this->getLogger()->warning('当前正在使用试用版授权,试用时间到后将强制关闭服务器');
		}
		//继续加载插件
		*/
        self::$instance = $this;
		$this->getLogger()->info( '[BlueWEMT]'.TextFormat::GREEN .'いらっしゃいませ'.TextFormat::RED.' ^o^ '.TextFormat::GOLD.'来了您嘞');
		$this->API = new API($this);
		$this->Listener = new Listener($this);
	}
	
	/**
	 * @param CommandSender $sender
	 * @param Command       $command
	 * @param string        $label
	 * @param array         $args
	 *
	 * @return bool
	 */
	//@override
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		$cmd = strtolower($command->getName());
		$params = $args;
		if(!($sender instanceof Player)){
			$sender->sendMessage($this->API->getMessage('RunThisCommandIn-game'));
			return true;
		}
		if($cmd{0} === "/"){
			$cmd = substr($cmd, 1);
		}
		if(!$this->API->IsSelectPointEffective()){
			$this->API->SendMessage($sender,'SelectPointNotEffective');
			return true;
		}
		switch($cmd){
			case 'replace':
			if(!isset($params[0])){
				$this->API->SendMessage($sender,'ReplaceMissingParameter2');
				return false;
			}
			case 'set':
			case 'supersede':
			case 'fill':
			$items = Item::fromString($params[0], true);
			$bcount = count($items) - 1;
			if($bcount < 0){
				$this->API->SendMessage($sender,'Incorrectblocks');
				return;
			}
			if($items){
				foreach($items as $item){
					if($item->getID() > 0xff or $item->getDamage() > 0x0f){
						$this->API->SendMessage($sender,'Incorrectblock');
						return;
					}
				}
				$item = $items[mt_rand(0, $bcount)];
				if($item->getID() != 0){
					if($cmd == 'supersede'){
						$this->API->WordEditorSchedulerUseSingleThreadAndRunIt("S",$item->getID(),$item->getDamage());
					}elseif($cmd == 'fill'){
						$this->API->WordEditorSchedulerUseSingleThreadAndRunIt("F",$item->getID(),$item->getDamage());
					}elseif($cmd == 'set'){
						$this->API->WordEditorSchedulerUseSingleThreadAndRunIt("P",$item->getID(),$item->getDamage());
					}elseif($cmd == 'replace'){
						$items2 = Item::fromString($params[1], true);
						if($items2){
							$bcnt = count($items2) - 1;
							if($bcnt < 0){
								$this->API->SendMessage($sender,'Incorrectblocks-replace');
								return;
							}
							foreach($items2 as $item2){
								if($item2->getID() > 0xff or $item2->getDamage() > 0x0f){
									$this->API->SendMessage($sender,'Incorrectblock-replace');
									return;
								}
							}
							$item2 = $items2[mt_rand(0, $bcnt)];
							$this->API->WordEditorSchedulerUseSingleThreadAndRunIt("R",$item2->getID(),$item2->getDamage(),$item->getID(),$item->getDamage());//倒过来更符合习惯
						}else{
							$this->API->SendMessage($sender,'Incorrectblock-UseID-replace');
						}
					}else{
						$this->API->SendMessage($sender,'Subcommandtan90');
					}
				}else{
					if($cmd == 'fill'){
						
					}elseif($cmd == 'replace'){
						$this->API->SendMessage($sender,'PleaseUseFillCommand');
					}elseif($cmd == 'supersede' or $cmd == 'set'){
						$this->API->WordEditorSchedulerUseSingleThreadAndRunIt("C",$item->getID(),$item->getDamage());
					}
					
				}
			
			} else {
				$this->API->SendMessage($sender,'Incorrectblock-UseID');
			}
			return true;
			break;
			
			case 'copy':
			    //API::$SavedBlocksPath.'/Test.szb'
			$this->API->CacheGenerateSchedulerUseSingleThreadAndRunIt();
			return true;
			break;
            case 'paste':
                $this->API->PasteCacheBlockSchedulerUseSingleThreadAndRunIt($sender);
                //TODO
                return true;
                break;
			
		}
		return false;
	}
}

//一个插件中非常关键的Task
class ImportantTask
{
	public function onRun()
	{
		//在开头执行一次,是否判断返回值自己看着办
		ZXDA::isTrialVersion();
		/*
		继续处理Task
		...
		*/
		
	}
}

class ZXDA
{
	const API_VERSION=5012;
	
	private static $_PID=\false;
	private static $_TOKEN=\false;
	private static $_PLUGIN=\null;
	private static $_VERIFIED=\false;
	
	public static function init($pid,$plugin)
	{
		if(!\is_numeric($pid))
		{
			self::killit('参数错误,请传入正确的PID(0001)');
			exit();
		}
		self::$_PLUGIN=$plugin;
		if(self::$_PID!==\false && self::$_PID!=$pid)
		{
			self::killit('非法访问(0002)');
			exit();
		}
		self::$_PID=$pid;
	}
	
	public static function checkKernelVersion()
	{
		if(self::$_PID===\false)
		{
			self::killit('SDK尚未初始化(0003)');
			exit();
		}
		if(!\class_exists('\\ZXDAKernel\\Main'))
		{
			self::killit('请到 https://pl.zxda.net/ 下载安装最新版ZXDA Kernel后再使用此插件(0004)');
			exit();
		}
		$version=\ZXDAKernel\Main::getVersion();
		if($version<self::API_VERSION)
		{
			self::killit('当前ZXDA Kernel版本太旧,无法使用此插件,请到 https://pl.zxda.net/ 下载安装最新版后再使用此插件(0005)');
			exit();
		}
		return $version;
	}
	
	public static function isTrialVersion()
	{
		try
		{
			self::checkKernelVersion();
			return \ZXDAKernel\Main::isTrialVersion(self::$_PID);
		}
		catch(\Exception $err)
		{
			@\file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',\var_export($err,\true));
			self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
		}
	}
	
	public static function requestCheck()
	{
		try
		{
			self::checkKernelVersion();
			self::$_VERIFIED=\false;
			self::$_TOKEN=\sha1(\uniqid());
			if(!\ZXDAKernel\Main::requestAuthorization(self::$_PID,self::$_PLUGIN,self::$_TOKEN))
			{
				self::killit('请求授权失败,请检查PID是否已正确传入(0006)');
				exit();
			}
		}
		catch(\Exception $err)
		{
			@\file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',\var_export($err,\true));
			self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
		}
	}
	
	public static function tokenCheck($key)
	{
		try
		{
			self::checkKernelVersion();
			self::$_VERIFIED=\false;
			$manager=self::$_PLUGIN->getServer()->getPluginManager();
			if(!($plugin=$manager->getPlugin('ZXDAKernel')) instanceof \ZXDAKernel\Main)
			{
				self::killit('ZXDA Kernel加载失败,请检查插件是否已正常安装(0008)');
			}
			if(!$manager->isPluginEnabled($plugin))
			{
				$manager->enablePlugin($plugin);
			}
			$key=\base64_decode($key);
			if(($token=\ZXDAKernel\Main::getResultToken(self::$_PID))===\false)
			{
				self::killit('请勿进行非法破解(0009)');
			}
			if(self::rsa_decode(\base64_decode($token),$key,768)!=\sha1(\strrev(self::$_TOKEN)))
			{
				self::killit('插件Key错误,请更新插件或联系作者(0010)');
			}
			self::$_VERIFIED=\true;
		}
		catch(\Exception $err)
		{
			@\file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',\var_export($err,\true));
			self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
		}
	}
	
	public static function isVerified()
	{
		return self::$_VERIFIED;
	}
	
	public static function getInfo()
	{
		try
		{
			self::checkKernelVersion();
			$manager=self::$_PLUGIN->getServer()->getPluginManager();
			if(!($plugin=$manager->getPlugin('ZXDAKernel')) instanceof \ZXDAKernel\Main)
			{
				self::killit('ZXDA Kernel加载失败,请检查插件是否已正常安装(0008)');
			}
			if(($data=\ZXDAKernel\Main::getPluginInfo(self::$_PID))===\false)
			{
				self::killit('请勿进行非法破解(0009)');
			}
			if(\count($data=\explode(',',$data))!=2)
			{
				return array(
					'success'=>\false,
					'message'=>'未知错误');
			}
			return array(
				'success'=>\true,
				'version'=>\base64_decode($data[0]),
				'update_info'=>\base64_decode($data[1]));
		}
		catch(\Exception $err)
		{
			@\file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',\var_export($err,\true));
			self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
		}
	}
	
	public static function killit($msg)
	{
		if(self::$_PLUGIN===\null)
		{
			echo('抱歉,插件授权验证失败[SDK:'.self::API_VERSION."]\n附加信息:".$msg);
		}
		else
		{
			@self::$_PLUGIN->getLogger()->warning('§e抱歉,插件授权验证失败[SDK:'.self::API_VERSION.']');
			@self::$_PLUGIN->getLogger()->warning('§e附加信息:'.$msg);
			@self::$_PLUGIN->getServer()->forceShutdown();
		}
		@\pocketmine\kill(getmypid());
		exit();
	}
	
	//RSA加密算法实现
	public static function rsa_encode($message,$modulus,$keylength=1024,$isPriv=\true){$result=array();while(\strlen($msg=\substr($message,0,$keylength/8-5))>0){$message=\substr($message,\strlen($msg));$result[]=self::number_to_binary(self::pow_mod(self::binary_to_number(self::add_PKCS1_padding($msg,$isPriv,$keylength/8)),'65537',$modulus),$keylength/8);unset($msg);}return(\implode('***&&&***',$result));}
	public static function rsa_decode($message,$modulus,$keylength=1024){$result=array();foreach(\explode('***&&&***',$message) as $message){$result[]=self::remove_PKCS1_padding(self::number_to_binary(self::pow_mod(self::binary_to_number($message),'65537',$modulus),$keylength/8),$keylength/8);unset($message);}return(\implode('',$result));}
	private static function pow_mod($p,$q,$r){$factors=array();$div=$q;$power_of_two=0;while(\bccomp($div,'0')==1){$rem=\bcmod($div,2);$div=\bcdiv($div,2);if($rem){\array_push($factors,$power_of_two);}$power_of_two++;}$partial_results=array();$part_res=$p;$idx=0;foreach($factors as $factor){while($idx<$factor){$part_res=\bcpow($part_res,'2');$part_res=\bcmod($part_res,$r);$idx++;}\array_push($partial_results,$part_res);}$result='1';foreach($partial_results as $part_res){$result=\bcmul($result,$part_res);$result=\bcmod($result,$r);}return($result);}
	private static function add_PKCS1_padding($data,$isprivateKey,$blocksize){$pad_length=$blocksize-3-\strlen($data);if($isprivateKey){$block_type="\x02";$padding='';for($i=0;$i<$pad_length;$i++){$rnd=\mt_rand(1,255);$padding.=\chr($rnd);}}else{$block_type="\x01";$padding=\str_repeat("\xFF",$pad_length);}return("\x00".$block_type.$padding."\x00".$data);}
	private static function remove_PKCS1_padding($data,$blocksize){\assert(\strlen($data)==$blocksize);$data=\substr($data,1);if($data{0}=='\0'){return('');}\assert(($data{0}=="\x01")||($data{0}=="\x02"));$offset=\strpos($data,"\0",1);return(\substr($data,$offset+1));}
	private static function binary_to_number($data){$radix='1';$result='0';for($i=\strlen($data)-1;$i>=0;$i--){$digit=ord($data{$i});$part_res=\bcmul($digit,$radix);$result=bcadd($result,$part_res);$radix=\bcmul($radix,'256');}return($result);}
	private static function number_to_binary($number,$blocksize){$result='';$div=$number;while($div>0){$mod=\bcmod($div,'256');$div=\bcdiv($div,'256');$result=\chr($mod).$result;}return(\str_pad($result,$blocksize,"\x00",\STR_PAD_LEFT));}
}
