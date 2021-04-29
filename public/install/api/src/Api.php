<?php

namespace Winter\Installer;

use ReflectionMethod;

class Api
{
    // Minimum PHP version required for Winter CMS
    const MIN_PHP_VERSION = '7.2.9';

    // Winter CMS Ping API endpoint
    const API_PING_URL = 'https://api.wintercms.com/marketplace/ping';

    /** @var string Requested endpoint */
    protected $endpoint;

    /** @var string Request method of last API call */
    protected $method;

    /** @var array Request data of last API call */
    protected $data = [];

    /** @var int Response code */
    protected $responseCode = 200;

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

    public function getCheckApi()
    {
        $contents = @file_get_contents(self::API_PING_URL);

        if ($contents !== 'pong') {
            $this->error('Winter CMS API is unavailable', 500);
            return;
        }
    }

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

    public function getCheckPhpExtensions()
    {

    }

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

    protected function setResponseCode(int $code)
    {
        $this->responseCode = $code;
    }

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

    protected function error(string $message, int $code = 500)
    {
        $this->setResponseCode($code);
        $this->data['error'] = $message;
        $this->response(false);
    }
}