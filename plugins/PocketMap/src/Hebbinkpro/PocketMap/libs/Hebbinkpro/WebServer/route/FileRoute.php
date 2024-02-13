<?php

namespace Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\route;

use Exception;
use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\exception\FileNotFoundException;
use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\http\HttpRequest;
use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\http\HttpResponse;
use Hebbinkpro\PocketMap\libs\Hebbinkpro\WebServer\libs\Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;

class FileRoute extends Route
{
    private string $file;

    /**
     * @throws PhpVersionNotSupportedException
     * @throws FileNotFoundException
     */
    public function __construct(string $path, string $file, ?string $default = null)
    {
        if (!file_exists($file) && $default === null) throw new FileNotFoundException($file);

        $this->file = $file;

        parent::__construct(HttpMethod::GET, $path,
            function (HttpRequest $req, HttpResponse $res, mixed ...$params) {
                $res->sendFile($params[0], $params[1]);
                $res->end();
            }, $file, $default);


    }

    public function getFile(): string {
        return $this->file;
    }

}