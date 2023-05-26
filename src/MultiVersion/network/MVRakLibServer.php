<?php

namespace MultiVersion\network;

use CortexPE\std\ReflectionUtils;
use pocketmine\network\mcpe\raklib\PthreadsChannelReader;
use pocketmine\network\mcpe\raklib\RakLibServer;
use pocketmine\network\mcpe\raklib\RakLibThreadCrashInfo;
use pocketmine\network\mcpe\ItemStackInfo;
use pocketmine\network\mcpe\raklib\SnoozeAwarePthreadsChannelWriter;
use pocketmine\snooze\SleeperNotifier;
use raklib\generic\Socket;
use raklib\generic\SocketException;
use raklib\server\ipc\RakLibToUserThreadMessageSender;
use raklib\server\ipc\UserToRakLibThreadMessageReceiver;
use raklib\server\ProtocolAcceptor;
use raklib\server\Server;
use raklib\utils\ExceptionTraceCleaner;
use raklib\utils\InternetAddress;
use Threaded;
use ThreadedLogger;
use Throwable;
use const pocketmine\PATH;

/**
 * Class MVRakLibServer
 *
 * We need this class to override the ProtocolAcceptor that gets initiated on thread start,
 * with one initiated on construction
 *
 * @package MultiVersion\network
 */
class MVRakLibServer extends RakLibServer{

	private InternetAddress $_address;
	private ProtocolAcceptor $acceptor;

	public function __construct(
		ThreadedLogger $logger,
		Threaded $mainToThreadBuffer,
		Threaded $threadToMainBuffer,
		InternetAddress $address,
		int $serverId,
		ProtocolAcceptor $acceptor,
		int $maxMtuSize = 1492,
		?SleeperNotifier $sleeper = null
	){
		$this->_address = $address;
		$this->serverId = $serverId;
		$this->maxMtuSize = $maxMtuSize;

		$this->logger = $logger;

		$this->mainToThreadBuffer = $mainToThreadBuffer;
		$this->threadToMainBuffer = $threadToMainBuffer;

		$this->mainPath = PATH;

		$this->acceptor = $acceptor;

		$this->mainThreadNotifier = $sleeper;
	}

	protected function onRun() : void{
		try{
			gc_enable();
			ini_set("display_errors", '1');
			ini_set("display_startup_errors", '1');

			register_shutdown_function([$this, "shutdownHandler"]);

			try{
				$socket = new Socket($this->_address);
			}catch(SocketException $e){
				ReflectionUtils::invoke(RakLibServer::class, $this, "setCrashInfo", RakLibThreadCrashInfo::fromThrowable($e));
				return;
			}
			$manager = new Server(
				$this->serverId,
				$this->logger,
				$socket,
				$this->maxMtuSize,
				$this->acceptor,
				new UserToRakLibThreadMessageReceiver(new PthreadsChannelReader($this->mainToThreadBuffer)),
				new RakLibToUserThreadMessageSender(new SnoozeAwarePthreadsChannelWriter($this->threadToMainBuffer, $this->mainThreadNotifier)),
				new ExceptionTraceCleaner($this->mainPath)
			);
			$this->synchronized(function() : void{
				$this->ready = true;
				$this->notify();
			});
			while(!$this->isKilled){
				$manager->tickProcessor();
			}
			$manager->waitShutdown();
			$this->cleanShutdown = true;
		}catch(Throwable $e){
			ReflectionUtils::invoke(RakLibServer::class, $this, "setCrashInfo", RakLibThreadCrashInfo::fromThrowable($e));
			$this->logger->logException($e);
		}
	}
}