<?php

namespace App\Command\Utils;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CleanFilesCommand extends Command
{
    protected static $defaultName = 'app:clean-files';
    protected static $defaultDescription = 'Clean files';


    protected $directory;
    
    public function __construct(
        ParameterBagInterface $params
    ) {
        $this->directory = $params->get('kernel.project_dir');
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder = new Finder();
        $finder->files()->in($this->directory . '/var/export');

        $fs = new Filesystem();
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $absoluteFilePath = $file->getRealPath();
                $fs->remove($absoluteFilePath);
            }
        }
       
     
        return Command::SUCCESS;
    }
}
