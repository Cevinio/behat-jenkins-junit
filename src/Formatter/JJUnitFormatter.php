<?php

declare(strict_types=1);

namespace Cevinio\BehatJenkinsJUnit\Formatter;

use Behat\Config\Formatter\Formatter;

final class JJUnitFormatter extends Formatter
{
    public const NAME = 'jjunit';
    public const SETTING_PREFIX = 'prefix';
    public const SETTING_SUFFIX = 'suffix';
    public const SETTING_SUCCESS = 'success';

    /**
     * @param bool $success include successful scenario's in the output file
     */
    public function __construct(
        ?string $prefix = null,
        ?string $suffix = null,
        bool $success = true,
    ) {
        parent::__construct(name: self::NAME, settings: [
            self::SETTING_PREFIX => $prefix,
            self::SETTING_SUFFIX => $suffix,
            self::SETTING_SUCCESS => $success,
        ]);
    }

    public static function defaults(): array
    {
        return (new self())->toArray();
    }
}
