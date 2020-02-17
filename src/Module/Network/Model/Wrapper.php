<?php

namespace TorneLIB\Module\Network\Model;

interface Wrapper
{

    public function __construct();

    public function getConfig();

    public function request();
}