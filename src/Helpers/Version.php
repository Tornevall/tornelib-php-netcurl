<?php

namespace TorneLIB\Helpers;

class Version
{
    public static function getRequiredVersion()
    {
        if (version_compare(PHP_VERSION, '5.5', '<=')) {
            throw new \Exception(
                sprintf(
                    'Your PHP version is way too old (%s)!! It is time to upgrade. ' .
                    'Try somthing above PHP 7.2 where PHP still has support. ' .
                    'If you still have no idea what this is, check out %s.',
                    PHP_VERSION,
                    'https://docs.tornevall.net/x/DoBPAw'
                ),
                500
            );
        }
    }
}
