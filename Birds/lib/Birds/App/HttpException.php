<?php
/**
 * Define a custom exception class
 */
namespace Birds\App;
class HttpException extends \Exception
{
    public function __construct($code, $message='')
    {
        parent::__construct($message, $code);
    }
}