<?php

namespace MultiVersion\network\proto\latest;

use Closure;
use MultiVersion\network\proto\MVPacketSerializer;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\utils\BinaryDataException;

class LatestPacketSerializer extends MVPacketSerializer{

	/**
	 * @phpstan-param Closure(PacketSerializer) : void $readExtraCrapInTheMiddle
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	public function getItemStack(Closure $readExtraCrapInTheMiddle) : ItemStack{
		$id = $this->getVarInt();
		if($id === 0){
			return ItemStack::null();
		}

		$count = $this->getLShort();
		$meta = $this->getUnsignedVarInt();

		$readExtraCrapInTheMiddle($this);

		$blockRuntimeId = $this->getVarInt();
		$extraData = self::newDecoder($this->getString(), 0, $this->getContext());
		return (static function() use ($extraData, $id, $meta, $count, $blockRuntimeId) : ItemStack{
			$nbtLen = $extraData->getLShort();

			/** @var CompoundTag|null $compound */
			$compound = null;
			if($nbtLen === 0xffff){
				$nbtDataVersion = $extraData->getByte();
				if($nbtDataVersion !== 1){
					throw new PacketDecodeException("Unexpected NBT data version $nbtDataVersion");
				}
				$offset = $extraData->getOffset();
				try{
					$compound = (new LittleEndianNbtSerializer())->read($extraData->getBuffer(), $offset, 512)->mustGetCompoundTag();
				}catch(NbtDataException $e){
					throw PacketDecodeException::wrap($e, "Failed decoding NBT root");
				}finally{
					$extraData->setOffset($offset);
				}
			}elseif($nbtLen !== 0){
				throw new PacketDecodeException("Unexpected fake NBT length $nbtLen");
			}

			$canPlaceOn = [];
			for($i = 0, $canPlaceOnCount = $extraData->getLInt(); $i < $canPlaceOnCount; ++$i){
				$canPlaceOn[] = $extraData->get($extraData->getLShort());
			}

			$canDestroy = [];
			for($i = 0, $canDestroyCount = $extraData->getLInt(); $i < $canDestroyCount; ++$i){
				$canDestroy[] = $extraData->get($extraData->getLShort());
			}

			$shieldBlockingTick = null;
			if($id === $extraData->shieldRuntimeId){
				$shieldBlockingTick = $extraData->getLLong();
			}

			if(!$extraData->feof()){
				throw new PacketDecodeException("Unexpected trailing extradata for network item $id");
			}

			return new ItemStack($id, $meta, $count, $blockRuntimeId, $compound, $canPlaceOn, $canDestroy, $shieldBlockingTick);
		})();
	}

	/**
	 * @phpstan-param Closure(PacketSerializer) : void $writeExtraCrapInTheMiddle
	 */
	public function putItemStack(ItemStack $item, Closure $writeExtraCrapInTheMiddle) : void{
		if($item->getId() === 0){
			$this->putVarInt(0);

			return;
		}

		$this->putVarInt($item->getId());
		$this->putLShort($item->getCount());
		$this->putUnsignedVarInt($item->getMeta());

		$writeExtraCrapInTheMiddle($this);

		$this->putVarInt($item->getBlockRuntimeId());
		$this->putString((function() use ($item) : string{
			$extraData = self::newEncoder($this->getContext());

			$nbt = $item->getNbt();
			if($nbt !== null){
				$extraData->putLShort(0xffff);
				$extraData->putByte(1); //TODO: NBT data version (?)
				$extraData->put((new LittleEndianNbtSerializer())->write(new TreeRoot($nbt)));
			}else{
				$extraData->putLShort(0);
			}

			$extraData->putLInt(count($item->getCanPlaceOn()));
			foreach($item->getCanPlaceOn() as $entry){
				$extraData->putLShort(strlen($entry));
				$extraData->put($entry);
			}
			$extraData->putLInt(count($item->getCanDestroy()));
			foreach($item->getCanDestroy() as $entry){
				$extraData->putLShort(strlen($entry));
				$extraData->put($entry);
			}

			$blockingTick = $item->getShieldBlockingTick();
			if($item->getId() === $extraData->shieldRuntimeId){
				$extraData->putLLong($blockingTick ?? 0);
			}
			return $extraData->getBuffer();
		})());
	}
}