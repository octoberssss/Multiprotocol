<?php

namespace MultiVersion\network\proto\v361;

use CortexPE\std\ReflectionUtils;
use JsonException;
use MultiVersion\network\MVInventoryManager;
use MultiVersion\network\MVNetworkSession;
use pocketmine\block\inventory\AnvilInventory;
use pocketmine\block\inventory\CraftingTableInventory;
use pocketmine\block\inventory\EnchantInventory;
use pocketmine\block\inventory\LoomInventory;
use pocketmine\block\inventory\StonecutterInventory;
use pocketmine\entity\Attribute;
use pocketmine\inventory\transaction\action\DestroyItemAction;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\CraftingTransaction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionBuilder;
use pocketmine\inventory\transaction\TransactionCancelledException;
use pocketmine\inventory\transaction\TransactionException;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\ItemStackContainerIdTranslator;
use pocketmine\network\mcpe\handler\ItemStackRequestExecutor;
use pocketmine\network\mcpe\handler\ItemStackRequestProcessException;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\network\mcpe\protocol\types\inventory\UIInventorySlotOffset;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\inventory\transaction\action\CreateItemAction;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\network\Network;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;

class v361InGamePacketHandler extends InGamePacketHandler{
    /** @var CraftingTransaction|null */
    protected $craftingTransaction = null;

    private Player $player;
    private MVNetworkSession $session;
    private MVInventoryManager $inventoryManager;

    public function __construct(Player $player, MVNetworkSession $session, MVInventoryManager $inventoryManager){
        parent::__construct($player, $session, $inventoryManager);
        $this->player = $player;
        $this->session = $session;
        $this->inventoryManager = $inventoryManager;

    }

    public function handlePlayerAction(PlayerActionPacket $packet) : bool{
        switch($packet->action){
            case PlayerAction::JUMP:
                $this->getPlayer()->jump();
                break;
            case PlayerAction::START_SPRINT:
                $this->getPlayer()->toggleSprint(true);
                break;
            case PlayerAction::STOP_SPRINT:
                $this->getPlayer()->toggleSprint(false);
                break;
            case PlayerAction::START_SNEAK:
                $this->getPlayer()->toggleSneak(true);
                break;
            case PlayerAction::STOP_SNEAK:
                $this->getPlayer()->toggleSneak(false);
                break;
            case PlayerAction::START_SWIMMING:
                $this->getPlayer()->toggleSwim(true);
                break;
            case PlayerAction::STOP_SWIMMING:
                $this->getPlayer()->toggleSwim(false);
                break;
            case PlayerAction::START_GLIDE:
                $this->getPlayer()->toggleGlide(true);
                break;
            case PlayerAction::STOP_GLIDE:
                $this->getPlayer()->toggleGlide(false);
                break;
            default:
                return parent::handlePlayerAction($packet);
        }
        return true;
    }



    /**
     * Internal function used to execute rollbacks when an action fails on a block.
     */
    private function onFailedBlockAction(Vector3 $blockPos, ?int $face) : void{
        #Failed to execute
        ReflectionUtils::invoke(InGamePacketHandler::class, $this, "onFailedBlockAction", $blockPos, $face);
    }


    private function handleUseItemOnEntityTransaction(UseItemOnEntityTransactionData $data) : bool{
        $target = $this->player->getWorld()->getEntity($data->getActorRuntimeId());
        if($target === null){
            return false;
        }

        $this->player->selectHotbarSlot($data->getHotbarSlot());

        switch($data->getActionType()){
            case UseItemOnEntityTransactionData::ACTION_INTERACT:
                $this->player->interactEntity($target, $data->getClickPosition());
                return true;
            case UseItemOnEntityTransactionData::ACTION_ATTACK:
                $this->player->attackEntity($target);
                return true;
        }

        return false;
    }


