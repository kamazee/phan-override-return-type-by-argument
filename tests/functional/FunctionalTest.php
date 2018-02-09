<?php

use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    private const PHAN = __DIR__ . '/../../vendor/bin/phan';
    private const PHAN_CONFIG = __DIR__ . '/.phan/config.php';

    /**
     * @dataProvider getCases
     *
     * @param string $path
     * @throws Exception
     */
    public function test(string $path): void
    {
        $expectedFile = $path . DIRECTORY_SEPARATOR . 'expected.txt';
        $command = sprintf(
            '%s --config-file %s',
            self::PHAN,
            self::PHAN_CONFIG
        );

        if (file_exists($expectedFile)) {
            $output = $this->exec($command, $path);
            $this->assertStringEqualsFile(
                $expectedFile,
                $output,
                'Error messages with plugin enabled must match expected'
            );
        }
    }

    public function getCases()
    {
        $casesDir = __DIR__ . DIRECTORY_SEPARATOR . 'cases';
        foreach (new DirectoryIterator($casesDir) as $entry) {
            if (!$entry->isDir()) {
                continue;
            }

            if ($entry->isDot()) {
                continue;
            }

            yield $entry->getFilename() => [$entry->getPathname()];
        }
    }

    /**
     * @param string $command
     * @param string $directory
     * @return string
     * @throws Exception
     */
    private function exec(string $command, string $directory): string
    {
        $pipes = [];

        $spec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($command, $spec, $pipes, $directory);

        if (!is_resource($process)) {
            $this->fail('phan process must run');
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);

        if (!empty($stderr)) {
            throw new Exception($stderr);
        }

        return (string) $stdout;
    }
}
