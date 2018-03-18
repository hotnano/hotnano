<?php

namespace HotNano\Service;

use HotNano\Exception\RpcException;
use HotNano\RaiBlocks\Server;

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
     * @var string
     */
    private $walletId;

    /**
     * @var Server
     */
    private $server;

    public function __construct(string $rpcHost, int $rpcPort, string $walletId)
    {
        $this->rpcHost = $rpcHost;
        $this->rpcPort = $rpcPort;
        $this->walletId = $walletId;

        $this->server = new Server($this->rpcHost, $this->rpcPort);
    }

    public function createNewAccount()
    {
        $this->server->accountCreate($this->walletId);
        $result = $this->server->run();
        if (array_key_exists('error', $result)) {
            throw new RpcException($result['error'], 100);
        }
        if (!array_key_exists('account', $result)) {
            throw new RpcException('No "account" field found.', 150);
        }

        return $result['account'];
    }

    public function getAccountHistory(string $accountId, int $offset = 0, int $count = 100)
    {
        $this->server->accountHistory($accountId, null, $offset, $count);
        $result = $this->server->run();
        if (array_key_exists('error', $result)) {
            throw new RpcException($result['error'], 200);
        }
        if (!array_key_exists('history', $result)) {
            throw new RpcException('No "history" field found.', 250);
        }

        if (!isset($result['history']) || !is_array($result['history']) || !$result['history']) {
            return [];
        }

        $history = $result['history'];
        // rsort($history);

        return $history;
    }

    public function getAccountInfo(string $accountId)
    {
        $this->server->accountInfo($accountId);
        $result = $this->server->run();
        if (array_key_exists('error', $result)) {
            throw new RpcException($result['error'], 300);
        }

        return $result;
    }

    public function getAccountBalance(string $accountId)
    {
        $this->server->accountBalance($accountId);
        $result = $this->server->run();
        if (array_key_exists('error', $result)) {
            throw new RpcException($result['error'], 400);
        }

        if (!array_key_exists('balance', $result)) {
            $result['balance'] = '0';
        }

        if (!array_key_exists('pending', $result)) {
            $result['pending'] = '0';
        }

        return $result;
    }

    public function send(string $source, string $destination, string $amount)
    {
        $this->server->send($this->walletId, $source, $destination, $amount);
        $result = $this->server->run();
        if (array_key_exists('error', $result)) {
            throw new RpcException($result['error'], 500);
        }

        if (!array_key_exists('block', $result)) {
            throw new RpcException('No "block" field found.', 250);
        }

        return $result['block'];
    }

    public function raiFromRaw(string $amount)
    {
        $this->server->raiFromRaw($amount);
        $result = $this->server->run();
        // if (array_key_exists('error', $result)) {
        //     throw new RpcException($result['error'], 10000);
        // }
        if (!array_key_exists('amount', $result)) {
            throw new RpcException('No "amount" field found.', 10100);
        }

        return $result['amount'];
    }

    public function raiToRaw(string $amount)
    {
        $this->server->raiToRaw($amount);
        $result = $this->server->run();
        // if (array_key_exists('error', $result)) {
        //     throw new RpcException($result['error'], 10000);
        // }
        if (!array_key_exists('amount', $result)) {
            throw new RpcException('No "amount" field found.', 10100);
        }

        return $result['amount'];
    }

    public function findNewOwner(string $accountId, int $offset, string $targetPrice)
    {
        $owner = null;
        $refunds = [];

        throw new \RuntimeException('WRONG IMPLEMENTATION'); // @todo

        $page = 0;
        do {
            $page++;

            // Process History since last frontier.
            $history = $this->getAccountHistory($accountId, $offset, 2);
            $historyCount = count($history);
            foreach ($history as $row) {
                printf("history row p=%d o=%d: %s\n", $page, $offset, $row['hash']);

                $offset++;

                if ('receive' != $row['type']) {
                    continue;
                }

                $rai = $this->raiFromRaw($row['amount']);

                if ($targetPrice == $rai) {
                    printf(" -> match '%s' == '%s'\n", $targetPrice, $rai);
                    $owner = $row;
                } else {
                    // Refund
                    $refunds[] = $row;
                }
            }
        } while ($page <= 1000 && $historyCount > 0);

        return [
            'offset' => $offset,
            'owner' => $owner,
            'refunds' => $refunds,
        ];
    }
}
