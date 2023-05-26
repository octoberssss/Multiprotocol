<?php

namespace MultiVersion\network\proto;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\utils\BinaryStream;

abstract class MVPacketSerializer extends PacketSerializer{

	protected int $shieldRuntimeId;

	protected function __construct(protected IMVPacketSerializerContext $pkSerializeContext, string $buffer = "", int $offset = 0){
		BinaryStream::__construct($buffer, $offset);
		$this->shieldRuntimeId = $pkSerializeContext->getItemDictionary()->fromStringId("minecraft:shield");
	}

	final public static function newEncoder(IMVPacketSerializerContext $context) : self{
		return new static($context);
	}

	final public static function newDecoder(string $buffer, int $offset, IMVPacketSerializerContext $context) : self{
		return new static($context, $buffer, $offset);
	}

	final protected function getContext() : IMVPacketSerializerContext{
		return $this->pkSerializeContext;
	}
}