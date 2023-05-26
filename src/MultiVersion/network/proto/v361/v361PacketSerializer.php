<?php

namespace MultiVersion\network\proto\v361;

use Closure;
use MultiVersion\network\proto\MVPacketSerializer;
use pocketmine\item\Durable;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\BlockPosMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\CompoundTagMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ShortMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\Vec3MetadataProperty;
use pocketmine\network\mcpe\protocol\types\FloatGameRule;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\GameRuleType;
use pocketmine\network\mcpe\protocol\types\IntGameRule;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use pocketmine\utils\BinaryDataException;
use UnexpectedValueException;

class v361PacketSerializer extends MVPacketSerializer{

	public function getSkin() : SkinData{
		$skinId = "";
		$skinData = $this->getSkinImage();
		$capeRawData = $this->getString();
		if(strlen($capeRawData) !== 0){
			$capeData = $this->getSkinImage();
		}
		$this->getString();
		$geometryData = $this->getString();
		return new SkinData(
			$skinId,
			"",
			null,
			$skinData,
			capeImage: $capeData ?? new SkinImage(0, 0, ""),
			geometryData: $geometryData,
		);
	}

	public function putSkin(SkinData $skin) : void{
		$this->putSkinImage($skin->getSkinImage());
		$this->putSkinImage($skin->getCapeImage());
		$this->putString("geometry.humanoid.custom");
		$this->putString($skin->getGeometryData());
	}

	private function getSkinImage() : SkinImage{
		$data = $this->getString();
		try{
			return SkinImage::fromLegacy($data);
		}catch(\InvalidArgumentException $e){
			throw new PacketDecodeException($e->getMessage(), 0, $e);
		}
	}

	private function putSkinImage(SkinImage $image) : void{
		$this->putString($image->getData());
	}

	public function getItemStack(Closure $writeExtraCrapInTheMiddle) : ItemStack{
		$id = $this->getVarInt();
		if($id === 0){
			return ItemStack::null();
		}

		$auxValue = $this->getVarInt();
		$data = $auxValue >> 8;
		$cnt = $auxValue & 0xff;

		$nbtLen = $this->getLShort();

		/** @var CompoundTag|null $nbt */
		$nbt = null;
		if($nbtLen === 0xffff){
			$c = $this->getByte();
			if($c !== 1){
				throw new UnexpectedValueException("Unexpected NBT count $c");
			}
			$decodedNBT = (new NetworkNbtSerializer())->read($this->buffer, $this->offset, 512)->mustGetCompoundTag();
			if(!($decodedNBT instanceof CompoundTag)){
				throw new UnexpectedValueException("Unexpected root tag type for itemstack");
			}
			$nbt = $decodedNBT;
		}elseif($nbtLen !== 0){
			throw new UnexpectedValueException("Unexpected fake NBT length $nbtLen");
		}

		$canBePlacedOn = [];
		for($i = 0, $canPlaceOn = $this->getVarInt(); $i < $canPlaceOn; ++$i){
			$canBePlacedOn[] = $this->getString();
		}

		$canDestroyBlocks = [];
		for($i = 0, $canDestroy = $this->getVarInt(); $i < $canDestroy; ++$i){
			$canDestroyBlocks[] = $this->getString();
		}
		$blockingTick = null;
		if($id === ItemIds::SHIELD){
			$blockingTick = $this->getVarLong(); //"blocking tick" (ffs mojang)
		}
		if($nbt !== null){
			if($nbt->getInt("Damage", -1) !== -1){
				$data = $nbt->getInt("Damage");
				$nbt->removeTag("Damage");
				if($nbt->count() === 0){
					$nbt = null;
					goto end;
				}
			}
			if(($conflicted = $nbt->getTag("___Damage_ProtocolCollisionResolution___")) !== null){
				$nbt->removeTag("___Damage_ProtocolCollisionResolution___");
				$nbt->setTag("Damage", $conflicted);
			}
		}
		end:
		return new ItemStack(
			$id, $data, $cnt,
			$this->getContext()->getBlockMapping()->toRuntimeId($id, $data), $nbt,
			$canBePlacedOn, $canDestroyBlocks, $blockingTick
		);
	}

