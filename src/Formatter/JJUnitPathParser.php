<?php

declare(strict_types=1);

namespace Cevinio\BehatJenkinsJUnit\Formatter;

class JJUnitPathParser
{
    private const EXTENSION_FEATURE = '.feature';

    public function __construct(
        private readonly ?string $pathsBase,
    ) {
    }

    /**
     * Strips the Behat paths.base from the path and remove the '.feature' extension
     */
    public function strip(string $path): string
    {
        if ($this->pathsBase !== null && true === str_starts_with($path, $this->pathsBase)) {
            $path = ltrim(substr($path, strlen($this->pathsBase)), '/\\');
        }

        if (true === str_ends_with($path, self::EXTENSION_FEATURE)) {
            $path = substr($path, 0, -strlen(self::EXTENSION_FEATURE));
        }

        return $path;
    }
}
