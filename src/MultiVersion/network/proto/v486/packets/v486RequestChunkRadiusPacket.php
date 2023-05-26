<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v486RequestChunkRadiusPacket extends RequestChunkRadiusPacket
{

    public static function fromLatest(RequestChunkRadiusPacket $pk): self {
        $npk = new self();
        $npk->radius = $pk->radius ?? 8;
        $npk->maxRadius = $pk->maxRadius ?? 8;
        return $npk;
    }

    protected function decodePayload(PacketSerializer $in) : void{
        $length = strlen($in->getBuffer());
        if ($length < 1) {
            throw new \RuntimeException("Not enough bytes left in buffer");
        }

        $this->radius = $in->getVarInt();
        $this->maxRadius = $in->getByte();
    }

    protected function encodePayload(PacketSerializer $out) : void{
        $out->putVarInt($this->radius);
        $out->putByte($this->maxRadius);
    }

    public function handle(PacketHandlerInterface $handler): bool {
        return $handler->handleRequestChunkRadius($this);
    }
}