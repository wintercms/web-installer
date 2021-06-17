<?php

namespace Winter\Installer;

use ZipArchive;
use ReflectionMethod;
use DirectoryIterator;
use BennoThommo\Packager\Composer;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * API Class
 *
 * Handles the PHP side of the installer process.
 *
 * The API accepts the endpoint in a GET request via the `endpoint` query string, and via the `endpoint` post variable
 * in a POST request. This class will use a callback in the form of `<method><endpoint>` in camel-case to process the
 * endpoint (eg. for a POST call to the `createDatabase` endpoint, the API will run the `postCreateDatabase` method).
 *
 * Any data that is sent in the query strings for GET, and in the post data for POST, will be available within this
 * method inside the `$this->data` variable.
 *
 * @author Ben Thomson <git@alfreido.com>
 * @author Winter CMS
 * @since 1.0.0
 */
class Api
{
    // Minimum PHP version required for Winter CMS
    const MIN_PHP_VERSION = '7.2.9';

    // Winter CMS Ping API endpoint
    const API_PING_URL = 'https://api.wintercms.com/marketplace/ping';

    // Winter CMS codebase archive
    const WINTER_ARCHIVE = 'https://github.com/wintercms/winter/archive/refs/heads/1.1.zip';

    // Archive subfolder
    const ARCHIVE_SUBFOLDER = 'winter-1.1/';

    /** @var string Requested endpoint */
    protected $endpoint;

    /** @var string Request method of last API call */
    protected $method;

    /** @var array Request data of last API call */
    protected $data = [];

    /** @var int Response code */
    protected $responseCode = 200;

    /**
     * Main endpoint, processes an incoming request and generates a response.
     *
     * @return void
     */
    public function request()
    {
        // Disable display errors to prevent corruption of JSON responses
        ini_set('display_errors', 'Off');

        $this->setExceptionHandler();
        $this->parseRequest();

        $method = $this->getRequestedMethod();
        if (is_null($method)) {
            $this->error('Invalid endpoint requested', 404);
            return;
        }

        $this->{$method}();

        $this->response(true);
    }

    /**
     * GET /api.php?endpoint=checkApi
     *
     * Checks that the Winter CMS Marketplace API is available.
     *
     * @return void
     */
    public function getCheckApi()
    {
        $contents = @file_get_contents(self::API_PING_URL);

        if ($contents !== 'pong') {
            $this->error('Winter CMS API is unavailable', 500);
            return;
        }
    }

    /**
     * GET /api.php?endpoint=checkPhpVersion
     *
     * Checks that the currently-running version of PHP matches the minimum required for Winter CMS (1.1 branch)
     *
     * @return void
     */
    public function getCheckPhpVersion()
    {
        $hasVersion = version_compare(trim(strtolower(PHP_VERSION)), self::MIN_PHP_VERSION, '>=');

        $this->data = [
            'detected' => PHP_VERSION,
            'needed' => self::MIN_PHP_VERSION,
            'installPath' => $this->rootDir(),
        ];

        if (!$hasVersion) {
            $this->error('PHP version requirement not met.');
        }
    }

    /**
     * GET /api.php?endpoint=checkPhpExtensions
     *
     * Checks that necessary extensions required for running Winter CMS are installed and enabled.
     *
     * @return void
     */
    public function getCheckPhpExtensions()
    {
        if (!function_exists('curl_init') || !defined('CURLOPT_FOLLOWLOCATION')) {
            $this->data['extension'] = 'curl';
            $this->error('Missing extension');
        }
        if (!function_exists('json_decode')) {
            $this->data['extension'] = 'json';
            $this->error('Missing extension');
        }
        if (!defined('PDO::ATTR_DRIVER_NAME')) {
            $this->data['extension'] = 'pdo';
            $this->error('Missing extension');
        }
        if (!class_exists('ZipArchive')) {
            $this->data['extension'] = 'zip';
            $this->error('Missing extension');
        }

        $extensions = ['mbstring', 'fileinfo', 'openssl', 'gd', 'filter', 'hash'];
        foreach ($extensions as $ext) {
            if (!extension_loaded($ext)) {
                $this->data['extension'] = $ext;
                $this->error('Missing extension');
            }
        }
    }

