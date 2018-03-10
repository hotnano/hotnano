<?php

namespace HotNano\Command;

use HotNano\Service\DatabaseService;
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

        $this->addOption('--output', '-o', InputOption::VALUE_OPTIONAL, 'Output path for HTML files.', $webDefaultPath);
        $this->addOption('--templates', '-t', InputOption::VALUE_OPTIONAL, 'Path to template directory.', $templatesDefaultPath);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getApplication();
        $appVersion = $app->getVersion();
        $appName = $app->getName();

        $this->io = new SymfonyStyle($input, $output);

        $webPath = $input->getOption('output');
        $templatesPath = $input->getOption('templates');

        $cachePath = realpath(sprintf('%s/../../tmp/twig_cache', __DIR__));

        $dbFilePath=realpath(sprintf('%s/db.yml', $webPath));
        $dbService=new DatabaseService($dbFilePath);
        $dbService->loadDb();
        $dbService->saveDb();

        //$renderService = new RenderService($webPath, $templatesPath, $cachePath, $this->io, $appName, $appVersion);
        //$renderService->renderIndex();
    }
}
