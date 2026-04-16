<?php

namespace Torq\PimcoreHelpersBundle\Service\Common;

use Exception;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

trait BundleAssetResolverTrait
{
    protected function getBundleAssetPaths(string $dir, ?string $extension = null)
    {
        if (!$this->isAbsolutePublicDirectory($dir)) {
            throw new Exception("dir: `$dir` is not an absolute public directory path that exists.");
        }

        $paths = [];
        $finder = (new Finder())->files()->in($dir)->name("*.$extension");
        foreach ($finder as $file) {
            $parts = explode('public/', $file->getRealPath());
            if (key_exists(1, $parts)) {
                $paths[] = $parts[1];
            }
        }
        return $paths;
    }

    private function isAbsolutePublicDirectory(string $dir)
    {
        $dir = realpath($dir);
        return $dir !== false && is_dir($dir) && str_contains($dir, 'public');
    }
}