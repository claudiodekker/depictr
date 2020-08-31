<?php

namespace Depictr\Console;

use Depictr\OperatingSystem;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use ZipArchive;

class DownloadChromiumSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'depictr:chromium {version?}
                    {--use-preferred=true : Use the preferred Chromium revision}
                    {--all : Install a Chromium Snapshot binary for every OS}
                    {--proxy= : The proxy to download the Chromium snapshot binary through (example: "tcp://127.0.0.1:9000")}
                    {--ssl-no-verify : Bypass SSL certificate verification when installing through a proxy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Chromium snapshot binary';

    /**
     * URL to the latest Chromium snapshot version.
     *
     * @var string
     */
    protected $versionUrl = 'https://storage.googleapis.com/chromium-browser-snapshots/%s/LAST_CHANGE';

    /**
     * URL to the ChromeDriver download.
     *
     * @var string
     */
    protected $downloadUrl = 'https://storage.googleapis.com/chromium-browser-snapshots/%s/%d/chrome-%s.zip';

    /**
     * Download slugs for the available operating systems.
     *
     * @var array
     */
    protected $slugs = [
        'linux' => 'Linux_x64',
        'mac' => 'Mac',
        'win' => 'Win_x64',
    ];

    /**
     * Path to the bin directory.
     *
     * @var string
     */
    protected $directory = __DIR__.'/../../bin/';

    /**
     * The preferred, known-to-be mostly working Chromium build revision.
     * @see https://github.com/puppeteer/puppeteer/blob/master/src/revisions.ts
     *
     * @var string
     */
    protected $preferredRevision = '782078';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(): void
    {
        $all = $this->option('all');

        $currentOS = OperatingSystem::id();

        foreach ($this->slugs as $os => $slug) {
            if ($all || ($os === $currentOS)) {
                $archive = $this->download($os, $slug);

                $this->extract($archive);
            }
        }

        $message = 'Chrome %s successfully installed.';

        $this->info(sprintf($message, $all ? 'binaries' : 'binary'));
    }

    /**
     * Get the desired ChromeDriver version.
     *
     * @param  string  $slug
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function version(string $slug)
    {
        $version = $this->argument('version');

        if ($version) {
            return (int) $version;
        }

        if ($this->option('use-preferred')) {
            return $this->preferredRevision;
        }

        return trim($this->getUrl(sprintf($this->versionUrl, $slug)));
    }

    /**
     * Download the ChromeDriver archive.
     *
     * @param  string  $os
     * @param  string  $slug
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function download(string $os, string $slug): string
    {
        $url = sprintf($this->downloadUrl, $slug, $this->version($slug), $os);

        $this->getUrl($url, ['sink' => $archive = $this->directory.'chromedriver.zip']);

        return $archive;
    }

    /**
     * Extract the ChromeDriver binary from the archive and delete the archive.
     * TODO: Replace \ZipArchive. It causes permission issue corruptions.
     *
     * @param  string  $archive
     * @return void
     */
    protected function extract(string $archive): void
    {
        $zip = new ZipArchive();

        $zip->open($archive);

        $zip->extractTo($this->directory);

        $zip->close();

        // TODO: Commented so we can manually extract it (to prove it works when done manually)
        // unlink($archive);
    }

    /**
     * Get the contents of a URL using the 'proxy' and 'ssl-no-verify' command options.
     *
     * @param  string  $url
     * @param  array  $options
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getUrl(string $url, $options = []): string
    {
        $defaults = [];

        if ($this->option('proxy')) {
            $defaults['proxy'] = $this->option('proxy');
        }

        if ($this->option('ssl-no-verify')) {
            $defaults['verify'] = false;
        }

        $response = (new Client())->request('GET', $url, array_merge($defaults, $options));
        if (isset($options['sink'])) {
            // Don't return the response when using 'sink'.
            // This prevents out-of-memory issues.
            return '';
        }

        return (string) $response->getBody();
    }
}