    private function handleUseItemTransaction(UseItemTransactionData $data) : bool{
        $this->getPlayer()->selectHotbarSlot($data->getHotbarSlot());

        switch($data->getActionType()){
            case UseItemTransactionData::ACTION_CLICK_BLOCK:
                //TODO: start hack for client spam bug
                $clickPos = $data->getClickPosition();
                $spamBug = ($this->lastRightClickData !== null &&
                    microtime(true) - $this->lastRightClickTime < 0.1 && //100ms
                    $this->lastRightClickData->getPlayerPosition()->distanceSquared($data->getPlayerPosition()) < 0.00001 &&
                    $this->lastRightClickData->getBlockPosition()->equals($data->getBlockPosition()) &&
                    $this->lastRightClickData->getClickPosition()->distanceSquared($clickPos) < 0.00001 //signature spam bug has 0 distance, but allow some error
                );
                //get rid of continued spam if the player clicks and holds right-click
                $this->lastRightClickData = $data;
                $this->lastRightClickTime = microtime(true);
                if($spamBug){
                    return true;
                }
                //TODO: end hack for client spam bug

                self::validateFacing($data->getFace());

                $blockPos = $data->getBlockPosition();
                $vBlockPos = new Vector3($blockPos->getX(), $blockPos->getY(), $blockPos->getZ());
                if(!$this->getPlayer()->interactBlock($vBlockPos, $data->getFace(), $clickPos)){
                    #$this->onFailedBlockAction($vBlockPos, $data->getFace());
                    ReflectionUtils::invoke(InGamePacketHandler::class, $this, "onFailedBlockAction", $vBlockPos, $data->getFace());
                }
                return true;
            case UseItemTransactionData::ACTION_BREAK_BLOCK:
                $blockPos = $data->getBlockPosition();
                $vBlockPos = new Vector3($blockPos->getX(), $blockPos->getY(), $blockPos->getZ());
                if(!$this->getPlayer()->breakBlock($vBlockPos)){
                    #$this->onFailedBlockAction($vBlockPos, null);
                    ReflectionUtils::invoke(InGamePacketHandler::class, $this, "onFailedBlockAction", $vBlockPos, null);
                }
                return true;
            case UseItemTransactionData::ACTION_CLICK_AIR:
                if($this->getPlayer()->isUsingItem()){
                    if(!$this->getPlayer()->consumeHeldItem()){
                        $hungerAttr = $this->getPlayer()->getAttributeMap()->get(Attribute::HUNGER) ?? throw new AssumptionFailedError();
                        $hungerAttr->markSynchronized(false);
                    }
                    return true;
                }
                $this->getPlayer()->useHeldItem();
                return true;
        }

        return false;
    }

    /**
     * @throws PacketHandlingException
     */
    private static function validateFacing(int $facing) : void{
        if(!in_array($facing, Facing::ALL, true)){
            throw new PacketHandlingException("Invalid facing value $facing");
        }
    }

