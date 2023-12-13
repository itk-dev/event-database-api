<?php

namespace App\Command;

use App\Fixtures\FixtureLoader;
use App\Model\IndexNames;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fixtures:load',
    description: 'Add a short description for your command',
)]
class FixturesLoadCommand extends Command
{
    public function __construct(
        private readonly string $appEnv,
        private readonly FixtureLoader $loader
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'index',
            InputArgument::REQUIRED,
            sprintf('Index to dump (one of %s)', implode(', ', IndexNames::values())),
            null,
            function (CompletionInput $input): array {
                return array_filter(IndexNames::values(), fn ($item) => str_starts_with($item, $input->getCompletionValue()));
            }
        )
        ->addOption('url', null, InputOption::VALUE_OPTIONAL, 'Remote url to read data from', 'https://raw.githubusercontent.com/itk-dev/event-database-imports/develop/src/DataFixtures/indexes/[index].json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $index = $input->getArgument('index');
        $url = (string) $input->getOption('url');
        $url = strtr($url, ['[index]' => $index]);

        if ('dev' !== $this->appEnv) {
            $io->error('This command should only be executed in development environment.');

            return Command::FAILURE;
        }

        if (!in_array($index, IndexNames::values())) {
            $io->error(sprintf('Index %s does not exist', $index));

            return Command::FAILURE;
        }

        $this->loader->process($index, $url);

        return Command::SUCCESS;
    }
}