    /**
     * POST /api.php[endpoint=checkDatabase]
     *
     * Checks that the given database credentials can be used to connect to a valid, empty database.
     *
     * @return void
     */
    public function postCheckDatabase()
    {
        $dbConfig = $this->data['site']['database'];

        // Create a temporary SQLite database if necessary
        try {
            if (!is_file($this->rootDir('.temp.sqlite'))) {
                touch($this->rootDir('.temp.sqlite'));
            }
        } catch (\Throwable $e) {
            $this->data['exception'] = $e->getMessage();
            $this->error('Unable to create a temporary SQLite database.');
        }

        try {
            $capsule = $this->createCapsule($dbConfig);
            $connection = $capsule->getConnection();
            $tables = $connection->getDoctrineSchemaManager()->listTableNames();
        } catch (\Throwable $e) {
            $this->data['exception'] = $e->getMessage();
            $this->error('Database could not be connected to.');
        }

        if (count($tables)) {
            $this->data['dbNotEmpty'] = true;
            $this->error('Database is not empty.');
        }
    }

    /**
     * GET /api.php?endpoint=checkWriteAccess
     *
     * Checks that the current work directory is writable.
     *
     * @return void
     */
    public function getCheckWriteAccess()
    {
        if (!is_writable($this->rootDir())) {
            $this->data['writable'] = false;
            $this->error('Current working directory is not writable.');
        }

        $this->data['writable'] = true;
    }

    /**
     * POST /api.php[endpoint=downloadWinter]
     *
     * Downloads the Winter CMS codebase from the 1.1 branch.
     *
     * @return void
     */
    public function postDownloadWinter()
    {
        if (!is_dir($this->workDir())) {
            if (!@mkdir($this->workDir(), 0755, true)) {
                $this->error('Unable to create a work directory for installation');
            }
        }

        $winterZip = $this->workDir('winter.zip');

        if (!file_exists($winterZip)) {
            try {
                file_put_contents(
                    $winterZip,
                    file_get_contents(self::WINTER_ARCHIVE)
                );
            } catch (\Throwable $e) {
                $this->error('Unable to download Winter CMS. ' . $e->getMessage());
            }
        }
    }

    /**
     * POST /api.php[endpoint=extractWinter]
     *
     * Extracts the downloaded ZIP file.
     *
     * @return void
     */
    public function postExtractWinter()
    {
        $winterZip = $this->workDir('winter.zip');

        if (!file_exists($winterZip)) {
            $this->error('Winter CMS Zip file not found.');
            return;
        }

        try {
            $zip = new ZipArchive();
            $zip->open($winterZip);
        } catch (\Throwable $e) {
            $this->error('Unable to extract Winter CMS. ' . $e->getMessage());
        }

        $zip->extractTo($this->workDir());

        if (!empty(self::ARCHIVE_SUBFOLDER)) {
            // Move files from subdirectory into install folder
            $dir = new DirectoryIterator($this->workDir(self::ARCHIVE_SUBFOLDER));

            foreach ($dir as $item) {
                if ($item->isDot()) {
                    continue;
                }

                $relativePath = str_replace($this->workDir(self::ARCHIVE_SUBFOLDER), '', $item->getPathname());

                rename($item->getPathname(), $this->workDir($relativePath));
            }
        }

        // Clean up
        $zip->close();
        rmdir($this->workDir(self::ARCHIVE_SUBFOLDER));

        // Make artisan command-line tool executable
        chmod($this->workDir('artisan'), 0755);

        // If using SQLite, move temp SQLite DB into position
        if ($this->data['site']['database']['type'] === 'sqlite' && is_file($this->rootDir('.temp.sqlite'))) {
            rename($this->rootDir('.temp.sqlite'), $this->workDir('storage/database.sqlite'));
        }
    }

