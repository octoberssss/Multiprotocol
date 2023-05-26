<?php

namespace MultiVersion\network;

use raklib\server\ProtocolAcceptor;

class MVRakNetProtocolAcceptor implements ProtocolAcceptor{

	/**
	 * MVProtocolAcceptor constructor.
	 *
	 * @param int[] $versions
	 */
	public function __construct(
		private array $versions
	){

	}

	public function accepts(int $protocolVersion) : bool{
		return in_array($protocolVersion, $this->versions, true);
	}

	public function getPrimaryVersion() : int{
		return max($this->versions);
	}
}