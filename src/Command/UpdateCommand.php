<?php

namespace HotNano\Command;

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
            ->addOption('--rpc_host',null,InputOption::VALUE_OPTIONAL,'RPC Host to Nano Core client.','127.0.0.1')
            ->addOption('--rpc_port',null,InputOption::VALUE_OPTIONAL,'RPC Port to Nano Core client.',7076)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getApplication();
        $appVersion = $app->getVersion();
        $appName = $app->getName();
        $rpcHost=$input->getOption('rpc_host');
        $rpcPort=intval($input->getOption('rpc_port'));

        $this->io = new SymfonyStyle($input, $output);

        $webPath = $input->getOption('output');
        $templatesPath = $input->getOption('templates');

        $cachePath = realpath(sprintf('%s/../../tmp/twig_cache', __DIR__));

        $nanoService=new NanoService($rpcHost,$rpcPort);

        $dbFilePath = sprintf('%s/db.yml', $webPath);
        $dbService = new DatabaseService($dbFilePath);
        $dbService->loadDb();
        $entities = $dbService->getEntities();

        $dbService->setEntities($entities);

        // We don't know which entities has been changed, so force save.
        $dbService->saveDb(true);

        $renderService = new RenderService($webPath, $templatesPath, $cachePath, $this->io, $appName, $appVersion);
        $renderService->renderIndex([
            'entities' => $entities,
        ]);
    }
}
