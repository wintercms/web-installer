<?php

namespace Winter\Installer;

use Illuminate\Database\Capsule\Manager as Capsule;

use ReflectionMethod;
use ZipArchive;

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
        $capsule = new Capsule;
        $dbConfig = $this->data['site']['database'];

        try {
            switch ($dbConfig['type']) {
                case 'sqlite':
                    $capsule->addConnection([
                        'driver' => $dbConfig['type'],
                        'database' => $dbConfig['database'],
                        'prefix' => '',
                    ]);
                    break;
                default:
                    $capsule->addConnection([
                        'driver' => $dbConfig['type'],
                        'host' => $dbConfig['host'] ?? null,
                        'port' => $dbConfig['port'] ?? $this->getDefaultDbPort($dbConfig['type']),
                        'database' => $dbConfig['name'],
                        'username' => $dbConfig['username'] ?? '',
                        'password' => $dbConfig['password'] ?? '',
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                        'prefix' => '',
                    ]);
            }

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
        $winterZip = $this->rootDir('winter.zip');

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

        try {
            $zip = new ZipArchive();
            $zip->open($winterZip);
        } catch (\Throwable $e) {
            $this->error('Unable to extract Winter CMS. ' . $e->getMessage());
        }

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $zipFile = $zip->getNameIndex($i);
            $stat = $zip->statIndex($i);
            $isDir = ((substr($zipFile, -1) === '/' || substr($zipFile, -1) === '\\') && $stat['size'] === 0);
            $destPath = $this->rootDir(str_replace('winter-1.1/', '', $zipFile));

            if ($isDir) {
                mkdir($destPath, 0755, true);
            } else {
                copy('zip://' . $zipFile, $destPath);
                chmod($destPath, 0644);
            }
        }
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
}