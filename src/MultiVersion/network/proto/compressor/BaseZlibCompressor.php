<?php

namespace MultiVersion\network\proto\compressor;

use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\utils\SingletonTrait;

abstract class BaseZlibCompressor implements Compressor{
	use SingletonTrait;

	public const DEFAULT_LEVEL = 7;
	public const DEFAULT_THRESHOLD = 256;
	public const DEFAULT_MAX_DECOMPRESSION_SIZE = 2 * 1024 * 1024;

	public function __construct(
		protected int $level,
		protected int $threshold,
		protected int $maxDecompressionSize
	){

	}

	/**
	 * @see SingletonTrait::make()
	 */
	private static function make() : self{
		return new static(static::DEFAULT_LEVEL, static::DEFAULT_THRESHOLD, static::DEFAULT_MAX_DECOMPRESSION_SIZE);
	}

	/**
	 * @throws DecompressionException
	 */
	public function decompress(string $payload) : string{
		$result = @zlib_decode($payload, $this->maxDecompressionSize);
		if($result === false){
			throw new DecompressionException("Failed to decompress data");
		}
		return $result;
	}

	public function compress(string $payload) : string{
		if($this->attempt_libdeflate($payload)) return $payload;
		return static::zlib_encode($payload, $this->willCompress($payload) ? $this->level : 0);
	}

	/** returns true if successful */
	abstract protected function attempt_libdeflate(string &$payload) : bool;

	abstract protected static function zlib_encode(string $data, int $level) : string;

	public function willCompress(string $data) : bool{
		return $this->threshold > -1 and strlen($data) >= $this->threshold;
	}
}