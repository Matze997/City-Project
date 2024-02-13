<?php

declare(strict_types=1);

foreach(scandir(__DIR__) as $folder) {
    if(!is_file(__DIR__."/".$folder."/manifest.json")) {
        continue;
    }
    $zipFile = __DIR__."/".$folder.".zip";
    $root = __DIR__."/".$folder;

    $zip = new ZipArchive();
    $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root."/"),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir() && !str_ends_with($name, ".zip")) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($root) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();

    echo "Built pack ".$folder.".\n";
}