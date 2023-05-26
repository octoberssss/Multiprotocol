<?php

namespace MultiVersion\network\proto\v361\packets;

use Exception;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v361PlayerSkinPacket extends PlayerSkinPacket{

	public Skin $_skin;
	public bool $premiumSkin = false;

	public static function fromLatest(PlayerSkinPacket $pk) : self{
		$npk = new self();
		$npk->uuid = $pk->uuid;
		$npk->_skin = SkinAdapterSingleton::get()->fromSkinData($pk->skin);
		$npk->oldSkinName = $pk->oldSkinName;
		$npk->newSkinName = $pk->newSkinName;
		$npk->premiumSkin = $pk->skin->isPremium();
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->uuid = $in->getUUID();

		$skinId = $in->getString();
		$this->newSkinName = $in->getString();
		$this->oldSkinName = $in->getString();
		$skinData = $in->getString();
		$capeData = $in->getString();
		$geometryModel = $in->getString();
		$geometryData = $in->getString();

		try{
			$this->_skin = new Skin($skinId, $skinData, $capeData, $geometryModel, $geometryData);
		}catch(Exception $e){
			throw new PacketDecodeException("Unable to decode skin, message {$e->getMessage()}");
		}

		$this->premiumSkin = $in->getBool();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putUUID($this->uuid);

		$out->putString($this->_skin->getSkinId());
		$out->putString($this->newSkinName);
		$out->putString($this->oldSkinName);
		$out->putString($this->_skin->getSkinData());
		$out->putString($this->_skin->getCapeData());
		$out->putString($this->_skin->getGeometryName());
		$out->putString($this->_skin->getGeometryData());

		$out->putBool($this->premiumSkin);
	}
}