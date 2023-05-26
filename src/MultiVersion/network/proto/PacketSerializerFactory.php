<?php

namespace MultiVersion\network\proto;

use MultiVersion\network\proto\chunk\serializer\MVChunkSerializer;
use MultiVersion\network\proto\static_resources\IRuntimeBlockMapping;

interface PacketSerializerFactory{

	public function newEncoder(IMVPacketSerializerContext $context) : MVPacketSerializer;

	public function newDecoder(string $buffer, int $offset, IMVPacketSerializerContext $context) : MVPacketSerializer;

	public function getClass() : string;

	public function newSerializerContext() : IMVPacketSerializerContext;

	public function getBlockMapping() : IRuntimeBlockMapping;

	public function getChunkSerializer() : MVChunkSerializer;
}