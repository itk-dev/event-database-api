<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\FormatException;

#[AsCommand(
    name: 'app:apikeys:add',
    description: 'Generate an API key for a user and add it to APP_API_KEYS in .env.local',
)]
class ApiKeysAddCommand extends Command
{
    private const string ENV_VAR = 'APP_API_KEYS';

    // Matches the whole assignment, including multi-line quoted values.
    private const string ENV_LINE_PATTERN = '/^APP_API_KEYS=(?:\'[^\']*\'|"(?:[^"\\\\]|\\\\.)*"|.*)$/m';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::OPTIONAL, 'Username to create an API key for (prompted for if omitted)');
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if ('' !== trim((string) $input->getArgument('username'))) {
            return;
        }

        $question = new Question('Username to create an API key for: ');
        $question->setValidator(function (?string $answer): string {
            if ('' === trim((string) $answer)) {
                throw new \RuntimeException('Username must not be empty.');
            }

            return trim((string) $answer);
        });

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $input->setArgument('username', $helper->ask($input, $output, $question));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = trim((string) $input->getArgument('username'));
        $envFile = $this->projectDir.'/.env.local';

        if ('' === $username) {
            $io->error('Username must not be empty.');

            return Command::FAILURE;
        }

        $original = file_exists($envFile) ? (string) file_get_contents($envFile) : '';

        try {
            $apikeys = $this->parseApiKeys($original);
        } catch (FormatException|\JsonException $e) {
            $io->error(sprintf('Could not read %s from %s: %s', self::ENV_VAR, $envFile, $e->getMessage()));

            return Command::FAILURE;
        }

        if (in_array($username, array_column($apikeys, 'username'), true)) {
            $io->error(sprintf('User "%s" already has an API key.', $username));

            return Command::FAILURE;
        }

        $apikey = bin2hex(random_bytes(32));
        $updated = [...$apikeys, ['username' => $username, 'apikey' => $apikey]];

        // JSON_HEX_APOS keeps the value safe inside single quotes.
        $line = self::ENV_VAR."='".json_encode($updated, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS)."'";
        if (preg_match(self::ENV_LINE_PATTERN, $original)) {
            $contents = (string) preg_replace_callback(self::ENV_LINE_PATTERN, fn () => $line, $original, 1);
        } else {
            $contents = $original.('' === $original || str_ends_with($original, "\n") ? '' : "\n").$line."\n";
        }

        file_put_contents($envFile, $contents);

        // Never leave .env.local in a state where existing keys are lost.
        try {
            $written = $this->parseApiKeys($contents);
        } catch (FormatException|\JsonException) {
            $written = null;
        }

        if ($written !== $updated) {
            file_put_contents($envFile, $original);
            $io->error(sprintf('Failed to update %s, the file has been restored.', $envFile));

            return Command::FAILURE;
        }

        $io->success(sprintf('API key for "%s" added to %s', $username, $envFile));
        $io->writeln($apikey);

        $compiledFile = $this->projectDir.'/.env.local.php';
        if (file_exists($compiledFile)) {
            $io->warning(sprintf('%s exists and takes precedence over .env.local. Run "composer dump-env prod" for the new key to take effect.', $compiledFile));

            $compiled = (include $compiledFile)[self::ENV_VAR] ?? '[]';
            if (json_decode((string) $compiled, true) !== $apikeys) {
                $io->warning(sprintf('%s in %s differs from .env.local. Reconcile them before running "composer dump-env prod", or keys may be lost.', self::ENV_VAR, $compiledFile));
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<array{username: string, apikey: string}>
     *
     * @throws FormatException
     * @throws \JsonException
     */
    private function parseApiKeys(string $contents): array
    {
        $value = (new Dotenv())->parse($contents)[self::ENV_VAR] ?? '[]';

        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }
}
