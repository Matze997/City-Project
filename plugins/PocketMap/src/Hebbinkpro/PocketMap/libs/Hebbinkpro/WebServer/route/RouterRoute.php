<?php

namespace Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\route;

use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\http\HttpUrl;
use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\http\status\HttpStatusCodes;
use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\WebClient;

class RouterRoute extends Route
{
    private Router $router;

    public function __construct(string $path, Router $router)
    {
        $this->router = $router;

        parent::__construct(HttpMethod::ALL, $path . "/*", null);
    }

    public function handleRequest(WebClient $client, HttpRequest $req): void
    {
        $route = $this->router->getRouteByPath($req->getMethod(), HttpUrl::getSubPath($req->getURL()->getPath(), $this->getPath()));

        // no route is found
        if ($route === null) {
            $res = new HttpResponse($client);
            $res->setStatus(HttpStatusCodes::NOT_F0UND);
            $res->end();
            return;
        }

        $route->handleRequest($client, $req);
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
}