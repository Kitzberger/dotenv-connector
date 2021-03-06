<?php
namespace Helhum\DotEnvConnector;

/*
 * This file is part of the dotenv connector package.
 *
 * (c) Helmut Hummel <info@helhum.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Dotenv\Dotenv;

/**
 * Class DotEnvReader
 */
class DotEnvReader
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * Whether or not it is allowed to override existing environment vars
     *
     * @var bool
     */
    protected $allowOverloading = true;

    /**
     * The .env parser/loader
     *
     * @var Dotenv
     */
    protected $dotEnv;

    /**
     * DotEnvReader constructor.
     *
     * @param Dotenv $dotEnv The .env parser/loader
     * @param Cache $cache Cache handler
     * @param bool $allowOverloading Whether or not existing environment vars should be overridden by .env
     */
    public function __construct(Dotenv $dotEnv, Cache $cache, $allowOverloading = false)
    {
        $this->dotEnv = $dotEnv;
        $this->cache = $cache;
        $this->allowOverloading = $allowOverloading;
    }

    /**
     * Reads the environment file either by parsing it directly or from a cached file
     */
    public function read()
    {
        if ($this->cache->isEnabled()) {
            if ($this->cache->hasCache()) {
                $this->cache->loadCache();
            } else {
                $superGlobalEnvBackup = $_ENV;
                $this->parseEnvironmentVariables();
                $writtenEnvVars = array_diff_assoc($_ENV, $superGlobalEnvBackup);
                $this->cache->storeCache($this->getCachedCode($writtenEnvVars));
            }
        } else {
            $this->parseEnvironmentVariables();
        }
    }

    /**
     * Parses environment file
     */
    protected function parseEnvironmentVariables()
    {
        if ($this->allowOverloading) {
            $this->dotEnv->overload();
        } else {
            $this->dotEnv->load();
        }
    }

    /**
     * Creates the code for the cached environment file
     *
     * @param array $writtenEnvVars
     * @return string
     */
    protected function getCachedCode(array $writtenEnvVars)
    {
        $cacheFileContent = "<?php\n";
        foreach ($writtenEnvVars as $name => $value) {
            $cacheFileContent .= "putenv('$name=$value');\n";
            $cacheFileContent .= "\$_ENV['$name'] = '$value';\n";
            $cacheFileContent .= "\$_SERVER['$name'] = '$value';\n";
        }
        return $cacheFileContent;
    }
}