    /**
     * POST /api.php[endpoint=lockDependencies]
     *
     * Locks the Composer dependencies for Winter CMS in composer.lock
     *
     * @return void
     */
    public function postLockDependencies()
    {
        set_time_limit(60);

        try {
            $composer = new Composer();
            $composer->setMemoryLimit(1536);
            $composer->setWorkDir($this->workDir());

            $tmpHomeDir = $this->tempDir('.composer');

            if (!is_dir($tmpHomeDir)) {
                mkdir($tmpHomeDir, 0755);
            }
            $composer->setHomeDir($tmpHomeDir);

            $update = $composer->update(true, true);
        } catch (\Throwable $e) {
            $this->error('Unable to determine dependencies for Winter CMS. ' . $e->getMessage());
        }

        $this->data['packagesInstalled'] = $update->getLockInstalledCount();
    }

    /**
     * POST /api.php[endpoint=installDependencies]
     *
     * Installs the locked depencies from the `lockDependencies` call.
     *
     * @return void
     */
    public function postInstallDependencies()
    {
        set_time_limit(180);

        try {
            $composer = new Composer();
            $composer->setMemoryLimit(1536);
            $composer->setWorkDir($this->workDir());

            $tmpHomeDir = $this->tempDir('.composer');

            if (!is_dir($tmpHomeDir)) {
                mkdir($tmpHomeDir, 0755);
            }
            $composer->setHomeDir($tmpHomeDir);

            $install = $composer->install(true);
        } catch (\Throwable $e) {
            $this->error('Unable to determine dependencies for Winter CMS. ' . $e->getMessage());
        }

        $this->data['packagesInstalled'] = $install->getInstalledCount();
    }

    /**
     * POST /api.php[endpoint=setupConfig]
     *
     * Rewrites the default configuration files with the values provided in the installer.
     *
     * @return void
     */
    public function postSetupConfig()
    {
        $this->bootFramework();

        try {
            $this->rewriter = new ConfigRewriter;

            // config/app.php
            $this->rewriter->toFile($this->workDir('config/app.php'), [
                'name' => $this->data['site']['name'],
                'url' => $this->data['site']['url'],
                'key' => $this->generateKey(),
            ]);

            // config/cms.php
            $this->rewriter->toFile($this->workDir('config/cms.php'), [
                'backendUri' => '/' . $this->data['site']['backendUrl'],
            ]);

            // config/database.php
            $dbConfig = $this->data['site']['database'];

            if ($dbConfig['type'] === 'sqlite') {
                $this->rewriter->toFile($this->workDir('config/database.php'), [
                    'default' => 'sqlite',
                ]);
            } else {
                $this->rewriter->toFile($this->workDir('config/database.php'), [
                    'default' => $dbConfig['type'],
                    'connections.' . $dbConfig['type'] . '.host' => $dbConfig['host'],
                    'connections.' . $dbConfig['type'] . '.port' => $dbConfig['port'],
                    'connections.' . $dbConfig['type'] . '.database' => $dbConfig['name'],
                    'connections.' . $dbConfig['type'] . '.username' => $dbConfig['user'],
                    'connections.' . $dbConfig['type'] . '.password' => $dbConfig['pass'],
                ]);
            }
        } catch (\Throwable $e) {
            $this->error('Unable to write config. ' . $e->getMessage());
        }

        // Force cache flush
        $opcacheEnabled = ini_get('opcache.enable');
        $opcachePath = trim(ini_get('opcache.restrict_api'));

        if (!empty($opcachePath) && !starts_with(__FILE__, $opcachePath)) {
            $opcacheEnabled = false;
        }

        if (function_exists('opcache_reset') && $opcacheEnabled) {
            opcache_reset();
        }
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
    }

    /**
     * POST /api.php[endpoint=runMigrations]
     *
     * Runs the migrations.
     *
     * @return void
     */
    public function postRunMigrations()
    {
        try {
            $this->bootFramework();

            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('october:up');
        } catch (\Throwable $e) {
            $this->error('Unable to run migrations. ' . $e->getMessage());
        }
    }

