<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ApiKeysAddCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;

class ApiKeysAddCommandTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/apikeys-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', (array) glob($this->dir.'/{,.}*.*', GLOB_BRACE));
        rmdir($this->dir);
    }

    public function testCreatesEnvLocalWhenMissing(): void
    {
        $tester = $this->runCommand('user_1');

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $keys = $this->keys();
        self::assertCount(1, $keys);
        self::assertSame('user_1', $keys[0]['username']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $keys[0]['apikey']);
        self::assertStringContainsString($keys[0]['apikey'], $tester->getDisplay());
    }

    public function testReplacesMultiLineValueAndKeepsOtherLines(): void
    {
        file_put_contents($this->dir.'/.env.local', <<<'ENV'
            INDEX_URL=http://elasticsearch:9200
            APP_API_KEYS='[
              {"username": "user_1", "apikey": "api_key_1"}
            ]'
            OTHER=value
            ENV);
        chmod($this->dir.'/.env.local', 0600);

        self::assertSame(Command::SUCCESS, $this->runCommand("o'brien")->getStatusCode());
        self::assertSame(0600, fileperms($this->dir.'/.env.local') & 0777);
        self::assertFileDoesNotExist($this->dir.'/.env.local.backup');
        self::assertFileDoesNotExist($this->dir.'/.env.local.temp');

        $env = (new Dotenv())->parse((string) file_get_contents($this->dir.'/.env.local'));
        self::assertSame('http://elasticsearch:9200', $env['INDEX_URL']);
        self::assertSame('value', $env['OTHER']);
        $keys = $this->keys();
        self::assertSame(['username' => 'user_1', 'apikey' => 'api_key_1'], $keys[0]);
        self::assertSame("o'brien", $keys[1]['username']);
    }

    public function testPromptsForUsernameWhenOmitted(): void
    {
        $command = new ApiKeysAddCommand($this->dir);
        $command->setHelperSet(new HelperSet([new QuestionHelper()]));
        $tester = new CommandTester($command);
        $tester->setInputs(['', 'user_1']);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Username must not be empty.', $tester->getDisplay());
        self::assertSame('user_1', $this->keys()[0]['username']);
    }

    public function testFailsWithoutUsernameWhenNonInteractive(): void
    {
        $tester = new CommandTester(new ApiKeysAddCommand($this->dir));
        $tester->execute([], ['interactive' => false]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertFileDoesNotExist($this->dir.'/.env.local');
    }

    public function testRejectsDuplicateUsername(): void
    {
        $original = "APP_API_KEYS='[{\"username\": \"user_1\", \"apikey\": \"api_key_1\"}]'\n";
        file_put_contents($this->dir.'/.env.local', $original);

        self::assertSame(Command::FAILURE, $this->runCommand('user_1')->getStatusCode());
        self::assertSame($original, file_get_contents($this->dir.'/.env.local'));
    }

    public function testLeavesFileUntouchedWhenRewriteBreaksIt(): void
    {
        // A trailing comment after a multi-line value defeats the line pattern.
        $original = "APP_API_KEYS='[\n{\"username\": \"user_1\", \"apikey\": \"api_key_1\"}\n]' # comment\n";
        file_put_contents($this->dir.'/.env.local', $original);

        self::assertSame(Command::FAILURE, $this->runCommand('user_2')->getStatusCode());
        self::assertSame($original, file_get_contents($this->dir.'/.env.local'));
        self::assertFileDoesNotExist($this->dir.'/.env.local.temp');
        self::assertFileDoesNotExist($this->dir.'/.env.local.backup');
    }

    public function testKeepsBackupWhenDeclined(): void
    {
        $original = "APP_API_KEYS='[]'\n";
        file_put_contents($this->dir.'/.env.local', $original);

        self::assertSame(Command::SUCCESS, $this->runCommand('user_1', ['no'])->getStatusCode());
        self::assertSame($original, file_get_contents($this->dir.'/.env.local.backup'));
        self::assertSame('user_1', $this->keys()[0]['username']);
    }

    public function testFailsWhenBackupExists(): void
    {
        $original = "APP_API_KEYS='[]'\n";
        file_put_contents($this->dir.'/.env.local', $original);
        file_put_contents($this->dir.'/.env.local.backup', 'old');

        self::assertSame(Command::FAILURE, $this->runCommand('user_1')->getStatusCode());
        self::assertSame($original, file_get_contents($this->dir.'/.env.local'));
        self::assertSame('old', file_get_contents($this->dir.'/.env.local.backup'));
    }

    public function testFailsWhenTempFileExists(): void
    {
        $original = "APP_API_KEYS='[]'\n";
        file_put_contents($this->dir.'/.env.local', $original);
        file_put_contents($this->dir.'/.env.local.temp', 'other run');

        self::assertSame(Command::FAILURE, $this->runCommand('user_1')->getStatusCode());
        self::assertSame($original, file_get_contents($this->dir.'/.env.local'));
        self::assertSame('other run', file_get_contents($this->dir.'/.env.local.temp'));
    }

    public function testFailsWhenEnvLocalIsSymlink(): void
    {
        $original = "APP_API_KEYS='[]'\n";
        file_put_contents($this->dir.'/target.env', $original);
        symlink($this->dir.'/target.env', $this->dir.'/.env.local');

        self::assertSame(Command::FAILURE, $this->runCommand('user_1')->getStatusCode());
        self::assertTrue(is_link($this->dir.'/.env.local'));
        self::assertSame($original, file_get_contents($this->dir.'/target.env'));
    }

    public function testAdvisesDumpEnvWhenCompiledFileExists(): void
    {
        file_put_contents($this->dir.'/.env.local.php', "<?php return ['APP_API_KEYS' => '[{\"username\":\"other\",\"apikey\":\"k\"}]'];\n");

        $display = (string) preg_replace('/\s+/', ' ', $this->runCommand('user_1')->getDisplay());

        self::assertStringContainsString('composer dump-env prod', $display);
        self::assertStringContainsString('differs from .env.local', $display);
    }

    /**
     * @param list<string> $answers runs non-interactively (default answers) when empty
     */
    private function runCommand(string $username, array $answers = []): CommandTester
    {
        $tester = new CommandTester(new ApiKeysAddCommand($this->dir));
        $tester->setInputs($answers);
        $tester->execute(['username' => $username], ['interactive' => [] !== $answers]);

        return $tester;
    }

    /**
     * @return list<array{username: string, apikey: string}>
     */
    private function keys(): array
    {
        $env = (new Dotenv())->parse((string) file_get_contents($this->dir.'/.env.local'));

        return json_decode($env['APP_API_KEYS'], true, 512, JSON_THROW_ON_ERROR);
    }
}
