<?php

namespace DC\SMS\Link;

class Response implements \DC\SMS\ResponseInterface {
    /**
     * @var bool
     */
    private $success;
    /**
     * @var string
     */
    private $id;

    /**
     * @param bool $success
     * @param string $id
     */
    function __construct($success, $id)
    {
        $this->success = $success;
        $this->id = $id;
    }

    /**
     * @return int
     */
    function getHTTPResponseCode()
    {
        return $this->success ? 200 : 500;
    }

    /**
     * @return string[]
     */
    function getHeaders()
    {
        return [];
    }

    /**
     * @return string
     */
    function getContent()
    {
        return $this->success ? "OK" : "FAIL";
    }
}