    /**
     * POST /api.php[endpoint=createAdmin]
     *
     * Creates (or updates) the administrator account.
     *
     * @return void
     */
    public function postCreateAdmin()
    {
        try {
            $this->bootFramework();

            $admin = \Backend\Models\User::find(1);
        } catch (\Throwable $e) {
            $this->error('Unable to find administrator account. ' . $e->getMessage());
        }

        $admin->email = $this->data['site']['admin']['email'];
        $admin->login = $this->data['site']['admin']['username'];
        $admin->password = $this->data['site']['admin']['password'];
        $admin->password_confirmation = $this->data['site']['admin']['password'];
        $admin->first_name = $this->data['site']['admin']['firstName'];
        $admin->last_name = $this->data['site']['admin']['lastName'];

        try {
            $admin->save();
        } catch (\Throwable $e) {
            $this->error('Unable to save administrator account. ' . $e->getMessage());
        }
    }

    /**
     * POST /api.php[endpoint=cleanUp]
     *
     * Cleans up and removes the installer and Composer cache, removes core development files, and then moves
     * Winter CMS files into position.
     *
     * @return void
     */
    public function postCleanUp()
    {
        // Remove install files
        @unlink($this->workDir('winter.zip'));
        @unlink($this->rootDir('install.htm'));

        // Remove install folders
        $this->rimraf($this->rootDir('install'));
        $this->rimraf($this->tempDir('.composer'));

        // Move files from subdirectory into install folder
        $dir = new DirectoryIterator($this->workDir());

        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }

            $relativePath = str_replace($this->workDir(), '', $item->getPathname());

