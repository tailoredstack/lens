<?php

declare(strict_types=1);

namespace Lens\Cli;

use Lens\LensInstaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to publish Lens configuration.
 */
class ConfigPublishCommand extends Command
{
    public function __construct(
        private readonly LensInstaller $installer = new LensInstaller(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('config:publish')
            ->setAliases(['publish:config', 'publish-config'])
            ->setDescription('Publish the Lens configuration file to your application');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Publishing Lens configuration...</info>');

        $this->installer->publishConfig();

        return Command::SUCCESS;
    }
}
