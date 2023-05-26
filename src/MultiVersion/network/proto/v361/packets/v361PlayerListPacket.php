<?php

namespace MultiVersion\network\proto\v361\packets;

use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;

class v361PlayerListPacket extends PlayerListPacket{

	public static function fromLatest(PlayerListPacket $pk) : self{
		$npk = new self();
		$npk->entries = $pk->entries;
		$npk->type = $pk->type;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->type = $in->getByte();
		$count = $in->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$entry = new PlayerListEntry();

			$entry->uuid = $in->getUUID();
			if($this->type === self::TYPE_ADD){
				$entry->actorUniqueId = $in->getActorUniqueId();
				$entry->username = $in->getString();

				$skinId = $in->getString();
				$skinData = $in->getString();
				$capeData = $in->getString();
				$geometryName = $in->getString();
				$geometryData = $in->getString();

				$entry->skinData = SkinAdapterSingleton::get()->toSkinData(new Skin(
					$skinId, $skinData, $capeData, $geometryName, $geometryData
				));

				$entry->xboxUserId = $in->getString();
				$entry->platformChatId = $in->getString();
			}

			$this->entries[$i] = $entry;
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putByte($this->type);
		$out->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			$out->putUUID($entry->uuid);
			if($this->type === self::TYPE_ADD){
				$out->putActorUniqueId($entry->actorUniqueId);
				$out->putString($entry->username);

				$skData = SkinAdapterSingleton::get()->fromSkinData($entry->skinData);
				$out->putString($skData->getSkinId());
				$out->putString($skData->getSkinData());
				$out->putString($skData->getCapeData());
				$out->putString($skData->getGeometryName());
				$out->putString($skData->getGeometryData());

				$out->putString($entry->xboxUserId);
				$out->putString($entry->platformChatId);
			}
		}
	}
}