<?php

namespace MultiVersion\network\proto\v361\packets;

use CortexPE\std\ReflectionUtils;
use MultiVersion\network\proto\v361\packets\types\inventory\v361MismatchTransactionData;
use MultiVersion\network\proto\v361\packets\types\inventory\v361NetworkInventoryAction;
use MultiVersion\network\proto\v361\packets\types\inventory\v361NormalTransactionData;
use MultiVersion\network\proto\v361\packets\types\inventory\v361ReleaseItemTransactionData;
use MultiVersion\network\proto\v361\packets\types\inventory\v361UseItemOnEntityTransactionData;
use MultiVersion\network\proto\v361\packets\types\inventory\v361UseItemTransactionData;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;

class v361InventoryTransactionPacket extends InventoryTransactionPacket{

    protected function decodePayload(PacketSerializer $in) : void{
        $transactionType = $in->getUnsignedVarInt();

        $this->trData = match ($transactionType) {
            NormalTransactionData::ID => new v361NormalTransactionData(),
            MismatchTransactionData::ID => new v361MismatchTransactionData(),
            UseItemTransactionData::ID => new v361UseItemTransactionData(),
            UseItemOnEntityTransactionData::ID => new v361UseItemOnEntityTransactionData(),
            ReleaseItemTransactionData::ID => new v361ReleaseItemTransactionData(),
            default => throw new PacketDecodeException("Unknown transaction type $transactionType"),
        };

        $actions = [];
        $actionCount = $in->getUnsignedVarInt();
        for($i = 0; $i < $actionCount; ++$i){
            $actions[] = (new v361NetworkInventoryAction())->read($in);
        }

        $this->requestId = $transactionType === MismatchTransactionData::ID ? $in->getUnsignedVarInt() : 0;
        $this->requestChangedSlots = [];
        if($this->requestId !== 0){
            for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
                $this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
            }
        }

        ReflectionUtils::setProperty(TransactionData::class, $this->trData, "actions", $actions);
        ReflectionUtils::invoke(get_class($this->trData), $this->trData, "decodeData", $in);
    }

    protected function encodePayload(PacketSerializer $out) : void{
        $out->putUnsignedVarInt($this->trData->getTypeId());

        $this->trData->encode($out);
    }
}