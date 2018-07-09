<?php

namespace Shpasser\GaeSupportL5\Setup;


use Artisan;
use Illuminate\Console\Command;

class PrepareForDeployCommand extends Command
{
    const CACHED_CONFIG_PATH = './bootstrap/cache/config.php';

    protected $name = 'gae:prepare';

    protected $description = 'Prepares the app for deployment to GAE.';

    public function fire()
    {
        $this->info('Caching routes');
        $this->call('route:cache');

        $this->info('Caching config');
        $this->call('config:cache');

        $this->info('Optimizing');
        $this->call('optimize --force');

        $this->info('Preparing config for GAE');
        $this->fixCachedConfigPaths();

        $this->info('Done');
    }

    /**
     * When Laravel caches the config it immediately resolves any paths,
     * making some configuration values invalid for deployment.
     *
     * This method replaces the values generated by Laravel with ones appropriate for GAE.
     */
    private function fixCachedConfigPaths()
    {
        $contents = file_get_contents(self::CACHED_CONFIG_PATH);
        $appPath = app_path();
        $storagePath = storage_path();
        $basePath = base_path();
        $replaceFunction = 'str_replace';

        if ($this->isRunningOnWindows()) {
            $contents = $this->preProcessWindowsPaths($contents);
            $appPath = str_replace('\\', '/', $appPath);
            $storagePath = str_replace('\\', '/', $storagePath);
            $basePath = str_replace('\\', '/', $basePath);
            $replaceFunction = 'str_ireplace';
        }

        $strings = [
            "'${appPath}",
            "'${storagePath}",
            "'${basePath}"
        ];

        $replacements = [
            "app_path().'",
            "storage_path().'",
            "base_path().'"
        ];

        $modified = $replaceFunction($strings, $replacements, $contents);

        file_put_contents(self::CACHED_CONFIG_PATH, $modified);
    }

    protected function isRunningOnWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    protected function preProcessWindowsPaths($contents)
    {
        $expression = "/'([A-Za-z]:)?((\\\\|\/)[^\\/:*?\"\'<>|\r\n]*)*'/";

        $paths = array();
        preg_match_all($expression, $contents, $paths);

        $modified = $contents;
        foreach ($paths[0] as $path) {
            $normalizedPath = str_replace('\\\\', '/', $path);
            $modified = str_replace($path, $normalizedPath, $modified);
        }

        return $modified;
    }
}