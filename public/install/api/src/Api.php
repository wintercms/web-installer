<?php

namespace Winter\Installer;

use ZipArchive;
use ReflectionMethod;
use DirectoryIterator;
use BennoThommo\Packager\Composer;
use Illuminate\Database\Capsule\Manager as Capsule;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Console\Output\BufferedOutput;

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

    // Winter CMS API URL
    const API_URL = 'https://api.wintercms.com/marketplace';

    // Winter CMS codebase archive
    const WINTER_ARCHIVE = 'https://github.com/wintercms/winter/archive/refs/heads/1.1.zip';

    // Archive subfolder
    const ARCHIVE_SUBFOLDER = 'winter-1.1/';

    /** @var Logger */
    protected $logger;

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

        $this->initialiseLogging();

        $this->setExceptionHandler();
        
        $this->parseRequest();

        $this->log->info('Installer API request received', [
            'method' => $this->method,
            'endpoint' => $this->endpoint,
        ]);
        
        $method = $this->getRequestedMethod();
        if (is_null($method)) {
            $this->error('Invalid Installer API endpoint requested', 404);
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
        $this->log->notice('Trying Winter CMS API');
        $response = $this->apiRequest('GET', 'ping');
        $this->log->notice('Response received from Winter CMS API', ['response' => $response]);

        if ($response !== 'pong') {
            $this->error('Winter CMS API is unavailable', 500);
        }

        $this->log->notice('Winter CMS API connection successful.');
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

        $this->log->notice('Compared PHP version', [
            'installed' => PHP_VERSION,
            'needed' => self::MIN_PHP_VERSION
        ]);

        if (!$hasVersion) {
            $this->error('PHP version requirement not met.');
        }

        $this->log->notice('PHP version requirement met.');
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
        $this->log->notice('Checking PHP "curl" extension');
        if (!function_exists('curl_init') || !defined('CURLOPT_FOLLOWLOCATION')) {
            $this->data['extension'] = 'curl';
            $this->error('Missing extension');
        }

        $this->log->notice('Checking PHP "json" extension');
        if (!function_exists('json_decode')) {
            $this->data['extension'] = 'json';
            $this->error('Missing extension');
        }

        $this->log->notice('Checking PHP "pdo" extension');
        if (!defined('PDO::ATTR_DRIVER_NAME')) {
            $this->data['extension'] = 'pdo';
            $this->error('Missing extension');
        }

        $this->log->notice('Checking PHP "zip" extension');
        if (!class_exists('ZipArchive')) {
            $this->data['extension'] = 'zip';
            $this->error('Missing extension');
        }

        $extensions = ['mbstring', 'fileinfo', 'openssl', 'gd', 'filter', 'hash'];
        foreach ($extensions as $ext) {
            $this->log->notice('Checking PHP "' . $ext . '" extension');

            if (!extension_loaded($ext)) {
                $this->data['extension'] = $ext;
                $this->error('Missing extension');
            }
        }

        $this->log->notice('Required PHP extensions are installed.');
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
        set_time_limit(60);

        $dbConfig = $this->data['site']['database'];

        // Create a temporary SQLite database if necessary
        if ($dbConfig['type'] === 'sqlite') {
            $this->log->notice('Creating temporary SQLite DB', ['path' => $this->rootDir('.temp.sqlite')]);

            try {
                if (!is_file($this->rootDir('.temp.sqlite'))) {
                    touch($this->rootDir('.temp.sqlite'));
                }
            } catch (\Throwable $e) {
                $this->data['exception'] = $e->getMessage();
                $this->error('Unable to create a temporary SQLite database.');
            }
        }

        try {
            $this->log->notice('Check database connection');
            $capsule = $this->createCapsule($dbConfig);
            $connection = $capsule->getConnection();

            $tables = $connection->getDoctrineSchemaManager()->listTableNames();
            $this->log->notice('Found ' . count($tables) . ' table(s)', ['tables' => implode(', ', $tables)]);
        } catch (\Throwable $e) {
            $this->data['exception'] = $e->getMessage();
            $this->error('Database could not be connected to.');
        }

        if (count($tables)) {
            $this->data['dbNotEmpty'] = true;
            $this->error('Database is not empty.');
        }

        $this->log->notice('Database connection established and verified empty');
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
        $this->log->notice('Current working directory is writable.');
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
        set_time_limit(360);

        if (!is_dir($this->workDir())) {
            if (!@mkdir($this->workDir(), 0755, true)) {
                $this->error('Unable to create a work directory for installation');
            }
        }

        $winterZip = $this->workDir('winter.zip');

        if (!file_exists($winterZip)) {
            $this->log->notice('Try downloading Winter CMS archive');

            try {
                $fp = fopen($winterZip, 'w');
                if (!$fp) {
                    $this->log->error('Winter ZIP file unwritable', ['path' => $winterZip]);
                    $this->error('Unable to write the Winter installation file');
                }
                $curl = curl_init();

                // Set default params
                $params['client'] = 'winter-installer';

                curl_setopt_array($curl, [
                    CURLOPT_URL => self::WINTER_ARCHIVE,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 300,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_MAXREDIRS => 5,
                    CURLOPT_FILE => $fp
                ]);

                $this->log->notice('Downloading Winter ZIP via cURL', ['url' => self::WINTER_ARCHIVE]);
                curl_exec($curl);
                $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                if ($responseCode < 200 || $responseCode > 299) {
                    throw new \Exception('Invalid HTTP code received - got ' . $responseCode);
                }

                curl_close($curl);
            } catch (\Throwable $e) {
                if (isset($fp)) {
                    fclose($fp);
                }
                if (isset($curl) && is_resource($curl)) {
                    curl_close($curl);
                }
                $this->error('Unable to download Winter CMS. ' . $e->getMessage());
            }

            $this->log->notice('Winter CMS ZIP file downloaded', ['path' => $winterZip]);
        } else {
            $this->log->notice('Winter CMS ZIP file already downloaded', ['path' => $winterZip]);
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
        set_time_limit(120);

        $winterZip = $this->workDir('winter.zip');

        if (!file_exists($winterZip)) {
            $this->error('Winter CMS Zip file not found.');
            return;
        }

        try {
            $this->log->notice('Begin extracting Winter CMS archive');
            $zip = new ZipArchive();
            $zip->open($winterZip);
        } catch (\Throwable $e) {
            $this->error('Unable to extract Winter CMS. ' . $e->getMessage());
        }

        $zip->extractTo($this->workDir());

        if (!empty(self::ARCHIVE_SUBFOLDER)) {
            $this->log->notice('Move subfoldered files into position', ['subfolder' => self::ARCHIVE_SUBFOLDER]);

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
        if (!empty(self::ARCHIVE_SUBFOLDER)) {
            $this->log->notice('Remove ZIP subfolder', ['subfolder' => self::ARCHIVE_SUBFOLDER]);
            rmdir($this->workDir(self::ARCHIVE_SUBFOLDER));
        }

        // Make artisan command-line tool executable
        $this->log->notice('Make artisan command-line tool executable', ['path' => $this->workDir('artisan')]);
        chmod($this->workDir('artisan'), 0755);

        // If using SQLite, move temp SQLite DB into position
        if ($this->data['site']['database']['type'] === 'sqlite' && is_file($this->rootDir('.temp.sqlite'))) {
            $this->log->notice('Move temp SQLite DB into position', [
                'from' => $this->rootDir('.temp.sqlite'),
                'to' => $this->workDir('storage/database.sqlite')
            ]);
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
        set_time_limit(360);

        try {
            $this->log->notice('Create Composer instance');
            $composer = new Composer();
            $this->log->notice('Set memory limit to 1.5GB');
            $composer->setMemoryLimit(1536);
            $this->log->notice('Set work directory for Composer', ['path' => $this->workDir()]);
            $composer->setWorkDir($this->workDir());

            $tmpHomeDir = $this->workDir('.composer');

            if (!is_dir($tmpHomeDir)) {
                $this->log->notice('Create home/cache directory for Composer', ['path' => $tmpHomeDir]);
                mkdir($tmpHomeDir, 0755);
            }
            $this->log->notice('Set home/cache directory for Composer', ['path' => $tmpHomeDir]);
            $composer->setHomeDir($tmpHomeDir);

            $this->log->notice('Run Composer "update" command - generate only a lockfile');
            $update = $composer->update(true, true, false, 'dist', true);
        } catch (\Throwable $e) {
            $this->error('Unable to determine dependencies for Winter CMS. ' . $e->getMessage());
        }

        $this->log->notice('Locked Composer packages', [
            'numPackages' => $update->getLockInstalledCount(),
            'lockFile' => $this->workDir('composer.lock'),
        ]);
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
            $this->log->notice('Create Composer instance');
            $composer = new Composer();
            $this->log->notice('Set memory limit to 1.5GB');
            $composer->setMemoryLimit(1536);
            $this->log->notice('Set work directory for Composer', ['path' => $this->workDir()]);
            $composer->setWorkDir($this->workDir());

            $tmpHomeDir = $this->workDir('.composer');

            if (!is_dir($tmpHomeDir)) {
                $this->log->notice('Create home/cache directory for Composer', ['path' => $tmpHomeDir]);
                mkdir($tmpHomeDir, 0755);
            }
            $this->log->notice('Set home/cache directory for Composer', ['path' => $tmpHomeDir]);
            $composer->setHomeDir($tmpHomeDir);

            $this->log->notice('Run Composer "install" command - install from lockfile');
            $install = $composer->install(true, false, false, 'dist', true);
        } catch (\Throwable $e) {
            $this->error('Unable to determine dependencies for Winter CMS. ' . $e->getMessage());
        }

        $this->log->notice('Installed Composer packages', [
            'numPackages' => $install->getInstalledCount(),
        ]);
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
            $this->log->notice('Rewriting config', ['path' => $this->workDir('config/app.php')]);
            $this->rewriter->toFile($this->workDir('config/app.php'), [
                'name' => $this->data['site']['name'],
                'url' => $this->data['site']['url'],
                'key' => $this->generateKey(),
            ]);

            // config/cms.php
            $this->log->notice('Rewriting config', ['path' => $this->workDir('config/cms.php')]);
            $this->rewriter->toFile($this->workDir('config/cms.php'), [
                'backendUri' => '/' . $this->data['site']['backendUrl'],
            ]);

            // config/database.php
            $dbConfig = $this->data['site']['database'];

            $this->log->notice('Rewriting config', ['path' => $this->workDir('config/database.php')]);
            if ($dbConfig['type'] === 'sqlite') {
                $this->rewriter->toFile($this->workDir('config/database.php'), [
                    'default' => 'sqlite',
                ]);
            } else {
                $this->rewriter->toFile($this->workDir('config/database.php'), [
                    'default' => $dbConfig['type'],
                    'connections.' . $dbConfig['type'] . '.host' => $dbConfig['host'] ?? null,
                    'connections.' . $dbConfig['type'] . '.port' => $dbConfig['port'] ?? $this->getDefaultDbPort($dbConfig['type']),
                    'connections.' . $dbConfig['type'] . '.database' => $dbConfig['name'],
                    'connections.' . $dbConfig['type'] . '.username' => $dbConfig['user'] ?? '',
                    'connections.' . $dbConfig['type'] . '.password' => $dbConfig['pass'] ?? ''
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
            $this->log->notice('Flushing OPCache');
            opcache_reset();
        }
        if (function_exists('apc_clear_cache')) {
            $this->log->notice('Flushing APC Cache');
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
        set_time_limit(120);

        try {
            $this->bootFramework();

            $this->log->notice('Running artisan "config:clear" command');
            $output = new BufferedOutput();
            \Illuminate\Support\Facades\Artisan::call('config:clear', [], $output);
            $this->log->notice('Command finished.', ['output' => $output->fetch()]);

            $this->log->notice('Running database migrations');
            $output = new BufferedOutput();
            \Illuminate\Support\Facades\Artisan::call('winter:up', [], $output);
            $this->log->notice('Command finished.', ['output' => $output->fetch()]);
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

            $this->log->notice('Finding initial admin account');
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
            $this->log->notice('Changing admin account to details provided in installation');
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
        set_time_limit(120);

        // Remove install files
        $this->log->notice('Removing installation files');
        @unlink($this->workDir('winter.zip'));
        @unlink($this->rootDir('install.html'));
        @unlink($this->rootDir('install.zip'));

        // Remove install folders
        $this->log->notice('Removing temporary installation folders');
        $this->rimraf($this->rootDir('install'));
        $this->rimraf($this->workDir('.composer'));

        // Remove core development files
        $this->log->notice('Removing core development files');
        $this->rimraf($this->workDir('.github'));
        $this->rimraf($this->workDir('tests/fixtures'));
        $this->rimraf($this->workDir('tests/js'));
        $this->rimraf($this->workDir('tests/unit'));
        @unlink($this->workDir('.gitconfig'));
        @unlink($this->workDir('.gitattributes'));
        @unlink($this->workDir('.jshintrc'));
        @unlink($this->workDir('.babelrc'));
        @unlink($this->workDir('package.json'));
        @unlink($this->workDir('CHANGELOG.md'));
        @unlink($this->workDir('phpunit.xml'));
        @unlink($this->workDir('phpcs.xml'));

        // Move files from subdirectory into install folder
        $this->log->notice('Moving files from temporary work directory to final installation path', [
            'workDir' => $this->workDir(),
            'installDir' => $this->rootDir(),
        ]);
        $dir = new DirectoryIterator($this->workDir());

        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }

            $relativePath = str_replace($this->workDir(), '', $item->getPathname());

            rename($item->getPathname(), $this->rootDir($relativePath));
        }

        // Remove work directory
        $this->log->notice('Removing work directory');
        rmdir($this->workDir());

        $this->log->notice('Installation complete!');
    }

    /**
     * Initialise the logging for the API / install.
     *
     * @return void
     */
    protected function initialiseLogging()
    {
        // Set format
        $dateFormat = 'Y-m-d H:i:sP';
        $logFormat = "[%datetime%] %level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($logFormat, $dateFormat, false, true);

        $this->log = new Logger('install');

        $stream = new StreamHandler($this->rootDir('install.log'));
        $stream->setFormatter($formatter);

        $this->log->pushHandler($stream, Logger::INFO);
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
        $this->log->error($message, [
            'code' => $code,
            'exception' => $this->data['exception'] ?? null
        ]);
        $this->response(false);
    }

    /**
     * Gets the root directory of the install path.
     *
     * @return string
     */
    protected function rootDir(string $suffix = ''): string
    {
        $suffix = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $suffix), '/\\');

        return (str_replace(['/', '\\'], DIRECTORY_SEPARATOR, dirname(dirname(dirname(__DIR__)))))
            . (!empty($suffix) ? DIRECTORY_SEPARATOR . $suffix : '');
    }

    /**
     * Gets the working directory.
     *
     * @return string
     */
    protected function workDir(string $suffix = ''): string
    {
        $suffix = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $suffix), '/\\');

        return $this->rootDir('.wintercms' . (!empty($suffix) ? DIRECTORY_SEPARATOR . $suffix : ''));
    }

    /**
     * Gets the base URL for the current install.
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $baseUrl = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
            $baseUrl .= '://'. $_SERVER['HTTP_HOST'];
            $baseUrl .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
        } else {
            $baseUrl = 'http://localhost/';
        }

        return $baseUrl;
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
                    'charset' => ($dbConfig['type'] === 'mysql') ? 'utf8mb4' : 'utf8',
                    'collation' => ($dbConfig['type'] === 'mysql') ? 'utf8mb4_unicode_ci' : null,
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
        $this->log->notice('Booting Laravel framework');

        $autoloadFile = $this->workDir('bootstrap/autoload.php');
        if (!file_exists($autoloadFile)) {
            $this->error('Unable to load bootstrap file for framework from "' . $autoloadFile . '".');
            return;
        }

        $this->log->notice('Loading autoloader');
        require $autoloadFile;

        $appFile = $this->workDir('bootstrap/app.php');
        if (!file_exists($appFile)) {
            $this->error('Unable to load application initialization file for framework from "' . $appFile . '".');
            return;
        }

        $this->log->notice('Bootstrapping kernel');
        $app = require_once $appFile;
        $kernel = $app->make('Illuminate\Contracts\Console\Kernel');
        $kernel->bootstrap();
    }

    protected function apiRequest(string $method = 'GET', string $uri = '', array $params = [])
    {
        if (!in_array($method, ['GET', 'POST'])) {
            throw new \Exception('Invalid method for API request, must be GET or POST');
        }

        $curl = $this->prepareRequest($method, $uri, $params);
        $this->log->info('Winter API request', ['method' => $method, 'uri' => $uri]);
        $response = curl_exec($curl);

        // Normalise line endings
        $response = str_replace(["\r\n", "\n"], "\n", $response);

        // Parse response and code
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = explode('; ', curl_getinfo($curl, CURLINFO_CONTENT_TYPE))[0];
        $errored = false;

        if ($code < 200 || $code > 299) {
            $this->log->error('HTTP code returned indicates an error', ['code' => $code]);
            $this->log->debug('Response received from Winter API', ['response' => $response]);
            $errored = true;
        }

        // Parse JSON
        if ($contentType === 'application/json' || $contentType === 'text/json') {
            $response = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log->error('JSON data sent from server, but unable to parse', ['jsonError' => json_last_error_msg()]);
                $this->log->debug('Response received from Winter API', ['response' => $response]);
                $errored = true;
                $response = 'Unable to parse JSON response from server';
            }
        }

        curl_close($curl);

        if ($errored === true) {
            throw new \Exception('An error occurred trying to communicate with the Winter CMS API: ' . $response);
        }

        return $response;
    }

    /**
     * Prepares a cURL request to the Winter CMS API.
     *
     * @param string $method One of "GET", "POST"
     * @param string $uri
     * @param array $params
     * @return \CurlHandle|resource
     */
    protected function prepareRequest(string $method = 'GET', string $uri = '', array $params = [])
    {
        $curl = curl_init();

        // Set default params
        $params['protocol_version'] = '1.2';
        $params['client'] = 'winter-installer';
        $params['server'] = base64_encode(json_encode([
            'php' => PHP_VERSION,
            'url' => $this->getBaseUrl(),
        ]));

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYHOST => true,
            CURLOPT_SSL_VERIFYHOST => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
        ]);

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_URL, self::API_URL . '/' . $uri);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        } else {
            curl_setopt($curl, CURLOPT_URL, self::API_URL . '/' . $uri . '?' . http_build_query($params));
        }

        return $curl;
    }

    /**
     * Generates a cryptographically-secure key for encryption.
     *
     * @return string
     */
    protected function generateKey(): string
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
     * 
     * @return void
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
        $this->log->error($exception->getMessage(), ['exception' => $exception]);
        $this->error($exception->getMessage());
    }
}
