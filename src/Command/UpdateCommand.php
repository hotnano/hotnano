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
                    $this->io->text(sprintf(' -> can be claimed'));

                    $targetAddress = $entity->getTargetAddress();
                    // $frontier = $entity->getFrontier();
                    $offset = $entity->getHistoryOffset();
                    $targetPrice = $entity->getTargetPrice();
                    // $targetPriceRaw = $nanoService->raiToRaw($targetPrice);

                    [
                        'offset' => $newOffset,
                        'owner' => $newOwner,
                        'refunds' => $refunds,
                    ] = $nanoService->findNewOwner($targetAddress, $offset, $targetPrice);

                    $balanceTmp = $nanoService->getAccountBalance($entity->getTargetAddress());

                    $balanceRai = $nanoService->raiFromRaw($balanceTmp['balance']);
                    $balanceInt = intval($balanceRai);

                    $this->io->text(sprintf(' -> new offset: %d', $newOffset));
                    $this->io->text(sprintf(' -> new owner: %s', $newOwner ? $newOwner:'N/A'));
                    $this->io->text(sprintf(' -> refunds: %d', count($refunds)));

                    $this->io->text(sprintf(' -> balance raw: %s', $balanceTmp['balance']));
                    $this->io->text(sprintf(' -> balance rai: %s', $balanceRai));
                    $this->io->text(sprintf(' -> balance int: %d', $balanceRai));
                    $this->io->text(sprintf(' -> pending raw: %s', $balanceTmp['pending']));

                    $entity->setHistoryOffset($newOffset);

                    if (null !== $newOwner) {
                        $oldOwner = $entity->getOwnerAddress();
                        if (null === $oldOwner) {
                            //
                        } else {
                            //@todo
                        }
                    }

                    // Calculate refund amount.
                    $refundAmountTotal = 0;
                    foreach ($refunds as $refund) {
                        $refundAmountRaw = $refund['amount'];
                        $refundAmountRai = $nanoService->raiFromRaw($refundAmountRaw);
                        $refundAmountInt = intval($refundAmountRai);

                        $refundAmountTotal += $refundAmountInt;
                    }

                    // Refund only if rest balance is equal to refund amount.
                    if ($balanceInt === $refundAmountTotal) {
                        // Execute Refund
                        foreach ($refunds as $refund) {
                            $sender = $refund['account'];
                            $refundAmountRaw = $refund['amount'];
                            // $refundAmountRai=$nanoService->raiFromRaw($refundAmountRaw);
                            // $refundAmountInt=intval($refundAmountRai);

                            $blockId = $nanoService->send($targetAddress, $sender, $refundAmountRaw);

                            $this->io->text(sprintf(' -> refund: %s %s %s', $targetAddress, $sender, $blockId));
                        }
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