	public function putItemStack(ItemStack $item, Closure $writeExtraCrapInTheMiddle) : void{
		if($item->getId() === 0){
			$this->putVarInt(0);

			return;
		}

		$this->putVarInt($item->getId());
		$auxValue = (($item->getMeta() & 0x7fff) << 8) | $item->getCount();
		$this->putVarInt($auxValue);

		$nbt = null;
		if($item->getNbt() !== null){
			$nbt = clone $item->getNbt();
		}

		$actualItem = ItemFactory::getInstance()->get($item->getId(), $item->getMeta(), $item->getCount());

		if($actualItem instanceof Durable and $actualItem->getDamage() > 0){
			if($nbt !== null){
				if(($existing = $nbt->getTag("Damage")) !== null){
					$nbt->removeTag("Damage");
					$nbt->setTag("___Damage_ProtocolCollisionResolution___", $existing);
				}
			}else{
				$nbt = new CompoundTag();
			}
			$nbt->setInt("Damage", $actualItem->getDamage());
		}

		if($nbt !== null){
			$this->putLShort(0xffff);
			$this->putByte(1); //TODO: some kind of count field? always 1 as of 1.9.0
			$this->put((new NetworkNbtSerializer())->write(new TreeRoot($nbt)));
		}else{
			$this->putLShort(0);
		}

		$this->putVarInt(count($item->getCanPlaceOn()));
		foreach($item->getCanPlaceOn() as $toWrite){
			$this->putString($toWrite);
		}
		$this->putVarInt(count($item->getCanDestroy()));
		foreach($item->getCanDestroy() as $toWrite){
			$this->putString($toWrite);
		}

		if($item->getId() === ItemIds::SHIELD){
			$this->putVarLong($item->getShieldBlockingTick() ?? 0); //"blocking tick" (ffs mojang)
		}
	}

	/**
	 * @throws BinaryDataException
	 */
	public function getEntityLink() : EntityLink{
		$fromEntityUniqueId = $this->getActorUniqueId();
		$toEntityUniqueId = $this->getActorUniqueId();
		$type = $this->getByte();
		$immediate = $this->getBool();
		return new EntityLink($fromEntityUniqueId, $toEntityUniqueId, $type, $immediate, /*v361 has no caused by rider flag*/ false);
	}

	public function putEntityLink(EntityLink $link) : void{
		$this->putActorUniqueId($link->fromActorUniqueId);
		$this->putActorUniqueId($link->toActorUniqueId);
		$this->putByte($link->type);
		$this->putBool($link->immediate);
		// v361 has no caused by rider flag
	}

	public function getRecipeIngredient() : RecipeIngredient{
		$id = $this->getVarInt();
		if($id === 0){
			return new RecipeIngredient(new IntIdMetaItemDescriptor(0, 0), 0);
		}
		$meta = $this->getVarInt();
		if($meta === 0x7fff){
			$meta = -1;
		}
		$count = $this->getVarInt();
		return new RecipeIngredient(new IntIdMetaItemDescriptor($id, $meta), $count);
	}

	public function putRecipeIngredient(RecipeIngredient $item) : void{
		$descriptor = $item->getDescriptor();
		if($descriptor?->getTypeId() === IntIdMetaItemDescriptor::ID){
			/** @var IntIdMetaItemDescriptor $descriptor */
			if($descriptor->getId() === 0){
				$this->putVarInt(0);
			}else{
				$this->putVarInt($descriptor->getId());
				$this->putVarInt($descriptor->getMeta() & 0x7fff);
				$this->putVarInt($item->getCount());
			}
		}else{
			$this->putVarInt(0);
		}
	}

	/**
	 * Reads gamerules
	 *
	 * @return GameRule[] game rule name => value
	 * @phpstan-return array<string, GameRule>
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getGameRules() : array{
		$count = $this->getUnsignedVarInt();
		$rules = [];
		for($i = 0; $i < $count; ++$i){
			$name = $this->getString();
			$type = $this->getUnsignedVarInt();
			$rules[$name] = $this->readGameRule($type);
		}

		return $rules;
	}

	private function readGameRule(int $type) : GameRule{
		return match ($type) {
			GameRuleType::BOOL => BoolGameRule::decode($this, false),
			GameRuleType::INT => IntGameRule::decode($this, false),
			GameRuleType::FLOAT => FloatGameRule::decode($this, false),
			default => throw new PacketDecodeException("Unknown gamerule type $type"),
		};
	}

	/**
	 * Writes a gamerule array
	 *
	 * @param GameRule[]                      $rules
	 *
	 * @phpstan-param array<string, GameRule> $rules
	 */
	public function putGameRules(array $rules) : void{
		$this->putUnsignedVarInt(count($rules));
		foreach($rules as $name => $rule){
			$this->putString($name);
			$this->putUnsignedVarInt($rule->getTypeId());
			$rule->encode($this);
		}
	}

