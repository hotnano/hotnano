<?php

namespace HotNano\Command;

use Carbon\Carbon;
use HotNano\Entity\Entity;
use HotNano\RaiBlocks\Server;
use HotNano\Service\DatabaseService;
use HotNano\Service\NanoService;
use HotNano\Service\RenderService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCommand extends Command
{
    protected static $defaultName = 'hotnano:update';

    /**
     * @var SymfonyStyle
     */
    private $io;

    protected function configure()
    {
        $this->setDescription('Update Frontend.');

        $webDefaultPath = realpath(sprintf('%s/../../web', __DIR__));
        $templatesDefaultPath = realpath(sprintf('%s/../../templates', __DIR__));

        $this
            ->addOption('--output', null, InputOption::VALUE_OPTIONAL, 'Output path for HTML files.', $webDefaultPath)
            ->addOption('--templates', null, InputOption::VALUE_OPTIONAL, 'Path to template directory.', $templatesDefaultPath)
            ->addOption('--timeout', null, InputOption::VALUE_OPTIONAL, 'Spam protection in seconds.', 60)
            ->addOption('--rpc_host', null, InputOption::VALUE_OPTIONAL, 'RPC Host to Nano Core client.', '127.0.0.1')
            ->addOption('--rpc_port', null, InputOption::VALUE_OPTIONAL, 'RPC Port to Nano Core client.', 7076)
            ->addOption('--wallet', null, InputOption::VALUE_OPTIONAL, 'Wallet ID')
            ->addOption('--ttl', null, InputOption::VALUE_OPTIONAL, 'TTL', 120)
            ->addOption('--starting_price', null, InputOption::VALUE_OPTIONAL, 'Starting price for new entities in Rai. Default: 0.05 Nano', 50000)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getApplication();
        $appVersion = $app->getVersion();
        $appName = $app->getName();
        $rpcHost = $input->getOption('rpc_host');
        $rpcPort = intval($input->getOption('rpc_port'));
        $walletId = $input->getOption('wallet');
        $ttl = intval($input->getOption('ttl'));
        $startingPrice = intval($input->getOption('starting_price'));

        $this->io = new SymfonyStyle($input, $output);

        $webPath = $input->getOption('output');
        $templatesPath = $input->getOption('templates');

        $cachePath = realpath(sprintf('%s/../../tmp/twig_cache', __DIR__));

        $nanoService = new NanoService($rpcHost, $rpcPort, $walletId);
        $nanoService->setOutput($output);

        $dbFilePath = sprintf('%s/db.yml', $webPath);
        $dbService = new DatabaseService($dbFilePath);
        $dbService->loadDb();
        $entities = $dbService->getEntities();

        // A list of Entities which should be renderted at the frontend.
        // The rest has errors.
        /** @var Entity[] $renderEntities */
        $renderEntities = [];

        foreach ($entities as $entity) {
            $this->io->text(sprintf('entity %s active=%s', $entity->getId(), $entity->isActive() ? 'Y' : 'N'));

            if (!$entity->isActive()) {
                continue;
            }

            try {
                if (null === $entity->getTargetPrice()) {
                    $entity->setTargetPrice($startingPrice);
                }

                if (null === $entity->getTargetAddress()) {
                    $newAccount = $nanoService->createNewAccount();
                    $entity->setTargetAddress($newAccount);

                    $targetTime = Carbon::now('UTC');
                    $targetTime->addSeconds($ttl);
                    $entity->setTargetTime($targetTime);
                }

                if ($entity->canBeClaimed()) {
                    $this->io->text(sprintf(' -> can be claimed: %s', $entity->getTargetTimeFormatted()));

                    $targetAddress = $entity->getTargetAddress();

                    $balanceTmp = $nanoService->getAccountBalance($targetAddress);
                    if ($balanceTmp['pending'] === '0') {
                        $entity->setHasPending(false);

                        $oldOwner = $entity->getOwnerAddress();
                        $frontier = $entity->getFrontier();
                        $targetPrice = $entity->getTargetPrice();
                        $targetPriceRaw = $nanoService->raiToRaw($targetPrice);
                        // $currentPrice = $entity->getCurrentPrice();

                        [
                            'owner' => $newOwner,
                            'owner_amount' => $newOwnerAmount,
                            'refunds' => $refunds,
                            'refunds_amount' => $refundsAmount,
                            'frontier' => $newFrontier,
                        ] = $nanoService->findNewOwner($targetAddress, $frontier, $targetPrice, $oldOwner);

                        $balanceRai = $nanoService->raiFromRaw($balanceTmp['balance']);
                        $balanceInt = intval($balanceRai);

                        $restBalanceInt = $newOwnerAmount + $refundsAmount;
                        $diffBalanceInt = $balanceInt - $restBalanceInt;
                        // $restBalanceInt -= $targetPrice;
                        // $restBalanceInt += intval($currentPrice);
                        // $restBalanceInt -= intval($currentPrice); // @todo

                        // $this->io->text(sprintf(' -> new offset: %d', $newOffset));
                        $this->io->text(sprintf(' -> new owner: %s', $newOwner ? $newOwner['account'] : 'N/A'));
                        $this->io->text(sprintf(' -> refunds: %d', count($refunds)));

                        // $this->io->text(sprintf(' -> balance raw: %s', $balanceTmp['balance']));
                        // $this->io->text(sprintf(' -> balance rai: %s', $balanceRai));
                        $this->io->text(sprintf(' -> balance int: %d', $balanceInt));
                        $this->io->text(sprintf(' ->        rest: %d', $restBalanceInt));
                        $this->io->text(sprintf(' ->        diff: %d', $diffBalanceInt));

                        $this->io->text(sprintf(' -> pending raw: %s', $balanceTmp['pending']));

                        // $entity->setHistoryOffset($newOffset);
                        $entity->setFrontier($newFrontier);

                        if (null !== $newOwner) {

                            if (null === $oldOwner) {
                            } else {
                                if ($balanceInt >= $targetPrice) {
                                    // Send Target Price back to Owner.
                                    $this->io->text(sprintf(' -> winner: %s %s', $oldOwner, $targetPriceRaw));
                                    $blockId = $nanoService->send($targetAddress, $oldOwner, $targetPriceRaw);
                                    $this->io->text(sprintf(' -> winner block: %s', $blockId));

                                    // Generate new Target Address.
                                    $newTargetAddress = $nanoService->createNewAccount();
                                    $entity->setTargetAddress($newTargetAddress);
                                    $entity->setFrontier(null);
                                }
                            }

                            // Set new Owner.
                            $entity->setOwnerAddress($newOwner['account']);
                            $entity->setOwnedSince(Carbon::now('UTC'));

                            // Set new Target Time.
                            $newTargetTime = Carbon::now('UTC');
                            $newTargetTime->addSeconds($ttl);
                            $entity->setTargetTime($newTargetTime);

                            // Set new Target Price.
                            $newTargetPrice = $entity->getTargetPrice() * 2;
                            $entity->setTargetPrice($newTargetPrice);

                            // Set Target Price as new Current Price.
                            $entity->setCurrentPrice($targetPrice);
                        }

                        // Calculate refund amount.
                        $refundAmountTotal = 0;
                        foreach ($refunds as $refund) {
                            $refundAmountRaw = $refund['amount'];
                            $refundAmountRai = $nanoService->raiFromRaw($refundAmountRaw);
                            $refundAmountInt = intval($refundAmountRai);

                            $this->io->text(sprintf(' -> collect refund: %d', $refundAmountInt));

                            $refundAmountTotal += $refundAmountInt;
                        }
                        $this->io->text(sprintf(' -> total refund: %d', $refundAmountTotal));

                        // Refund only if rest balance is equal to refund amount.
                        if ($refundsAmount > 0 && 0 === $diffBalanceInt) {
                            // Execute Refund
                            foreach ($refunds as $refund) {
                                $sender = $refund['account'];
                                $refundAmountRaw = $refund['amount'];
                                $refundAmountRai = $nanoService->raiFromRaw($refundAmountRaw);
                                $refundAmountInt = intval($refundAmountRai);

                                $blockId = $nanoService->send($targetAddress, $sender, $refundAmountRaw);

                                $this->io->text(sprintf(' -> refund: %s %s %s %d', $targetAddress, $sender, $blockId, $refundAmountInt));
                            }
                        } else {
                            if (count($refunds) > 0) {
                                $this->io->text(sprintf(' -> refund not possible: "%d" !== "%d"', $restBalanceInt, $refundAmountTotal));
                            }
                        }
                    } else {
                        $this->io->text(sprintf(' -> pending raw: %s', $balanceTmp['pending']));
                        $this->io->text(sprintf(' -> skip while pending'));

                        $entity->setHasPending(true);
                    }
                } else {
                    $this->io->text(sprintf(' -> can not be claimed'));
                }

                $renderEntities[] = $entity;
            } catch (\RuntimeException $e) {
                $msg = sprintf('%s (%d)', $e->getMessage(), $e->getCode());
                $this->io->error($msg);
                $entity->setErrorMessage($msg);
            }
        }

        // $server = new Server($rpcHost, $rpcPort);
        // $server->walletAccountsBalances($walletId);
        // $balances = $server->run();

        // Put the Entities back to the service.
        $dbService->setEntities($entities);

        // We don't know which entities has been changed, so force save.
        $dbService->saveDb(true);

        $renderService = new RenderService($webPath, $templatesPath, $cachePath, $this->io, $appName, $appVersion);
        $renderService->renderIndex([
            'entities' => $renderEntities,
        ]);
    }
}
