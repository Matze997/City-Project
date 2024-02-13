<?php

namespace Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\exception;

class FileNotFoundException extends WebServerException
{
    public function __construct(string $file)
    {
        parent::__construct("No file found at: '$file'.");
    }
}