	/**
	 * Decodes entity metadata from the stream.
	 *
	 * @return MetadataProperty[]
	 * @phpstan-return array<int, MetadataProperty>
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getEntityMetadata() : array{
		$count = $this->getUnsignedVarInt();
		$metadata = [];
		for($i = 0; $i < $count; ++$i){
			$key = $this->getUnsignedVarInt();
			$type = $this->getUnsignedVarInt();

			$metadata[$key] = $this->readMetadataProperty($type);
		}

		/** @var LongMetadataProperty $flag1Property */
		$flag1Property = $metadata[EntityMetadataProperties::FLAGS] ?? new LongMetadataProperty(0);
		/** @var LongMetadataProperty $flag2Property */
		$flag2Property = $metadata[EntityMetadataProperties::FLAGS2] ?? new LongMetadataProperty(0);
		$flag1 = $flag1Property->getValue();
		$flag2 = $flag2Property->getValue();

		$flag2 <<= 1; // shift left by 1, leaving a 0 at the end
		$flag2 |= (($flag1 >> 63) & 1); // push the last bit from flag1 to the first bit of flag2

		$newFlag1 = $flag1 & ~(~0 << (EntityMetadataFlags::CAN_DASH - 1)); // don't include CAN_DASH and above
		$lastHalf = $flag1 & (~0 << (EntityMetadataFlags::CAN_DASH - 1)); // include everything after where CAN_DASH would be
		$lastHalf <<= 1; // shift left by 1, CAN_DASH is now 0
		$newFlag1 |= $lastHalf; // combine the two halves

		$metadata[EntityMetadataProperties::FLAGS2] = new LongMetadataProperty($flag2);
		$metadata[EntityMetadataProperties::FLAGS] = new LongMetadataProperty($newFlag1);

