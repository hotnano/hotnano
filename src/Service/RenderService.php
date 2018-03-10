<?php

namespace HotNano\Service;

use Symfony\Component\Console\Style\SymfonyStyle;
use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * This renders the HTML files for the frontend.
 */
class RenderService
{
    /**
     * @var string
     */
    private $webPath;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var array
     */
    private $twigData;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var string
     */
    private $appName;

    /**
     * @var string
     */
    private $appVersion;

    public function __construct(string $webPath, string $templatesPath, string $cachePath, SymfonyStyle $io, string $appName, string $appVersion)
    {
        $this->webPath = $webPath;
        $loader = new Twig_Loader_Filesystem($templatesPath);
        $this->twig = new Twig_Environment($loader, [
            'cache' => $cachePath,
            'strict_variables' => true,
            'auto_reload' => true,
        ]);
        $this->io = $io;
        $this->appName = $appName;
        $this->appVersion = $appVersion;

        $this->twigData = [
            'app_version' => $this->appVersion,
            'app_name' => $this->appName,
        ];
    }

    /**
     * @param array $data
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderIndex(array $data = [])
    {
        $twigData = array_merge($this->twigData, $data);
        $indexHtml = $this->twig->render('index.html.twig', $twigData);
        $indexPath = sprintf('%s/index.html', $this->webPath);
        $filePut = file_put_contents($indexPath, $indexHtml);
        if ($filePut) {
            $this->io->success('Index');
        } else {
            $this->io->error('Index failed');
        }
    }
}
