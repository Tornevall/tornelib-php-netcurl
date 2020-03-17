<?php

namespace TorneLIB\Module\Network\Model;

use TorneLIB\Model\Type\dataType;

interface Wrapper
{

    public function __construct();

    public function getConfig();

    public function request($url, $data = [], $method = requestMethod::METHOD_GET, $dataType = dataType::NORMAL);
}