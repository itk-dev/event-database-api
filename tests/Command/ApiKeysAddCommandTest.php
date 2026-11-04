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

final class ApiKeysAddCommandTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/apikeys-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), (array) glob($this->dir.'/{,.}*.*', GLOB_BRACE));
        rmdir($this->dir);
    }

    public function testCreatesEnvLocalWhenMissing(): void
    {
        $tester = $this->runCommand('user_1');

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $keys = $this->keys();
        $this->assertCount(1, $keys);
        $this->assertSame('user_1', $keys[0]['username']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $keys[0]['apikey']);
        $this->assertStringContainsString($keys[0]['apikey'], $tester->getDisplay());
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
        // Same inode afterwards means .env.local was written in place, not renamed over,
        // which is required when it is a single-file bind mount (prod).
        $inode = fileinode($this->dir.'/.env.local');

        $this->assertSame(Command::SUCCESS, $this->runCommand("o'brien")->getStatusCode());
        $this->assertSame(0600, fileperms($this->dir.'/.env.local') & 0777);
        $this->assertSame($inode, fileinode($this->dir.'/.env.local'));
        $this->assertFileDoesNotExist($this->dir.'/.env.local.backup');
        $this->assertFileDoesNotExist($this->dir.'/.env.local.temp');

        $env = (new Dotenv())->parse((string) file_get_contents($this->dir.'/.env.local'));
        $this->assertSame('http://elasticsearch:9200', $env['INDEX_URL']);
        $this->assertSame('value', $env['OTHER']);
        $keys = $this->keys();
        $this->assertSame(['username' => 'user_1', 'apikey' => 'api_key_1'], $keys[0]);
        $this->assertSame("o'brien", $keys[1]['username']);
    }

    public function testPromptsForUsernameWhenOmitted(): void
    {
        $command = new ApiKeysAddCommand($this->dir);
        $command->setHelperSet(new HelperSet([new QuestionHelper()]));
        $tester = new CommandTester($command);
        $tester->setInputs(['', 'user_1']);
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Username must not be empty.', $tester->getDisplay());
        $this->assertSame('user_1', $this->keys()[0]['username']);
    }

    public function testFailsWithoutUsernameWhenNonInteractive(): void
    {
        $tester = new CommandTester(new ApiKeysAddCommand($this->dir));
        $tester->execute([], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertFileDoesNotExist($this->dir.'/.env.local');
    }

    public function testRejectsDuplicateUsername(): void
    {
        $original = "APP_API_KEYS='[{\"username\": \"user_1\", \"apikey\": \"api_key_1\"}]'\n";
        file_put_contents($this->dir.'/.env.local', $original);

        $this->assertSame(Command::FAILURE, $this->runCommand('user_1')->getStatusCode());
        $this->assertSame($original, file_get_contents($this->dir.'/.env.local'));
    }

    public function testLeavesFileUntouchedWhenRewriteBreaksIt(): void
    {
        // A trailing comment after a multi-line value defeats the line pattern.
        $original = "APP_API_KEYS='[\n{\"username\": \"user_1\", \"apikey\": \"api_key_1\"}\n]' # comment\n";
        file_put_contents($this->dir.'/.env.local', $original);

        $this->assertSame(Command::FAILURE, $this->runCommand('user_2')->getStatusCode());
        $this->assertSame($original, file_get_contents($this->dir.'/.env.local'));
        $this->assertFileDoesNotExist($this->dir.'/.env.local.temp');
        $this->assertFileDoesNotExist($this->dir.'/.env.local.backup');
    }

    public function testKeepsBackupWhenDeclined(): void
    {
        $original = "APP_API_KEYS='[]'\n";
        file_put_contents($this->dir.'/.env.local', $original);

        $this->assertSame(Command::SUCCESS, $this->runCommand('user_1', ['no'])->getStatusCode());
        $this->assertSame($original, file_get_contents($this->dir.'/.env.local.backup'));
        $this->assertSame('user_1', $this->keys()[0]['username']);
    }

    public function testFailsWhenBackupExists(): void
    {
        $original = "APP_API_KEYS='[]'\n";
        file_put_contents($this->dir.'/.env.local', $original);
        file_put_contents($this->dir.'/.env.local.backup', 'old');

        $this->assertSame(Command::FAILURE, $this->runCommand('user_1')->getStatusCode());
        $this->assertSame($original, file_get_contents($this->dir.'/.env.local'));
        $this->assertSame('old', file_get_contents($this->dir.'/.env.local.backup'));
    }

    public function testFailsWhenTempFileExists(): void
    {
        $original = "APP_API_KEYS='[]'\n";
        file_put_contents($this->dir.'/.env.local', $original);
        file_put_contents($this->dir.'/.env.local.temp', 'other run');

        $this->assertSame(Command::FAILURE, $this->runCommand('user_1')->getStatusCode());
        $this->assertSame($original, file_get_contents($this->dir.'/.env.local'));
        $this->assertSame('other run', file_get_contents($this->dir.'/.env.local.temp'));
    }

    public function testFailsWhenEnvLocalIsSymlink(): void
    {
        $original = "APP_API_KEYS='[]'\n";
        file_put_contents($this->dir.'/target.env', $original);
        symlink($this->dir.'/target.env', $this->dir.'/.env.local');

        $this->assertSame(Command::FAILURE, $this->runCommand('user_1')->getStatusCode());
        $this->assertTrue(is_link($this->dir.'/.env.local'));
        $this->assertSame($original, file_get_contents($this->dir.'/target.env'));
    }

    public function testAdvisesDumpEnvWhenCompiledFileExists(): void
    {
        file_put_contents($this->dir.'/.env.local.php', "<?php return ['APP_API_KEYS' => '[{\"username\":\"other\",\"apikey\":\"k\"}]'];\n");

        $display = (string) preg_replace('/\s+/', ' ', $this->runCommand('user_1')->getDisplay());

        $this->assertStringContainsString('composer dump-env prod', $display);
        $this->assertStringContainsString('differs from .env.local', $display);
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

        return json_decode((string) $env['APP_API_KEYS'], true, 512, JSON_THROW_ON_ERROR);
    }
}
