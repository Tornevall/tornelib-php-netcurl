<?php

namespace TorneLIB\Helpers;

use Exception;

class Version
{
    /**
     * @param string $lowest
     * @param string $op
     * @throws Exception
     */
    public static function getRequiredVersion($lowest = '5.4', $op = '<')
    {
        if (version_compare(PHP_VERSION, $lowest, $op)) {
            throw new Exception(
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
