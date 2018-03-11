<?php

namespace HotNano\Service;

use dansup\RaiBlocks\Server;

/**
 * Communication to the Nano Core process via RPC.
 */
class NanoService
{
    /**
     * @var string
     */
    private $rpcHost;

    /**
     * @var int
     */
    private $rpcPort;

    /**
     * @var
     */
    private $server;

    public function __construct(string $rpcHost, int $rpcPort)
    {
        $this->rpcHost = $rpcHost;
        $this->rpcPort = $rpcPort;

        $this->server = new Server($this->rpcHost,$this->rpcPort);
    }
}
