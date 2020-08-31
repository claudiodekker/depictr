<?php

namespace Depictr\Browsers;

use Depictr\Contracts\Browser;
use Depictr\OperatingSystem;
use Illuminate\Support\Arr;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class Chromium implements Browser
{
    /**
     * Path to the bin directory.
     *
     * @var string
     */
    protected $directory = __DIR__.'/../../bin/';

    /**
     * Relative paths to the Chromium executable.
     *
     * @var string[]
     */
    protected $entrypoints = [
        'mac' => 'Chromium.app/Contents/MacOS/Chromium',
        'linux' => 'chrome',
        'win64' => 'chrome.exe',
    ];

    /**
     * Renders a HTML page.
     *
     * @param  string  $url
     * @return string
     * @throws Throwable
     */
    public function render(string $url): string
    {
        $executable = $this->executablePath(OperatingSystem::id());
        if (! is_executable($executable)) {
            chmod($executable, 0755);
        }

        $process = new Process(
            [$executable, '--headless', '--disable-gpu', '--dump-dom', $url],
            dirname(__DIR__, 2)
        );
        $process->start();

        while ($process->isRunning()) {
            // Waiting for Chromium to finish..
        }

        if ($process->getExitCode() !== 0) {
            throw new RuntimeException($process->getExitCodeText(), $process->getExitCode());
        }

        return $process->getOutput();
    }

    /**
     * Get the platform-specific path to the Chromium runtime.
     *
     * @param  string  $os
     * @return string
     */
    protected function executablePath(string $os):string
    {
        if (! Arr::has($this->entrypoints, $os)) {
            throw new RuntimeException("Platform [$os] is not supported.");
        }

        return realpath(sprintf('%s/chrome-%s/%s', $this->directory, $os, $this->entrypoints[$os]));
    }
}
