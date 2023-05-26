<?php

namespace MultiVersion\network\proto\v486;

use CortexPE\std\ReflectionUtils;
use JsonException;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;

class v486InGamePacketHandler extends InGamePacketHandler{

	/*public function handlePlayerAction(PlayerActionPacket $packet) : bool{
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
	}*/

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

    public function handleRequestChunkRadius(RequestChunkRadiusPacket $packet) : bool{
        $this->getPlayer()->setViewDistance($packet->radius);
        return true;
    }

	private function getPlayer() : Player{
		return ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "player");
	}
}