            rename($item->getPathname(), $this->rootDir($relativePath));
        }

        // Remove work directory
        rmdir($this->workDir());
    }

    /**
     * Parses an incoming request for use in this API class.
     *
     * The method will be available in `$this->method`. Any request data will be available in `$this->data`.
     *
     * @return void
     */
    protected function parseRequest()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];

        if (!in_array($this->method, ['GET', 'POST'])) {
            $this->error('Invalid request method. Must be one of: GET, POST', 405);
            return;
        }

        if ($this->method === 'GET') {
            $this->data = $_GET;
        } else {
            $json = file_get_contents('php://input');

            if (empty($json)) {
                $this->error('No JSON input detected', 400);
                return;
            }

            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Malformed JSON request: ' . json_last_error_msg());
                return;
            }

            $this->data = $data;
        }

        $this->endpoint = $this->data['endpoint'] ?? null;
        unset($this->data['endpoint']);

        if (is_null($this->endpoint)) {
            $this->error('Missing requested endpoint', 400);
        }
    }

    /**
     * Determines if the correct API handler method is available.
     *
     * @return void
     */
    protected function getRequestedMethod()
    {
        $method = strtolower($this->method) . ucfirst($this->endpoint);

        if (!method_exists($this, $method)) {
            return null;
        }

        $reflection = new ReflectionMethod($this, $method);
        if (!$reflection->isPublic()) {
            return null;
        }

        return $method;
    }

    /**
     * Sets the HTTP response code.
     *
     * @param integer $code
     * @return void
     */
    protected function setResponseCode(int $code)
    {
        $this->responseCode = $code;
    }

    /**
     * Generates and echoes a JSON response to the browser.
     *
     * @param boolean $success Is this is a successful response?
     * @return void
     */
    protected function response(bool $success = true)
    {
        $response = [
            'success' => $success,
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'code' => $this->responseCode,
        ];

        if (!$success) {
            $response['error'] = $this->data['error'];
        }
        if (count($this->data)) {
            $response['data'] = $this->data;
        }

        // Set headers (including CORS)
        http_response_code($this->responseCode);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 3600');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

        echo json_encode($response);
        exit(0);
    }

    /**
     * Shortcut to generate a failure response for simple error message responses.
     *
     * @param string $message Message to return.
     * @param integer $code HTTP code to return.
     * @return void
     */
    protected function error(string $message, int $code = 500)
    {
        $this->setResponseCode($code);
        $this->data['error'] = $message;
        $this->response(false);
    }

    /**
     * Gets the root directory of the install path.
     *
     * @return string
     */
    protected function rootDir(string $suffix = '')
    {
        $suffix = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $suffix), '/\\');

        return dirname(dirname(dirname(__DIR__))) . (!empty($suffix) ? DIRECTORY_SEPARATOR . $suffix : '');
    }

    /**
     * Gets the working directory.
     *
     * @return string
     */
    protected function workDir(string $suffix = '')
    {
        $suffix = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $suffix), '/\\');

        return $this->rootDir('.wintercms' . (!empty($suffix) ? DIRECTORY_SEPARATOR . $suffix : ''));
    }

    /**
     * Gets the temp directory.
     *
     * @return string
     */
    protected function tempDir(string $suffix = '')
    {
        $suffix = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $suffix), '/\\');

        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . (!empty($suffix) ? DIRECTORY_SEPARATOR . $suffix : '');
    }

    /**
     * Determines the default port number for the given database type.
     *
     * @param string $type
     * @return integer
     */
    protected function getDefaultDbPort(string $type): int
    {
        switch ($type) {
            case 'mysql':
                return 3306;
            case 'pgsql':
                return 5432;
            case 'sqlsrv':
                return 1433;
            default:
                throw new \Exception('Invalid database type provided');
        }
    }

    /**
     * Creates a database capsule.
     *
     * @param array $dbConfig
     * @return Capsule
     */
    protected function createCapsule(array $dbConfig)
    {
        $capsule = new Capsule();

        switch ($dbConfig['type']) {
            case 'sqlite':
                $capsule->addConnection([
                    'driver' => $dbConfig['type'],
                    'database' => $this->rootDir('.temp.sqlite'),
                    'prefix' => '',
                ]);
                break;
            default:
                $capsule->addConnection([
                    'driver' => $dbConfig['type'],
                    'host' => $dbConfig['host'] ?? null,
                    'port' => $dbConfig['port'] ?? $this->getDefaultDbPort($dbConfig['type']),
                    'database' => $dbConfig['name'],
                    'username' => $dbConfig['user'] ?? '',
                    'password' => $dbConfig['pass'] ?? '',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                ]);
        }

        return $capsule;
    }

    /**
     * Boots the Laravel framework for use in some installation steps.
     *
     * @return void
     */
    protected function bootFramework()
    {
        $autoloadFile = $this->workDir('bootstrap/autoload.php');
        if (!file_exists($autoloadFile)) {
            $this->error('Unable to load bootstrap file for framework from "' . $autoloadFile . '".');
            return;
        }

        require $autoloadFile;

        $appFile = $this->workDir('bootstrap/app.php');
        if (!file_exists($appFile)) {
            $this->error('Unable to load application initialization file for framework from "' . $appFile . '".');
            return;
        }

        $app = require_once $appFile;
        $kernel = $app->make('Illuminate\Contracts\Console\Kernel');
        $kernel->bootstrap();
    }

    /**
     * Generates a cryptographically-secure key for encryption.
     *
     * @return void
     */
    protected function generateKey()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($chars) - 1;
        $key = '';

        for ($i = 0; $i < 32; ++$i) {
            $key .= substr($chars, random_int(0, $max), 1);
        }

        return $key;
    }

    /**
     * PHP-based "rm -rf" command.
     * 
     * Recursively removes a directory and all files and subdirectories within.
     */
    protected function rimraf(string $path)
    {
        $dir = new DirectoryIterator($path);

        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }

            if ($item->isDir()) {
                $this->rimraf($item->getPathname());
            }

            @unlink($item->getPathname());
        }

        @rmdir($path);
    }

    /**
     * Register a custom exception handler for the API.
     *
     * @return void
     */
    protected function setExceptionHandler()
    {
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Handle an uncaught PHP exception.
     *
     * @param \Exception $exception
     * @return void
     */
    public function handleException($exception)
    {
        $this->data['code'] = $exception->getCode();
        $this->data['file'] = $exception->getFile();
        $this->data['line'] = $exception->getLine();
        $this->error($exception->getMessage());
    }
}
