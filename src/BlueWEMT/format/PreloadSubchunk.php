<?php
declare(strict_types = 1);
namespace BlueWEMT\format;
class PreloadSubchunk extends \pocketmine\level\format\SubChunk{
	public function __construct(int $id = 0, int $dat = 0,string $ids = "", string $data = "", string $skyLight = "", string $blockLight = ""){
		if($id !== 0 && $ids === ""){
			$ids = str_repeat(chr($id % 0xff), 4096);
		}
		if($dat !== 0 && $data === ""){
			$data = str_repeat(chr($dat % 0xf), 2048);
		}
		parent::__construct($ids,$data,$skyLight,$blockLight);
	}
}
?>