		return $metadata;
	}

	private function readMetadataProperty(int $type) : MetadataProperty{
		return match ($type) {
			ByteMetadataProperty::ID => ByteMetadataProperty::read($this),
			ShortMetadataProperty::ID => ShortMetadataProperty::read($this),
			IntMetadataProperty::ID => IntMetadataProperty::read($this),
			FloatMetadataProperty::ID => FloatMetadataProperty::read($this),
			StringMetadataProperty::ID => StringMetadataProperty::read($this),
			CompoundTagMetadataProperty::ID => CompoundTagMetadataProperty::read($this),
			BlockPosMetadataProperty::ID => BlockPosMetadataProperty::read($this),
			LongMetadataProperty::ID => LongMetadataProperty::read($this),
			Vec3MetadataProperty::ID => Vec3MetadataProperty::read($this),
			default => throw new PacketDecodeException("Unknown entity metadata type " . $type),
		};
	}

	/**
	 * Writes entity metadata to the packet buffer.
	 *
	 * @param MetadataProperty[]                   $metadata
	 *
	 * @phpstan-param array<int, MetadataProperty> $metadata
	 */
	public function putEntityMetadata(array $metadata) : void{
		$data = $metadata;
		foreach($data as $type => $val){
			$metadata[match ($type) {
				EntityMetadataProperties::AREA_EFFECT_CLOUD_RADIUS => 60,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_WAITING => 61,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_PARTICLE_ID => 62,
				EntityMetadataProperties::SHULKER_ATTACH_FACE => 64,
				EntityMetadataProperties::SHULKER_ATTACH_POS => 66,
				EntityMetadataProperties::TRADING_PLAYER_EID => 67,
				EntityMetadataProperties::COMMAND_BLOCK_COMMAND => 70,
				EntityMetadataProperties::COMMAND_BLOCK_LAST_OUTPUT => 71,
				EntityMetadataProperties::COMMAND_BLOCK_TRACK_OUTPUT => 72,
				EntityMetadataProperties::CONTROLLING_RIDER_SEAT_NUMBER => 73,
				EntityMetadataProperties::STRENGTH => 74,
				EntityMetadataProperties::MAX_STRENGTH => 75,
				EntityMetadataProperties::LIMITED_LIFE => 77,
				EntityMetadataProperties::ARMOR_STAND_POSE_INDEX => 78,
				EntityMetadataProperties::ENDER_CRYSTAL_TIME_OFFSET => 79,
				EntityMetadataProperties::ALWAYS_SHOW_NAMETAG => 80,
				EntityMetadataProperties::COLOR_2 => 81,
				EntityMetadataProperties::SCORE_TAG => 83,
				EntityMetadataProperties::BALLOON_ATTACHED_ENTITY => 84,
				EntityMetadataProperties::PUFFERFISH_SIZE => 85,
				EntityMetadataProperties::BOAT_BUBBLE_TIME => 86,
				EntityMetadataProperties::PLAYER_AGENT_EID => 87,
				EntityMetadataProperties::EAT_COUNTER => 90,
				EntityMetadataProperties::FLAGS2 => 91,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_DURATION => 94,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_SPAWN_TIME => 95,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_RADIUS_PER_TICK => 96,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_RADIUS_CHANGE_ON_PICKUP => 97,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_PICKUP_COUNT => 98,
				EntityMetadataProperties::INTERACTIVE_TAG => 99,
				EntityMetadataProperties::TRADE_TIER => 100,
				EntityMetadataProperties::MAX_TRADE_TIER => 101,
				EntityMetadataProperties::TRADE_XP => 102,
				EntityMetadataProperties::SKIN_ID => 104,
				EntityMetadataProperties::COMMAND_BLOCK_TICK_DELAY => 105,
				EntityMetadataProperties::COMMAND_BLOCK_EXECUTE_ON_FIRST_TICK => 106,
				EntityMetadataProperties::AMBIENT_SOUND_INTERVAL_MIN => 107,
				EntityMetadataProperties::AMBIENT_SOUND_INTERVAL_RANGE => 108,
				EntityMetadataProperties::AMBIENT_SOUND_EVENT => 109,
				default => $type,
			}] = $val;
		}

		/** @var LongMetadataProperty $flag1Property */
		$flag1Property = $metadata[EntityMetadataProperties::FLAGS] ?? new LongMetadataProperty(0);
		/** @var LongMetadataProperty $flag2Property */
		$flag2Property = $metadata[EntityMetadataProperties::FLAGS2] ?? new LongMetadataProperty(0);
		$flag1 = $flag1Property->getValue();
		$flag2 = $flag2Property->getValue();

		if($flag1 !== 0 || $flag2 !== 0){
			$newFlag1 = $flag1 & ~(~0 << (EntityMetadataFlags::CAN_DASH - 1));
			$lastHalf = $flag1 & (~0 << EntityMetadataFlags::CAN_DASH);
			$lastHalf >>= 1;
			$lastHalf &= PHP_INT_MAX;

			$newFlag1 |= $lastHalf;

			if($flag2 !== 0){
				$flag2 = $flag2Property->getValue();
				$newFlag1 ^= ($flag2 & 1) << 63;
				$flag2 >>= 1;
				$flag2 &= PHP_INT_MAX;

				$metadata[EntityMetadataProperties::FLAGS2] = new LongMetadataProperty($flag2);
			}

			$metadata[EntityMetadataProperties::FLAGS] = new LongMetadataProperty($newFlag1);
		}

		$this->putUnsignedVarInt(count($metadata));
		foreach($metadata as $key => $d){
			$this->putUnsignedVarInt($key);
			$this->putUnsignedVarInt($d->getTypeId());
			$d->write($this);
		}
	}

	/**
	 * Reads a list of Attributes from the stream.
	 * @return Attribute[]
	 *
	 * @throws BinaryDataException
	 */
	public function getAttributeList() : array{
		$list = [];
		$count = $this->getUnsignedVarInt();

		for($i = 0; $i < $count; ++$i){
			$min = $this->getLFloat();
			$max = $this->getLFloat();
			$current = $this->getLFloat();
			$default = $this->getLFloat();
			$id = $this->getString();

			$list[] = new Attribute($id, $min, $max, $current, $default, []);
		}

		return $list;
	}

	/**
	 * Writes a list of Attributes to the packet buffer using the standard format.
	 */
	public function putAttributeList(Attribute ...$attributes) : void{
		$this->putUnsignedVarInt(count($attributes));
		foreach($attributes as $attribute){
			$this->putLFloat($attribute->getMin());
			$this->putLFloat($attribute->getMax());
			$this->putLFloat($attribute->getCurrent());
			$this->putLFloat($attribute->getDefault());
			$this->putString($attribute->getId());
		}
	}
}