    public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
        return true;
    }

    public function handleMovePlayer(MovePlayerPacket $packet) : bool{
        if($this->session->getPacketTranslator()::PROTOCOL_VERSION !== 361) {
            return true;
        }

        $rawPos = $packet->position;
        $rawYaw = $packet->yaw;
        $rawPitch = $packet->pitch;
        foreach([$rawPos->x, $rawPos->y, $rawPos->z, $rawYaw, $packet->headYaw, $rawPitch] as $float){
            if(is_infinite($float) || is_nan($float)){
                $this->session->getLogger()->debug("Invalid movement received, contains NAN/INF components");
                return false;
            }
        }

        $hasMoved = $this->lastPlayerAuthInputPosition === null || !$this->lastPlayerAuthInputPosition->equals($rawPos);
        $newPos = $rawPos->round(4)->subtract(0, 1.62, 0);

        if($this->forceMoveSync && $hasMoved){
            $curPos = $this->player->getLocation();

            if($newPos->distanceSquared($curPos) > 1){  //Tolerate up to 1 block to avoid problems with client-sided physics when spawning in blocks
                $this->session->getLogger()->debug("Got outdated pre-teleport movement, received " . $newPos . ", expected " . $curPos);
                //Still getting movements from before teleport, ignore them
                return false;
            }

            // Once we get a movement within a reasonable distance, treat it as a teleport ACK and remove position lock
            $this->forceMoveSync = false;
        }

        $yaw = fmod($rawYaw, 360);
        $pitch = fmod($rawPitch, 360);
        if($yaw < 0){
            $yaw += 360;
        }

        $this->lastPlayerAuthInputPosition = $rawPos;
        $this->player->setRotation($yaw, $pitch);
        $this->player->handleMovement($newPos);


        return true;
    }


    private function resolveOnOffInputFlags(int $inputFlags, int $startFlag, int $stopFlag) : ?bool{
        $enabled = ($inputFlags & (1 << $startFlag)) !== 0;
        $disabled = ($inputFlags & (1 << $stopFlag)) !== 0;
        if($enabled !== $disabled){
            return $enabled;
        }
        //neither flag was set, or both were set
        return null;
    }


    public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool{
        $result = true;
        if(count($packet->trData->getActions()) > 100){
            throw new PacketHandlingException("Too many actions in inventory transaction");
        }


        $this->inventoryManager->addPredictedSlotChanges($packet->trData->getActions());

        if($packet->trData instanceof NormalTransactionData){
            $result = $this->handleNormalTransaction($packet->trData);
        }elseif($packet->trData instanceof MismatchTransactionData){
            $this->session->getLogger()->debug("Mismatch transaction received");
            $this->inventoryManager->syncAll();
            $result = true;
        }elseif($packet->trData instanceof UseItemTransactionData){
            $result = $this->handleUseItemTransaction($packet->trData);
        }elseif($packet->trData instanceof UseItemOnEntityTransactionData){
            $result = $this->handleUseItemOnEntityTransaction($packet->trData);
        }elseif($packet->trData instanceof ReleaseItemTransactionData){
            $result = $this->handleReleaseItemTransaction($packet->trData);
        }



        $this->inventoryManager->syncMismatchedPredictedSlotChanges();
        return $result;
    }

    private function handleNormalTransaction(NormalTransactionData $data) : bool{

        /** @var InventoryAction[] $actions */
        $actions = [];
        foreach($data->getActions() as $networkInventoryAction){
            try{
                $action = $this->createInventoryAction($this->getPlayer(), $networkInventoryAction);
                if($action !== null){
                    $actions[] = $action;
                }
            }catch(\UnexpectedValueException $e){
                $this->getPlayer()->getServer()->getLogger()->debug("Unhandled inventory action from " . $this->getPlayer()->getName() . ": " . $e->getMessage());
                $this->inventoryManager->syncAll();
                return false;
            }
        }

        if(count($actions) === 0){
            //TODO: 1.13+ often sends transactions with nothing but useless crap in them, no need for the debug noise
            return true;
        }

        $this->player->setUsingItem(false);
        $transaction = new InventoryTransaction($this->player, $actions);
        $this->inventoryManager->onTransactionStart($transaction);

        try{
            $transaction->execute();
        }catch(TransactionException $e){
            $logger = $this->session->getLogger();
            $logger->debug("Failed to execute inventory transaction: " . $e->getMessage());
            $logger->debug("Actions: " . json_encode($data->getActions()));

            foreach($transaction->getInventories() as $inventory){
                $this->inventoryManager->syncContents($inventory);
            }

            return false;
        }
        return true;
    }

    /**
     * @throws TypeConversionException
     */
    public function createInventoryAction(Player $player, NetworkInventoryAction $action) : ?InventoryAction{
        switch($action->sourceType){
            case NetworkInventoryAction::SOURCE_CONTAINER:
                $window = $player->getNetworkSession()->getInvManager()->getWindow($action->windowId);
                if($window !== null){
                    $oldItem = TypeConverter::getInstance()->netItemStackToCore($action->oldItem->getItemStack());
                    $newItem = TypeConverter::getInstance()->netItemStackToCore($action->newItem->getItemStack());
                    return new SlotChangeAction($window, $action->inventorySlot, $oldItem, $newItem);
                }

                throw new \UnexpectedValueException("No open container with window ID $action->windowId");
            case NetworkInventoryAction::SOURCE_WORLD:
                if($action->inventorySlot !== NetworkInventoryAction::ACTION_MAGIC_SLOT_DROP_ITEM){
                    throw new \UnexpectedValueException("Only expecting drop-item world actions from the client!");
                }

                $newItem = TypeConverter::getInstance()->netItemStackToCore($action->newItem->getItemStack());
                return new DropItemAction($newItem);
            case NetworkInventoryAction::SOURCE_CREATIVE:
                switch($action->inventorySlot){
                    case NetworkInventoryAction::ACTION_MAGIC_SLOT_CREATIVE_DELETE_ITEM:
                        $newItem = TypeConverter::getInstance()->netItemStackToCore($action->newItem->getItemStack());
                        return new DestroyItemAction($newItem);
                    case NetworkInventoryAction::ACTION_MAGIC_SLOT_CREATIVE_CREATE_ITEM:
                        $oldItem = TypeConverter::getInstance()->netItemStackToCore($action->oldItem->getItemStack());
                        return new CreateItemAction($oldItem);
                    default:
                        throw new \UnexpectedValueException("Unexpected creative action type $action->inventorySlot");

                }
            default:
                throw new \UnexpectedValueException("Unknown inventory source type $action->sourceType");
        }
    }

    public function handlePlayerAuthInput(PlayerAuthInputPacket $packet): bool{
        return ReflectionUtils::invoke(InGamePacketHandler::class, $this, "handlePlayerAuthInput", $packet);
    }

    private function handleReleaseItemTransaction(ReleaseItemTransactionData $data) : bool{
        $this->getPlayer()->selectHotbarSlot($data->getHotbarSlot());
        $this->getPlayer()->getNetworkSession()->getInvManager()->addRawPredictedSlotChanges($data->getActions());

        //TODO: use transactiondata for rollbacks here (resending entire inventory is very wasteful)
        switch($data->getActionType()){
            case ReleaseItemTransactionData::ACTION_RELEASE:
                if(!$this->getPlayer()->releaseHeldItem()){
                    $this->getPlayer()->getNetworkSession()->getInvManager()->syncContents($this->getPlayer()->getInventory());
                }
                #Syncing Slots
                return true;
            case ReleaseItemTransactionData::ACTION_CONSUME:
                if($this->getPlayer()->isUsingItem()){
                    if(!$this->getPlayer()->consumeHeldItem()){
                        $hungerAttr = $this->getPlayer()->getAttributeMap()->get(Attribute::HUNGER) ?? throw new AssumptionFailedError();
                        $hungerAttr->markSynchronized(false);
                    }
                    return true;
                }
                $this->getPlayer()->useHeldItem();
                return true;
        }

        return false;
    }

    private const MAX_FORM_RESPONSE_DEPTH = 2; //modal/simple will be 1, custom forms 2 - they will never contain anything other than string|int|float|bool|null

    public function handleModalFormResponse(ModalFormResponsePacket $packet) : bool{
        if($packet?->formData !== null){
            try{
                $responseData = json_decode($packet->formData, true, self::MAX_FORM_RESPONSE_DEPTH, JSON_THROW_ON_ERROR);
            }catch(JsonException $e){
                throw PacketHandlingException::wrap($e, "Failed to decode form response data");
            }
            return $this->getPlayer()->onFormSubmit($packet->formId, $responseData);
        }else{
            throw new PacketHandlingException("Expected either formData or cancelReason to be set in ModalFormResponsePacket");
        }
    }

    private function getPlayer() : Player{
        return ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "player");
    }
}
