<?php

namespace TorneLIB;

interface NETCURL_DRIVERS_INTERFACE {

	public function __construct( $parameters = null );

	public function setDriverId( $driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET );

	public function setParameters( $parameters = array() );

}