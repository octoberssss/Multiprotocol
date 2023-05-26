<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v361RequestChunkRadiusPacket extends RequestChunkRadiusPacket {

    public static function fromLatest(RequestChunkRadiusPacket $pk) : self{
        $npk = new self();
        $npk->radius = $pk->radius;
        return $npk;
    }

    protected function decodePayload(PacketSerializer $in) : void{
        $this->radius = $in->getVarInt();
    }

    protected function encodePayload(PacketSerializer $out) : void{
        $out->putVarInt($this->radius);
    }

}