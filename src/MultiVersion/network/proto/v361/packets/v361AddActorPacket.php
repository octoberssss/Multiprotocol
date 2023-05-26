<?php

namespace MultiVersion\network\proto\v361\packets;

use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use UnexpectedValueException;

class v361AddActorPacket extends AddActorPacket{

	public const LEGACY_ID_MAP_BC = [
		EntityIds::NPC => "minecraft:npc",
		EntityIds::PLAYER => "minecraft:player",
		EntityIds::WITHER_SKELETON => "minecraft:wither_skeleton",
		EntityIds::HUSK => "minecraft:husk",
		EntityIds::STRAY => "minecraft:stray",
		EntityIds::WITCH => "minecraft:witch",
		EntityIds::ZOMBIE_VILLAGER => "minecraft:zombie_villager",
		EntityIds::BLAZE => "minecraft:blaze",
		EntityIds::MAGMA_CUBE => "minecraft:magma_cube",
		EntityIds::GHAST => "minecraft:ghast",
		EntityIds::CAVE_SPIDER => "minecraft:cave_spider",
		EntityIds::SILVERFISH => "minecraft:silverfish",
		EntityIds::ENDERMAN => "minecraft:enderman",
		EntityIds::SLIME => "minecraft:slime",
		EntityIds::ZOMBIE_PIGMAN => "minecraft:zombie_pigman",
		EntityIds::SPIDER => "minecraft:spider",
		EntityIds::SKELETON => "minecraft:skeleton",
		EntityIds::CREEPER => "minecraft:creeper",
		EntityIds::ZOMBIE => "minecraft:zombie",
		EntityIds::SKELETON_HORSE => "minecraft:skeleton_horse",
		EntityIds::MULE => "minecraft:mule",
		EntityIds::DONKEY => "minecraft:donkey",
		EntityIds::DOLPHIN => "minecraft:dolphin",
		EntityIds::TROPICALFISH => "minecraft:tropicalfish",
		EntityIds::WOLF => "minecraft:wolf",
		EntityIds::SQUID => "minecraft:squid",
		EntityIds::DROWNED => "minecraft:drowned",
		EntityIds::SHEEP => "minecraft:sheep",
		EntityIds::MOOSHROOM => "minecraft:mooshroom",
		EntityIds::PANDA => "minecraft:panda",
		EntityIds::SALMON => "minecraft:salmon",
		EntityIds::PIG => "minecraft:pig",
		EntityIds::VILLAGER => "minecraft:villager",
		EntityIds::COD => "minecraft:cod",
		EntityIds::PUFFERFISH => "minecraft:pufferfish",
		EntityIds::COW => "minecraft:cow",
		EntityIds::CHICKEN => "minecraft:chicken",
		EntityIds::BALLOON => "minecraft:balloon",
		EntityIds::LLAMA => "minecraft:llama",
		EntityIds::IRON_GOLEM => "minecraft:iron_golem",
		EntityIds::RABBIT => "minecraft:rabbit",
		EntityIds::SNOW_GOLEM => "minecraft:snow_golem",
		EntityIds::BAT => "minecraft:bat",
		EntityIds::OCELOT => "minecraft:ocelot",
		EntityIds::HORSE => "minecraft:horse",
		EntityIds::CAT => "minecraft:cat",
		EntityIds::POLAR_BEAR => "minecraft:polar_bear",
		EntityIds::ZOMBIE_HORSE => "minecraft:zombie_horse",
		EntityIds::TURTLE => "minecraft:turtle",
		EntityIds::PARROT => "minecraft:parrot",
		EntityIds::GUARDIAN => "minecraft:guardian",
		EntityIds::ELDER_GUARDIAN => "minecraft:elder_guardian",
		EntityIds::VINDICATOR => "minecraft:vindicator",
		EntityIds::WITHER => "minecraft:wither",
		EntityIds::ENDER_DRAGON => "minecraft:ender_dragon",
		EntityIds::SHULKER => "minecraft:shulker",
		EntityIds::ENDERMITE => "minecraft:endermite",
		EntityIds::MINECART => "minecraft:minecart",
		EntityIds::HOPPER_MINECART => "minecraft:hopper_minecart",
		EntityIds::TNT_MINECART => "minecraft:tnt_minecart",
		EntityIds::CHEST_MINECART => "minecraft:chest_minecart",
		EntityIds::COMMAND_BLOCK_MINECART => "minecraft:command_block_minecart",
		EntityIds::ARMOR_STAND => "minecraft:armor_stand",
		EntityIds::ITEM => "minecraft:item",
		EntityIds::TNT => "minecraft:tnt",
		EntityIds::FALLING_BLOCK => "minecraft:falling_block",
		EntityIds::XP_BOTTLE => "minecraft:xp_bottle",
		EntityIds::XP_ORB => "minecraft:xp_orb",
		EntityIds::EYE_OF_ENDER_SIGNAL => "minecraft:eye_of_ender_signal",
		EntityIds::ENDER_CRYSTAL => "minecraft:ender_crystal",
		EntityIds::SHULKER_BULLET => "minecraft:shulker_bullet",
		EntityIds::FISHING_HOOK => "minecraft:fishing_hook",
		EntityIds::DRAGON_FIREBALL => "minecraft:dragon_fireball",
		EntityIds::ARROW => "minecraft:arrow",
		EntityIds::SNOWBALL => "minecraft:snowball",
		EntityIds::EGG => "minecraft:egg",
		EntityIds::PAINTING => "minecraft:painting",
		EntityIds::THROWN_TRIDENT => "minecraft:thrown_trident",
		EntityIds::FIREBALL => "minecraft:fireball",
		EntityIds::SPLASH_POTION => "minecraft:splash_potion",
		EntityIds::ENDER_PEARL => "minecraft:ender_pearl",
		EntityIds::LEASH_KNOT => "minecraft:leash_knot",
		EntityIds::WITHER_SKULL => "minecraft:wither_skull",
		EntityIds::WITHER_SKULL_DANGEROUS => "minecraft:wither_skull_dangerous",
		EntityIds::BOAT => "minecraft:boat",
		EntityIds::LIGHTNING_BOLT => "minecraft:lightning_bolt",
		EntityIds::SMALL_FIREBALL => "minecraft:small_fireball",
		EntityIds::LLAMA_SPIT => "minecraft:llama_spit",
		EntityIds::AREA_EFFECT_CLOUD => "minecraft:area_effect_cloud",
		EntityIds::LINGERING_POTION => "minecraft:lingering_potion",
		EntityIds::FIREWORKS_ROCKET => "minecraft:fireworks_rocket",
		EntityIds::EVOCATION_FANG => "minecraft:evocation_fang",
		EntityIds::EVOCATION_ILLAGER => "minecraft:evocation_illager",
		EntityIds::VEX => "minecraft:vex",
		EntityIds::AGENT => "minecraft:agent",
		EntityIds::ICE_BOMB => "minecraft:ice_bomb",
		EntityIds::PHANTOM => "minecraft:phantom",
		EntityIds::TRIPOD_CAMERA => "minecraft:tripod_camera"
	];

