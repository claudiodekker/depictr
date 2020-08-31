<?php

namespace Depictr\Browsers;

use Depictr\Contracts\Browser;
use Symfony\Component\Process\Process;
use Throwable;

class NodePuppeteer implements Browser
{
    /**
     * Renders a HTML page.
     *
     * @param  string  $url
     * @return string
     * @throws Throwable
     */
    public function render(string $url): string
    {
        $process = new Process(
            ['node', 'index.js', $url],
            dirname(__DIR__, 2)
        );

        $process->start();
        while ($process->isRunning()) {
            // waiting for process to finish
        }

        return $process->getOutput();
    }
}
