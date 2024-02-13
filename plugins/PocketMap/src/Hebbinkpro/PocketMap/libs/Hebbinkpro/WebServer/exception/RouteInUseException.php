<?php

namespace Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\exception;

class RouteInUseException extends WebServerException
{
    public function __construct(string $route)
    {
        parent::__construct("The route '$route' is already in use.");
    }
}