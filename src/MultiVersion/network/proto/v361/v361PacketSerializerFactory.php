<?php

namespace MultiVersion\network\proto\v361;

use MultiVersion\network\proto\chunk\serializer\MultiLayeredChunkSerializer;
use MultiVersion\network\proto\IMVPacketSerializerContext;
use MultiVersion\network\proto\MVPacketSerializer;
use MultiVersion\network\proto\MVPacketSerializerContext;
use MultiVersion\network\proto\PacketSerializerFactory;
use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;

class v361PacketSerializerFactory implements PacketSerializerFactory{

	private MultiLayeredChunkSerializer $chunkSerializer;

	public function __construct(
		private ItemTypeDictionary $itemDictionary,
		private IRuntimeBlockMapping $blockMapping
	){
		$this->chunkSerializer = new MultiLayeredChunkSerializer();
	}

	public function newEncoder(IMVPacketSerializerContext $context) : MVPacketSerializer{
		return v361PacketSerializer::newEncoder($context);
	}

	public function newDecoder(string $buffer, int $offset, IMVPacketSerializerContext $context) : MVPacketSerializer{
		return v361PacketSerializer::newDecoder($buffer, $offset, $context);
	}

	public function getClass() : string{
		return v361PacketSerializer::class;
	}

	public function newSerializerContext() : IMVPacketSerializerContext{
		return new MVPacketSerializerContext($this->itemDictionary, $this->blockMapping);
	}

	public function getBlockMapping() : IRuntimeBlockMapping{
		return $this->blockMapping;
	}

	public function getChunkSerializer() : MultiLayeredChunkSerializer{
		return $this->chunkSerializer;
	}
}