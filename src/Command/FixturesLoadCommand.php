<?php

namespace App\Command;

use App\Fixtures\FixtureLoader;
use App\Model\IndexNames;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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
            sprintf('Index to populate with fixture data (one of %s)', implode(', ', IndexNames::values())),
            null,
            function (CompletionInput $input): array {
                return array_filter(IndexNames::values(), fn ($item) => str_starts_with($item, $input->getCompletionValue()));
            }
        )
        ->addOption('url', null, InputOption::VALUE_OPTIONAL, 'Remote url to read fixture data from', 'https://raw.githubusercontent.com/itk-dev/event-database-imports/develop/src/DataFixtures/indexes/[index].json');
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \JsonException
     * @throws TransportExceptionInterface
     * @throws ClientResponseException
     * @throws ServerExceptionInterface
     * @throws \HttpException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $indexName = $input->getArgument('index');
        $url = (string) $input->getOption('url');
        $url = strtr($url, ['[index]' => $indexName]);

        if ('dev' !== $this->appEnv) {
            $io->error('This command should only be executed in development environment.');

            return Command::FAILURE;
        }

        if (!in_array($indexName, IndexNames::values())) {
            $io->error(sprintf('Index %s does not exist', $indexName));

            return Command::FAILURE;
        }

        $this->loader->process($indexName, $url);

        return Command::SUCCESS;
    }
}
