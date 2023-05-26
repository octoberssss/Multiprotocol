<?php

namespace MultiVersion\network\proto;

use Closure;
use JsonMapper;
use JsonMapper_Exception;
use MultiVersion\network\MVInventoryManager;
use MultiVersion\network\MVNetworkSession;
use pocketmine\network\mcpe\handler\LoginPacketHandler;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\types\login\ClientData;
use pocketmine\network\PacketHandlingException;
use pocketmine\Server;

class MVLoginPacketHandler extends LoginPacketHandler{

	private MVNetworkSession $_session;
	private ?PacketTranslator $translator = null;

	public function __construct(Server $server, MVNetworkSession $session, Closure $playerInfoConsumer, Closure $authCallback){
		$this->_session = $session;
		parent::__construct($server, $session, $playerInfoConsumer, $authCallback);
	}

	public function handleLogin(LoginPacket $packet) : bool{
		if($this->shouldTranslateProtocol($packet->protocol)){
			$this->translator = $this->_session->getInterface()->getTranslator($packet->protocol);
			$this->_session->setPacketTranslator($this->translator);
			if($packet->protocol !== ProtocolInfo::CURRENT_PROTOCOL){
				$this->_session->getLogger()->info("Translating packets from protocol $packet->protocol");
				$packet->protocol = ProtocolInfo::CURRENT_PROTOCOL; // hack, jk this entire thing is a hack lmao
			}
		}

		return parent::handleLogin($packet);
	}

    /**
	 * @throws PacketHandlingException
	 */
	protected function parseClientData(string $clientDataJwt) : ClientData{
		try{
			[, $clientDataClaims,] = JwtUtils::parse($clientDataJwt);
		}catch(JwtException $e){
			throw PacketDecodeException::wrap($e);
		}

		$this->translator?->injectClientData($clientDataClaims);

		$mapper = new JsonMapper;
		$mapper->bEnforceMapType = false; //TODO: we don't really need this as an array, but right now we don't have enough models
		$mapper->bExceptionOnMissingData = true;
		$mapper->bExceptionOnUndefinedProperty = true;
		try{
            $clientDataClaims['CompatibleWithClientSideChunkGen'] = true;
			$clientData = $mapper->map($clientDataClaims, new ClientData);
		}catch(JsonMapper_Exception $e){
			throw PacketDecodeException::wrap($e);
		}
		return $clientData;
	}

	protected function isCompatibleProtocol(int $protocolVersion) : bool{
		return $protocolVersion === ProtocolInfo::CURRENT_PROTOCOL || $this->shouldTranslateProtocol($protocolVersion);
	}

	protected function shouldTranslateProtocol(int $protocolVersion) : bool{
		return in_array($protocolVersion, $this->_session->getInterface()->getAllowedMCPEProtocols(), true);
	}
}