<?php

namespace TorneLIB;

if (!class_exists('MODULE_SSL', NETCURL_CLASS_EXISTS_AUTOLOAD) && !class_exists('TorneLIB\MODULE_SSL',
        NETCURL_CLASS_EXISTS_AUTOLOAD)) {
    if (!defined('NETCURL_SSL_RELEASE')) {
        define('NETCURL_SSL_RELEASE', '6.0.0');
    }
    if (!defined('NETCURL_SSL_MODIFY')) {
        define('NETCURL_SSL_MODIFY', '20180325');
    }
    if (!defined('NETCURL_SSL_CLIENTNAME')) {
        define('NETCURL_SSL_CLIENTNAME', 'MODULE_SSL');
    }

    /**
     * Class MODULE_SSL SSL Helper class
     *
     * @package TorneLIB
     * @version 6.0.0
     * @deprecated Replaced with PSR4 compliances in v6.1
     */
    class MODULE_SSL
    {
        /** @var array Default paths to the certificates we are looking for */
        private $sslPemLocations = ['/etc/ssl/certs'];
        /** @var array Files to look for in sslPemLocations */
        private $sslPemFiles = ['cacert.pem', 'ca-certificates.crt'];
        /** @var string Location of the SSL certificate bundle */
        private $sslRealCertLocation;
        /** @var bool Strict verification of the connection (sslVerify) */
        private $SSL_STRICT_VERIFICATION = true;
        /** @var null|bool Allow self signed certificates */
        private $SSL_STRICT_SELF_SIGNED = true;
        /** @var bool Allowing fallback/failover to unstict verification */
        private $SSL_STRICT_FAILOVER = false;

        /** @var MODULE_CURL $PARENT */
        private $PARENT;
        /** @var MODULE_NETWORK $NETWORK */
        private $NETWORK;

        /**
         * @var array Options.
         */
        private $sslopt = [];

        /**
         * MODULE_SSL constructor.
         *
         * @param MODULE_CURL $MODULE_CURL
         */
        public function __construct($MODULE_CURL = null)
        {
            if (is_object($MODULE_CURL)) {
                $this->PARENT = $MODULE_CURL;
            }
            $this->NETWORK = new MODULE_NETWORK();
        }

        /**
         * @return array
         * @since 6.0.0
         */
        public static function getCurlSslAvailable()
        {
            // Common ssl checkers (if they fail, there is a sslDriverError to recall

            $sslDriverError = [];
            $streamWrappers = @stream_get_wrappers();
            if (!is_array($streamWrappers)) {
                $streamWrappers = [];
            }
            if (!in_array('https', array_map("strtolower", $streamWrappers))) {
                $sslDriverError[] = "SSL Failure: HTTPS wrapper can not be found";
            }
            if (!extension_loaded('openssl')) {
                $sslDriverError[] = "SSL Failure: HTTPS extension can not be found";
            }

            if (function_exists('curl_version')) {
                $curlVersionRequest = curl_version();
                if (defined('CURL_VERSION_SSL')) {
                    if (isset($curlVersionRequest['features'])) {
                        $CURL_SSL_AVAILABLE = ($curlVersionRequest['features'] & CURL_VERSION_SSL ? true : false);
                        if (!$CURL_SSL_AVAILABLE) {
                            $sslDriverError[] = 'SSL Failure: Protocol "https" not supported or disabled in libcurl';
                        }
                    } else {
                        $sslDriverError[] = "SSL Failure: CurlVersionFeaturesList does not return any feature (this should not be happen)";
                    }
                }
            }

            return $sslDriverError;
        }

        /**
         * Returns true if no errors occured in the control
         *
         * @return bool
         * @deprecated Removed from 6.1
         */
        public static function hasSsl()
        {
            if (!count(self::getCurlSslAvailable())) {
                return true;
            }

            return false;
        }

        /**
         * Make sure that we are allowed to do things
         *
         * @param bool $checkSafeMode If true, we will also check if safe_mode is active
         * @param bool $mockSafeMode If true, NetCurl will pretend safe_mode is true (for testing)
         * @return bool If true, PHP is in secure mode and won't allow things like follow-redirects and setting up different paths for certificates, etc
         * @since 6.0.20
         * @deprecated Replaced with getSecureMode in 6.1
         */
        public function getIsSecure($checkSafeMode = true, $mockSafeMode = false)
        {
            $currentBaseDir = trim(ini_get('open_basedir'));
            if ($checkSafeMode) {
                if ($currentBaseDir == '' && !$this->getSafeMode($mockSafeMode)) {
                    return false;
                }

                return true;
            } else {
                if ($currentBaseDir == '') {
                    return false;
                }

                return true;
            }
        }

        /**
         * Get safe_mode status (mockable)
         *
         * @param bool $mockedSafeMode When active, this always returns true
         * @return bool
         * @deprecated Moved to external security library.
         */
        private function getSafeMode($mockedSafeMode = false)
        {
            if ($mockedSafeMode) {
                return true;
            }

            // There is no safe mode in PHP 5.4.0 and above
            if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
                return false;
            }

            return (filter_var(ini_get('safe_mode'), FILTER_VALIDATE_BOOLEAN));
        }

        /**
         * openssl_guess rewrite
         *
         * @param bool $forceChecking
         * @return string
         * @since 6.0.0
         * @deprecated Removed in 6.1 - context will be handed over to the developer.
         */
        public function getSslCertificateBundle($forceChecking = false)
        {
            // Assume that sysadmins can handle this, if open_basedir is set as things will fail if we proceed here
            if ($this->getIsSecure(false) && !$forceChecking) {
                return null;
            }

            foreach ($this->sslPemLocations as $filePath) {
                if (is_dir($filePath) && !in_array($filePath, $this->sslPemLocations)) {
                    $this->sslPemLocations[] = $filePath;
                }
            }

            // If PHP >= 5.6.0, the OpenSSL module has its own way getting certificate locations
            if (version_compare(PHP_VERSION, "5.6.0", ">=") && function_exists("openssl_get_cert_locations")) {
                $internalCheck = openssl_get_cert_locations();
                if (isset($internalCheck['default_cert_dir']) &&
                    is_dir($internalCheck['default_cert_dir']) &&
                    !empty($internalCheck['default_cert_file'])
                ) {
                    $certFile = basename($internalCheck['default_cert_file']);
                    if (!in_array($internalCheck['default_cert_dir'], $this->sslPemLocations)) {
                        $this->sslPemLocations[] = $internalCheck['default_cert_dir'];
                    }
                    if (!in_array($certFile, $this->sslPemFiles)) {
                        $this->sslPemFiles[] = $certFile;
                    }
                }
            }

            // get first match
            foreach ($this->sslPemLocations as $location) {
                foreach ($this->sslPemFiles as $file) {
                    $fullCertPath = $location . "/" . $file;
                    if (file_exists($fullCertPath) && empty($this->sslRealCertLocation)) {
                        $this->sslRealCertLocation = $fullCertPath;
                    }
                }
            }

            return $this->sslRealCertLocation;
        }

        /**
         * @param array $pemLocationData
         *
         * @return bool
         * @throws \Exception
         * @since 6.0.20
         * @deprecated Removed in 6.1 - context will be handed over to the developer.
         */
        public function setPemLocation($pemLocationData = [])
        {
            $failAdd = false;
            if (is_string($pemLocationData)) {
                $pemLocationData = [$pemLocationData];
            }
            if (is_array($pemLocationData) && is_array($pemLocationData)) {
                foreach ($pemLocationData as $pemDataRow) {
                    $pemDataRow = trim(preg_replace("/\/$/", '', $pemDataRow));
                    $pemFile = $pemDataRow;
                    $pemDir = dirname($pemDataRow);
                    if ($pemFile != $pemDir && is_file($pemFile)) {
                        $this->sslPemFiles[] = $pemFile;
                        $this->sslPemLocations[] = $pemDir;
                    } else {
                        $failAdd = true;
                    }
                }
            }
            if ($failAdd) {
                throw new \Exception(
                    NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: The format of pemLocationData is not properly set",
                    $this->NETWORK->getExceptionCode('NETCURL_PEMLOCATIONDATA_FORMAT_ERROR')
                );
            }

            return true;
        }

        /**
         * @return array
         * @deprecated Removed in 6.1 - context will be handed over to the developer.
         */
        public function getPemLocations()
        {
            return $this->sslPemLocations;
        }

        /**
         * Set the rules of how to verify SSL certificates
         *
         * @param bool $strictCertificateVerification
         * @param bool $prohibitSelfSigned This only covers streams
         * @since 6.0.0
         * @deprecated Input variables will change in 6.1
         */
        public function setStrictVerification($strictCertificateVerification = true, $prohibitSelfSigned = true)
        {
            $this->SSL_STRICT_VERIFICATION = $strictCertificateVerification;
            $this->SSL_STRICT_SELF_SIGNED = $prohibitSelfSigned;
        }

        /**
         * Returns the mode of strict verification set up. If true, netcurl will be very strict with all certificate verifications.
         *
         * @return bool
         * @since 6.0.0
         * @deprecated Replaced by getContext in 6.1
         */
        public function getStrictVerification()
        {
            return $this->SSL_STRICT_VERIFICATION;
        }

        /**
         * @return bool|null
         * @deprecated Removed from 6.1
         */
        public function getStrictSelfSignedVerification()
        {
            // If this is not set, assume we want the value hardened
            return $this->SSL_STRICT_SELF_SIGNED;
        }

        /**
         * Allow NetCurl to make failover (fallback) to unstrict SSL verification after a strict call has been made
         *
         * Replacement for allowSslUnverified setup
         *
         * @param bool $sslFailoverEnabled *
         * @since 6.0.0
         * @deprecated Removed from 6.1
         */
        public function setStrictFallback($sslFailoverEnabled = false)
        {
            $this->SSL_STRICT_FAILOVER = $sslFailoverEnabled;
        }

        /**
         * @return bool
         * @since 6.0.0
         * @deprecated Removed from 6.1
         */
        public function getStrictFallback()
        {
            return $this->SSL_STRICT_FAILOVER;
        }

        /**
         * Prepare context stream for SSL
         *
         * @return array
         * @since 6.0.0
         * @deprecated Rewritten in 6.1
         */
        public function getSslStreamContext()
        {
            $sslCaBundle = $this->getSslCertificateBundle();
            /** @var array $contextGenerateArray Default stream context array, does not contain a ca bundle */
            $contextGenerateArray = [
                'verify_peer' => $this->SSL_STRICT_VERIFICATION,
                'verify_peer_name' => $this->SSL_STRICT_VERIFICATION,
                'verify_host' => $this->SSL_STRICT_VERIFICATION,
                'allow_self_signed' => $this->SSL_STRICT_SELF_SIGNED,
            ];
            // During tests, this bundle might disappear depending on what happens in tests. If something fails, that might render
            // strange false alarms, so we'll just add the file into the array if it's set. Many tests in a row can strangely have this effect.
            if (!empty($sslCaBundle)) {
                $contextGenerateArray['cafile'] = $sslCaBundle;
            }

            return $contextGenerateArray;
        }

        /**
         * Put the context into stream for SSL
         *
         * @param array $optionsArray
         * @param array $addonContextData
         *
         * @return array
         * @since 6.0.0
         * @deprecated Removed from 6.1
         */
        public function getSslStream($optionsArray = [], $addonContextData = [])
        {
            $streamContextOptions = [];
            if (is_object($this->PARENT)) {
                $this->PARENT->setUserAgent(NETCURL_SSL_CLIENTNAME . "-" . NETCURL_SSL_RELEASE);
                $streamContextOptions['http'] = [
                    "user_agent" => $this->PARENT->getUserAgent(),
                ];
            }
            $sslCorrection = $this->getSslStreamContext();
            if (count($sslCorrection)) {
                $streamContextOptions['ssl'] = $this->getSslStreamContext();
            }
            if (is_array($addonContextData) && count($addonContextData)) {
                foreach ($addonContextData as $contextKey => $contextValue) {
                    $streamContextOptions[$contextKey] = $contextValue;
                }
            }
            $optionsArray['stream_context'] = stream_context_create($streamContextOptions);
            $this->sslopt = $optionsArray;

            return $optionsArray;
        }
    }
}
