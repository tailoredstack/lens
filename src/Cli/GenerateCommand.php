<?php

declare(strict_types=1);

namespace Lens\Cli;

use Lens\Builder\OpenApiBuilder;
use Lens\Config\OpenApiConfig;
use Lens\Infer\Engine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * CLI command to generate OpenAPI specification.
 */
class GenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('generate')
            ->setAliases(['gen', 'g'])
            ->setDescription('Generate OpenAPI specification from Tempest controllers')
            ->addOption(
                'source',
                's',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Source directories to scan for controllers',
                ['src'],
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output file path',
                'openapi.json',
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format (json or yaml)',
                'json',
            )
            ->addOption(
                'title',
                't',
                InputOption::VALUE_REQUIRED,
                'API title',
                'Tempest API',
            )
            ->addOption(
                'api-version',
                null,
                InputOption::VALUE_REQUIRED,
                'API version',
                '1.0.0',
            )
            ->addOption(
                'base-path',
                'b',
                InputOption::VALUE_REQUIRED,
                'Base path for API',
                '/',
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Namespaces to exclude',
                [],
            )
            ->addOption(
                'include-internal',
                null,
                InputOption::VALUE_NONE,
                'Include internal methods (starting with __)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sources = $input->getOption('source');
        $outputPath = $input->getOption('output');
        $format = $input->getOption('format');
        $title = $input->getOption('title');
        $version = $input->getOption('api-version');
        $basePath = $input->getOption('base-path');
        $exclude = $input->getOption('exclude');
        $includeInternal = $input->getOption('include-internal');

        // Validate source directories
        foreach ($sources as $source) {
            if (! is_dir($source)) {
                $output->writeln("<error>Source directory not found: {$source}</error>");
                return Command::FAILURE;
            }
        }

        $output->writeln('<info>Scanning controllers...</info>');

        $engine = new Engine($sources);
        $engine->analyze();

        $operations = $engine->getOperations();
        $output->writeln(sprintf('  Found %d controller(s) with %d method(s)', count($operations), sum(array_map('count', $operations))));

        $output->writeln('<info>Building OpenAPI specification...</info>');

        $builder = new OpenApiBuilder();
        $config = new OpenApiConfig(
            sources: $sources,
            title: $title,
            version: $version,
            basePath: $basePath,
            exclude: $exclude,
            includeInternal: $includeInternal,
            outputFormat: $format,
            exportPath: $outputPath,
        );

        $spec = $builder->buildFromInfer($engine, $config);

        // Write output
        $output->writeln("<info>Writing {$format} to {$outputPath}...</info>");

        if ($format === 'yaml') {
            $content = Yaml::dump($spec, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        }

        if ($format === 'json') {
            $content = json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, $content);

        $output->writeln('<info>✓ OpenAPI specification generated successfully</info>');
        $output->writeln(sprintf('  Path: %s', realpath($outputPath)));
        $output->writeln(sprintf('  Operations: %d', count($spec['paths'])));

        return Command::SUCCESS;
    }
}

function sum(array $array): int
{
    return array_sum($array);
}
