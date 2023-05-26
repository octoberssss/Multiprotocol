<?php

namespace MultiVersion\network\proto\latest;

use MultiVersion\network\proto\chunk\serializer\ExtendedYChunkSerializer;
use MultiVersion\network\proto\chunk\serializer\MVChunkSerializer;
use MultiVersion\network\proto\IMVPacketSerializerContext;
use MultiVersion\network\proto\MVPacketSerializer;
use MultiVersion\network\proto\MVPacketSerializerContext;
use MultiVersion\network\proto\PacketSerializerFactory;
use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;

class LatestPacketSerializerFactory implements PacketSerializerFactory{

	private ExtendedYChunkSerializer $chunkSerializer;

	public function __construct(
		private IRuntimeBlockMapping $blockMapping
	){
		$this->chunkSerializer = new ExtendedYChunkSerializer();
	}

	public function newEncoder(IMVPacketSerializerContext $context) : MVPacketSerializer{
		return LatestPacketSerializer::newEncoder($context);
	}

	public function newDecoder(string $buffer, int $offset, IMVPacketSerializerContext $context) : MVPacketSerializer{
		return LatestPacketSerializer::newDecoder($buffer, $offset, $context);
	}

	public function getClass() : string{
		return LatestPacketSerializer::class;
	}

	public function newSerializerContext() : IMVPacketSerializerContext{
		return new MVPacketSerializerContext(GlobalItemTypeDictionary::getInstance()->getDictionary(), $this->blockMapping);
	}

	public function getBlockMapping() : IRuntimeBlockMapping{
		return $this->blockMapping;
	}

	public function getChunkSerializer() : MVChunkSerializer{
		return $this->chunkSerializer;
	}
}