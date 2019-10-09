<?php

namespace TorneLIB;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
}

use PHPUnit\Framework\TestCase;

/**
 * Class additionalTest Class for testing additional protocols (currently: imap)
 *
 * @package TorneLIB
 */
class additionalTest extends TestCase
{
    /** @var MODULE_CURL */
    private $CURL;

    /** @var string Change this */
    private $ACCOUNT = "imap://user:password@imap.server";

    function setUp()/* The :void return type declaration that should be here would cause a BC issue */
    {
        parent::setUp();

        $this->CURL = new MODULE_CURL();
    }

    /**
     * @test
     */
    public function testImap()
    {
        if (preg_match("/user\:password/", $this->ACCOUNT)) {
            static::markTestSkipped("Testing imap connectivity requires proper user login data");
            return;
        }
        try {
            $imapRequest = $this->CURL->doGet($this->ACCOUNT)->getHeader();
            static::assertTrue((bool)preg_match("/LIST(.*?)INBOX/", $imapRequest));
        } catch (\Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }
}
