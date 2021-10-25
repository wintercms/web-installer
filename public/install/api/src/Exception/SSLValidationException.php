<?php

namespace Winter\Installer\Exception;

class SSLValidationException extends \Exception
{
    protected $message = 'SSL validation failed';
}