	public static function fromLatest(AddActorPacket $pk) : self{
		$npk = new self();
		$npk->actorUniqueId = $pk->actorUniqueId;
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->type = $pk->type;
		$npk->position = $pk->position;
		$npk->motion = $pk->motion;
		$npk->pitch = $pk->pitch;
		$npk->yaw = $pk->yaw;
		$npk->headYaw = $pk->headYaw;
		$npk->attributes = $pk->attributes;
		$npk->metadata = $pk->metadata;
		$npk->links = $pk->links;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorUniqueId = $in->getActorUniqueId();
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->type = array_search($t = $in->getString(), self::LEGACY_ID_MAP_BC, true);
		if($this->type === false){
			throw new UnexpectedValueException("Can't map ID $t to legacy ID");
		}
		$this->position = $in->getVector3();
		$this->motion = $in->getVector3();
		$this->pitch = $in->getLFloat();
		$this->yaw = $in->getLFloat();
		$this->headYaw = $in->getLFloat();

		$attrCount = $in->getUnsignedVarInt();
		for($i = 0; $i < $attrCount; ++$i){
			$id = $in->getString();
			$min = $in->getLFloat();
			$current = $in->getLFloat();
			$max = $in->getLFloat();
			$this->attributes[] = new Attribute($id, $min, $max, $current, $current, []);
		}

		$this->metadata = $in->getEntityMetadata();
		$linkCount = $in->getUnsignedVarInt();
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[] = $in->getEntityLink();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorUniqueId($this->actorUniqueId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		if(!isset(self::LEGACY_ID_MAP_BC[$this->type])){
			throw new InvalidArgumentException("Unknown entity numeric ID $this->type");
		}
		$out->putString(self::LEGACY_ID_MAP_BC[$this->type]);
		$out->putVector3($this->position);
		$out->putVector3Nullable($this->motion);
		$out->putLFloat($this->pitch);
		$out->putLFloat($this->yaw);
		$out->putLFloat($this->headYaw);

		$out->putUnsignedVarInt(count($this->attributes));
		foreach($this->attributes as $attribute){
			$out->putString($attribute->getId());
			$out->putLFloat($attribute->getMin());
			$out->putLFloat($attribute->getCurrent());
			$out->putLFloat($attribute->getMax());
		}

		$out->putEntityMetadata($this->metadata);
		$out->putUnsignedVarInt(count($this->links));
		foreach($this->links as $link){
			$out->putEntityLink($link);
		}
	}
}
