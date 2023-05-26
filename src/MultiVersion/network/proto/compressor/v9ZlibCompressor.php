<?php

namespace MultiVersion\network\proto\compressor;

use pocketmine\utils\AssumptionFailedError;

final class v9ZlibCompressor extends BaseZlibCompressor{

	public function __construct(
		protected int $level,
		protected int $threshold,
		protected int $maxDecompressionSize
	){
		parent::__construct($level, $threshold, $maxDecompressionSize);
	}

	public function getCompressionThreshold() : ?int{
		return $this->threshold;
	}

	protected function attempt_libdeflate(string &$payload) : bool{
		// https://github.com/pmmp/ext-libdeflate/blob/4f8a23eaeecfd780f534dcdfb9845e2fb1696500/README.md
		if(!function_exists('libdeflate_zlib_compress')) return false;
		if($this->willCompress($payload))
			$payload = libdeflate_zlib_compress($payload, $this->level);
		else
			$payload = self::zlib_encode($payload, 0);
		return true;
	}

	protected static function zlib_encode(string $data, int $level) : string{
		$result = zlib_encode($data, ZLIB_ENCODING_DEFLATE, $level);
		if($result === false) throw new AssumptionFailedError("ZLIB compression failed");
		return $result;
	}
}