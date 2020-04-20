<?php

namespace TorneLIB\Module\Config;

/**
 * Class WrapperCurlOpt Protective layour for CURLOPT constants, in case they are not installed via curl.
 * Normally, when curl is installed with PHP, CURLOPT_<constant> is available. But if they are not present, this could
 * cause warnings or worse in a system that uses them. This is the failover class.
 *
 * @package TorneLIB\Module\Config
 * @version 6.1.0
 * @since 6.1.0
 */
abstract class WrapperCurlOpt
{
    const NETCURL_CURLOPT_AUTOREFERER = 58;
    const NETCURL_CURLOPT_BINARYTRANSFER = 19914;
    const NETCURL_CURLOPT_BUFFERSIZE = 98;
    const NETCURL_CURLOPT_CAINFO = 10065;
    const NETCURL_CURLOPT_CAPATH = 10097;
    const NETCURL_CURLOPT_CONNECTTIMEOUT = 78;
    const NETCURL_CURLOPT_COOKIE = 10022;
    const NETCURL_CURLOPT_COOKIEFILE = 10031;
    const NETCURL_CURLOPT_COOKIEJAR = 10082;
    const NETCURL_CURLOPT_COOKIESESSION = 96;
    const NETCURL_CURLOPT_CRLF = 27;
    const NETCURL_CURLOPT_CUSTOMREQUEST = 10036;
    const NETCURL_CURLOPT_DNS_CACHE_TIMEOUT = 92;
    const NETCURL_CURLOPT_DNS_USE_GLOBAL_CACHE = 91;
    const NETCURL_CURLOPT_EGDSOCKET = 10077;
    const NETCURL_CURLOPT_ENCODING = 10102;
    const NETCURL_CURLOPT_FAILONERROR = 45;
    const NETCURL_CURLOPT_FILE = 10001;
    const NETCURL_CURLOPT_FILETIME = 69;
    const NETCURL_CURLOPT_FOLLOWLOCATION = 52;
    const NETCURL_CURLOPT_FORBID_REUSE = 75;
    const NETCURL_CURLOPT_FRESH_CONNECT = 74;
    const NETCURL_CURLOPT_FTPAPPEND = 50;
    const NETCURL_CURLOPT_FTPLISTONLY = 48;
    const NETCURL_CURLOPT_FTPPORT = 10017;
    const NETCURL_CURLOPT_FTP_USE_EPRT = 106;
    const NETCURL_CURLOPT_FTP_USE_EPSV = 85;
    const NETCURL_CURLOPT_HEADER = 42;
    const NETCURL_CURLOPT_HEADERFUNCTION = 20079;
    const NETCURL_CURLOPT_HTTP200ALIASES = 10104;
    const NETCURL_CURLOPT_HTTPGET = 80;
    const NETCURL_CURLOPT_HTTPHEADER = 10023;
    const NETCURL_CURLOPT_HTTPPROXYTUNNEL = 61;
    const NETCURL_CURLOPT_HTTP_VERSION = 84;
    const NETCURL_CURLOPT_INFILE = 10009;
    const NETCURL_CURLOPT_INFILESIZE = 14;
    const NETCURL_CURLOPT_INTERFACE = 10062;
    const NETCURL_CURLOPT_KRB4LEVEL = 10063;
    const NETCURL_CURLOPT_LOW_SPEED_LIMIT = 19;
    const NETCURL_CURLOPT_LOW_SPEED_TIME = 20;
    const NETCURL_CURLOPT_MAXCONNECTS = 71;
    const NETCURL_CURLOPT_MAXREDIRS = 68;
    const NETCURL_CURLOPT_NETRC = 51;
    const NETCURL_CURLOPT_NOBODY = 44;
    const NETCURL_CURLOPT_NOPROGRESS = 43;
    const NETCURL_CURLOPT_NOSIGNAL = 99;
    const NETCURL_CURLOPT_PORT = 3;
    const NETCURL_CURLOPT_POST = 47;
    const NETCURL_CURLOPT_POSTFIELDS = 10015;
    const NETCURL_CURLOPT_POSTQUOTE = 10039;
    const NETCURL_CURLOPT_PREQUOTE = 10093;
    const NETCURL_CURLOPT_PRIVATE = 10103;
    const NETCURL_CURLOPT_PROGRESSFUNCTION = 20056;
    const NETCURL_CURLOPT_PROXY = 10004;
    const NETCURL_CURLOPT_PROXYPORT = 59;
    const NETCURL_CURLOPT_PROXYTYPE = 101;
    const NETCURL_CURLOPT_PROXYUSERPWD = 10006;
    const NETCURL_CURLOPT_PUT = 54;
    const NETCURL_CURLOPT_QUOTE = 10028;
    const NETCURL_CURLOPT_RANDOM_FILE = 10076;
    const NETCURL_CURLOPT_RANGE = 10007;
    const NETCURL_CURLOPT_READDATA = 10009;
    const NETCURL_CURLOPT_READFUNCTION = 20012;
    const NETCURL_CURLOPT_REFERER = 10016;
    const NETCURL_CURLOPT_RESUME_FROM = 21;
    const NETCURL_CURLOPT_RETURNTRANSFER = 19913;
    const NETCURL_CURLOPT_SHARE = 10100;
    const NETCURL_CURLOPT_SSLCERT = 10025;
    const NETCURL_CURLOPT_SSLCERTPASSWD = 10026;
    const NETCURL_CURLOPT_SSLCERTTYPE = 10086;
    const NETCURL_CURLOPT_SSLENGINE = 10089;
    const NETCURL_CURLOPT_SSLENGINE_DEFAULT = 90;
    const NETCURL_CURLOPT_SSLKEY = 10087;
    const NETCURL_CURLOPT_SSLKEYPASSWD = 10026;
    const NETCURL_CURLOPT_SSLKEYTYPE = 10088;
    const NETCURL_CURLOPT_SSLVERSION = 32;
    const NETCURL_CURLOPT_SSL_CIPHER_LIST = 10083;
    const NETCURL_CURLOPT_SSL_VERIFYHOST = 81;
    const NETCURL_CURLOPT_SSL_VERIFYPEER = 64;
    const NETCURL_CURLOPT_STDERR = 10037;
    const NETCURL_CURLOPT_TELNETOPTIONS = 10070;
    const NETCURL_CURLOPT_TIMECONDITION = 33;
    const NETCURL_CURLOPT_TIMEOUT = 13;
    const NETCURL_CURLOPT_TIMEVALUE = 34;
    const NETCURL_CURLOPT_TRANSFERTEXT = 53;
    const NETCURL_CURLOPT_UNRESTRICTED_AUTH = 105;
    const NETCURL_CURLOPT_UPLOAD = 46;
    const NETCURL_CURLOPT_URL = 10002;
    const NETCURL_CURLOPT_USERAGENT = 10018;
    const NETCURL_CURLOPT_USERPWD = 10005;
    const NETCURL_CURLOPT_VERBOSE = 41;
    const NETCURL_CURLOPT_WRITEFUNCTION = 20011;
    const NETCURL_CURLOPT_WRITEHEADER = 10029;
    const NETCURL_CURLE_ABORTED_BY_CALLBACK = 42;
    const NETCURL_CURLE_BAD_CALLING_ORDER = 44;
    const NETCURL_CURLE_BAD_CONTENT_ENCODING = 61;
    const NETCURL_CURLE_BAD_DOWNLOAD_RESUME = 36;
    const NETCURL_CURLE_BAD_FUNCTION_ARGUMENT = 43;
    const NETCURL_CURLE_BAD_PASSWORD_ENTERED = 46;
    const NETCURL_CURLE_COULDNT_CONNECT = 7;
    const NETCURL_CURLE_COULDNT_RESOLVE_HOST = 6;
    const NETCURL_CURLE_COULDNT_RESOLVE_PROXY = 5;
    const NETCURL_CURLE_FAILED_INIT = 2;
    const NETCURL_CURLE_FILE_COULDNT_READ_FILE = 37;
    const NETCURL_CURLE_FTP_ACCESS_DENIED = 9;
    const NETCURL_CURLE_FTP_BAD_DOWNLOAD_RESUME = 36;
    const NETCURL_CURLE_FTP_CANT_GET_HOST = 15;
    const NETCURL_CURLE_FTP_CANT_RECONNECT = 16;
    const NETCURL_CURLE_FTP_COULDNT_GET_SIZE = 32;
    const NETCURL_CURLE_FTP_COULDNT_RETR_FILE = 19;
    const NETCURL_CURLE_FTP_COULDNT_SET_ASCII = 29;
    const NETCURL_CURLE_FTP_COULDNT_SET_BINARY = 17;
    const NETCURL_CURLE_FTP_COULDNT_STOR_FILE = 25;
    const NETCURL_CURLE_FTP_COULDNT_USE_REST = 31;
    const NETCURL_CURLE_FTP_PARTIAL_FILE = 18;
    const NETCURL_CURLE_FTP_PORT_FAILED = 30;
    const NETCURL_CURLE_FTP_QUOTE_ERROR = 21;
    const NETCURL_CURLE_FTP_USER_PASSWORD_INCORRECT = 10;
    const NETCURL_CURLE_FTP_WEIRD_227_FORMAT = 14;
    const NETCURL_CURLE_FTP_WEIRD_PASS_REPLY = 11;
    const NETCURL_CURLE_FTP_WEIRD_PASV_REPLY = 13;
    const NETCURL_CURLE_FTP_WEIRD_SERVER_REPLY = 8;
    const NETCURL_CURLE_FTP_WEIRD_USER_REPLY = 12;
    const NETCURL_CURLE_FTP_WRITE_ERROR = 20;
    const NETCURL_CURLE_FUNCTION_NOT_FOUND = 41;
    const NETCURL_CURLE_GOT_NOTHING = 52;
    const NETCURL_CURLE_HTTP_NOT_FOUND = 22;
    const NETCURL_CURLE_HTTP_PORT_FAILED = 45;
    const NETCURL_CURLE_HTTP_POST_ERROR = 34;
    const NETCURL_CURLE_HTTP_RANGE_ERROR = 33;
    const NETCURL_CURLE_HTTP_RETURNED_ERROR = 22;
    const NETCURL_CURLE_LDAP_CANNOT_BIND = 38;
    const NETCURL_CURLE_LDAP_SEARCH_FAILED = 39;
    const NETCURL_CURLE_LIBRARY_NOT_FOUND = 40;
    const NETCURL_CURLE_MALFORMAT_USER = 24;
    const NETCURL_CURLE_OBSOLETE = 50;
    const NETCURL_CURLE_OK = 0;
    const NETCURL_CURLE_OPERATION_TIMEDOUT = 28;
    const NETCURL_CURLE_OPERATION_TIMEOUTED = 28;
    const NETCURL_CURLE_OUT_OF_MEMORY = 27;
    const NETCURL_CURLE_PARTIAL_FILE = 18;
    const NETCURL_CURLE_READ_ERROR = 26;
    const NETCURL_CURLE_RECV_ERROR = 56;
    const NETCURL_CURLE_SEND_ERROR = 55;
    const NETCURL_CURLE_SHARE_IN_USE = 57;
    const NETCURL_CURLE_SSL_CACERT = 60;
    const NETCURL_CURLE_SSL_CERTPROBLEM = 58;
    const NETCURL_CURLE_SSL_CIPHER = 59;
    const NETCURL_CURLE_SSL_CONNECT_ERROR = 35;
    const NETCURL_CURLE_SSL_ENGINE_NOTFOUND = 53;
    const NETCURL_CURLE_SSL_ENGINE_SETFAILED = 54;
    const NETCURL_CURLE_SSL_PEER_CERTIFICATE = 51;
    const NETCURL_CURLE_SSL_PINNEDPUBKEYNOTMATCH = 90;
    const NETCURL_CURLE_TELNET_OPTION_SYNTAX = 49;
    const NETCURL_CURLE_TOO_MANY_REDIRECTS = 47;
    const NETCURL_CURLE_UNKNOWN_TELNET_OPTION = 48;
    const NETCURL_CURLE_UNSUPPORTED_PROTOCOL = 1;
    const NETCURL_CURLE_URL_MALFORMAT = 3;
    const NETCURL_CURLE_URL_MALFORMAT_USER = 4;
    const NETCURL_CURLE_WRITE_ERROR = 23;
    const NETCURL_CURLINFO_CONNECT_TIME = 3145733;
    const NETCURL_CURLINFO_CONTENT_LENGTH_DOWNLOAD = 3145743;
    const NETCURL_CURLINFO_CONTENT_LENGTH_UPLOAD = 3145744;
    const NETCURL_CURLINFO_CONTENT_TYPE = 1048594;
    const NETCURL_CURLINFO_EFFECTIVE_URL = 1048577;
    const NETCURL_CURLINFO_FILETIME = 2097166;
    const NETCURL_CURLINFO_HEADER_OUT = 2;
    const NETCURL_CURLINFO_HEADER_SIZE = 2097163;
    const NETCURL_CURLINFO_HTTP_CODE = 2097154;
    const NETCURL_CURLINFO_LASTONE = 49;
    const NETCURL_CURLINFO_NAMELOOKUP_TIME = 3145732;
    const NETCURL_CURLINFO_PRETRANSFER_TIME = 3145734;
    const NETCURL_CURLINFO_PRIVATE = 1048597;
    const NETCURL_CURLINFO_REDIRECT_COUNT = 2097172;
    const NETCURL_CURLINFO_REDIRECT_TIME = 3145747;
    const NETCURL_CURLINFO_REQUEST_SIZE = 2097164;
    const NETCURL_CURLINFO_SIZE_DOWNLOAD = 3145736;
    const NETCURL_CURLINFO_SIZE_UPLOAD = 3145735;
    const NETCURL_CURLINFO_SPEED_DOWNLOAD = 3145737;
    const NETCURL_CURLINFO_SPEED_UPLOAD = 3145738;
    const NETCURL_CURLINFO_SSL_VERIFYRESULT = 2097165;
    const NETCURL_CURLINFO_STARTTRANSFER_TIME = 3145745;
    const NETCURL_CURLINFO_TOTAL_TIME = 3145731;
    const NETCURL_CURLMSG_DONE = 1;
    const NETCURL_CURLVERSION_NOW = 4;
    const NETCURL_CURLM_BAD_EASY_HANDLE = 2;
    const NETCURL_CURLM_BAD_HANDLE = 1;
    const NETCURL_CURLM_CALL_MULTI_PERFORM = -1;
    const NETCURL_CURLM_INTERNAL_ERROR = 4;
    const NETCURL_CURLM_OK = 0;
    const NETCURL_CURLM_OUT_OF_MEMORY = 3;
    const NETCURL_CURLM_ADDED_ALREADY = 7;
    const NETCURL_CURLPROXY_HTTP = 0;
    const NETCURL_CURLPROXY_SOCKS4 = 4;
    const NETCURL_CURLPROXY_SOCKS5 = 5;
    const NETCURL_CURLSHOPT_NONE = 0;
    const NETCURL_CURLSHOPT_SHARE = 1;
    const NETCURL_CURLSHOPT_UNSHARE = 2;
    const NETCURL_CURL_HTTP_VERSION_1_0 = 1;
    const NETCURL_CURL_HTTP_VERSION_1_1 = 2;
    const NETCURL_CURL_HTTP_VERSION_NONE = 0;
    const NETCURL_CURL_LOCK_DATA_COOKIE = 2;
    const NETCURL_CURL_LOCK_DATA_DNS = 3;
    const NETCURL_CURL_LOCK_DATA_SSL_SESSION = 4;
    const NETCURL_CURL_NETRC_IGNORED = 0;
    const NETCURL_CURL_NETRC_OPTIONAL = 1;
    const NETCURL_CURL_NETRC_REQUIRED = 2;
    const NETCURL_CURL_SSLVERSION_DEFAULT = 0;
    const NETCURL_CURL_SSLVERSION_SSLv2 = 2;
    const NETCURL_CURL_SSLVERSION_SSLv3 = 3;
    const NETCURL_CURL_SSLVERSION_TLSv1 = 1;
    const NETCURL_CURL_TIMECOND_IFMODSINCE = 1;
    const NETCURL_CURL_TIMECOND_IFUNMODSINCE = 2;
    const NETCURL_CURL_TIMECOND_LASTMOD = 3;
    const NETCURL_CURL_TIMECOND_NONE = 0;
    const NETCURL_CURL_VERSION_IPV6 = 1;
    const NETCURL_CURL_VERSION_KERBEROS4 = 2;
    const NETCURL_CURL_VERSION_LIBZ = 8;
    const NETCURL_CURL_VERSION_SSL = 4;
    const NETCURL_CURLOPT_HTTPAUTH = 107;
    const NETCURL_CURLAUTH_ANY = -17;
    const NETCURL_CURLAUTH_ANYSAFE = -18;
    const NETCURL_CURLAUTH_BASIC = 1;
    const NETCURL_CURLAUTH_DIGEST = 2;
    const NETCURL_CURLAUTH_GSSNEGOTIATE = 4;
    const NETCURL_CURLAUTH_NONE = 0;
    const NETCURL_CURLAUTH_NTLM = 8;
    const NETCURL_CURLINFO_HTTP_CONNECTCODE = 2097174;
    const NETCURL_CURLOPT_FTP_CREATE_MISSING_DIRS = 110;
    const NETCURL_CURLOPT_PROXYAUTH = 111;
    const NETCURL_CURLE_FILESIZE_EXCEEDED = 63;
    const NETCURL_CURLE_LDAP_INVALID_URL = 62;
    const NETCURL_CURLINFO_HTTPAUTH_AVAIL = 2097175;
    const NETCURL_CURLINFO_RESPONSE_CODE = 2097154;
    const NETCURL_CURLINFO_PROXYAUTH_AVAIL = 2097176;
    const NETCURL_CURLOPT_FTP_RESPONSE_TIMEOUT = 112;
    const NETCURL_CURLOPT_IPRESOLVE = 113;
    const NETCURL_CURLOPT_MAXFILESIZE = 114;
    const NETCURL_CURL_IPRESOLVE_V4 = 1;
    const NETCURL_CURL_IPRESOLVE_V6 = 2;
    const NETCURL_CURL_IPRESOLVE_WHATEVER = 0;
    const NETCURL_CURLE_FTP_SSL_FAILED = 64;
    const NETCURL_CURLFTPSSL_ALL = 3;
    const NETCURL_CURLFTPSSL_CONTROL = 2;
    const NETCURL_CURLFTPSSL_NONE = 0;
    const NETCURL_CURLFTPSSL_TRY = 1;
    const NETCURL_CURLOPT_FTP_SSL = 119;
    const NETCURL_CURLOPT_NETRC_FILE = 10118;
    const NETCURL_CURLFTPAUTH_DEFAULT = 0;
    const NETCURL_CURLFTPAUTH_SSL = 1;
    const NETCURL_CURLFTPAUTH_TLS = 2;
    const NETCURL_CURLOPT_FTPSSLAUTH = 129;
    const NETCURL_CURLOPT_FTP_ACCOUNT = 10134;
    const NETCURL_CURLOPT_TCP_NODELAY = 121;
    const NETCURL_CURLINFO_OS_ERRNO = 2097177;
    const NETCURL_CURLINFO_NUM_CONNECTS = 2097178;
    const NETCURL_CURLINFO_SSL_ENGINES = 4194331;
    const NETCURL_CURLINFO_COOKIELIST = 4194332;
    const NETCURL_CURLOPT_COOKIELIST = 10135;
    const NETCURL_CURLOPT_IGNORE_CONTENT_LENGTH = 136;
    const NETCURL_CURLOPT_FTP_SKIP_PASV_IP = 137;
    const NETCURL_CURLOPT_FTP_FILEMETHOD = 138;
    const NETCURL_CURLOPT_CONNECT_ONLY = 141;
    const NETCURL_CURLOPT_LOCALPORT = 139;
    const NETCURL_CURLOPT_LOCALPORTRANGE = 140;
    const NETCURL_CURLFTPMETHOD_MULTICWD = 1;
    const NETCURL_CURLFTPMETHOD_NOCWD = 2;
    const NETCURL_CURLFTPMETHOD_SINGLECWD = 3;
    const NETCURL_CURLINFO_FTP_ENTRY_PATH = 1048606;
    const NETCURL_CURLOPT_FTP_ALTERNATIVE_TO_USER = 10147;
    const NETCURL_CURLOPT_MAX_RECV_SPEED_LARGE = 30146;
    const NETCURL_CURLOPT_MAX_SEND_SPEED_LARGE = 30145;
    const NETCURL_CURLE_SSL_CACERT_BADFILE = 77;
    const NETCURL_CURLOPT_SSL_SESSIONID_CACHE = 150;
    const NETCURL_CURLMOPT_PIPELINING = 3;
    const NETCURL_CURLE_SSH = 79;
    const NETCURL_CURLOPT_FTP_SSL_CCC = 154;
    const NETCURL_CURLOPT_SSH_AUTH_TYPES = 151;
    const NETCURL_CURLOPT_SSH_PRIVATE_KEYFILE = 10153;
    const NETCURL_CURLOPT_SSH_PUBLIC_KEYFILE = 10152;
    const NETCURL_CURLFTPSSL_CCC_ACTIVE = 2;
    const NETCURL_CURLFTPSSL_CCC_NONE = 0;
    const NETCURL_CURLFTPSSL_CCC_PASSIVE = 1;
    const NETCURL_CURLOPT_CONNECTTIMEOUT_MS = 156;
    const NETCURL_CURLOPT_HTTP_CONTENT_DECODING = 158;
    const NETCURL_CURLOPT_HTTP_TRANSFER_DECODING = 157;
    const NETCURL_CURLOPT_TIMEOUT_MS = 155;
    const NETCURL_CURLMOPT_MAXCONNECTS = 6;
    const NETCURL_CURLOPT_KRBLEVEL = 10063;
    const NETCURL_CURLOPT_NEW_DIRECTORY_PERMS = 160;
    const NETCURL_CURLOPT_NEW_FILE_PERMS = 159;
    const NETCURL_CURLOPT_APPEND = 50;
    const NETCURL_CURLOPT_DIRLISTONLY = 48;
    const NETCURL_CURLOPT_USE_SSL = 119;
    const NETCURL_CURLUSESSL_ALL = 3;
    const NETCURL_CURLUSESSL_CONTROL = 2;
    const NETCURL_CURLUSESSL_NONE = 0;
    const NETCURL_CURLUSESSL_TRY = 1;
    const NETCURL_CURLOPT_SSH_HOST_PUBLIC_KEY_MD5 = 10162;
    const NETCURL_CURLOPT_PROXY_TRANSFER_MODE = 166;
    const NETCURL_CURLPAUSE_ALL = 5;
    const NETCURL_CURLPAUSE_CONT = 0;
    const NETCURL_CURLPAUSE_RECV = 1;
    const NETCURL_CURLPAUSE_RECV_CONT = 0;
    const NETCURL_CURLPAUSE_SEND = 4;
    const NETCURL_CURLPAUSE_SEND_CONT = 0;
    const NETCURL_CURL_READFUNC_PAUSE = 268435457;
    const NETCURL_CURL_WRITEFUNC_PAUSE = 268435457;
    const NETCURL_CURLPROXY_SOCKS4A = 6;
    const NETCURL_CURLPROXY_SOCKS5_HOSTNAME = 7;
    const NETCURL_CURLINFO_REDIRECT_URL = 1048607;
    const NETCURL_CURLINFO_APPCONNECT_TIME = 3145761;
    const NETCURL_CURLINFO_PRIMARY_IP = 1048608;
    const NETCURL_CURLOPT_ADDRESS_SCOPE = 171;
    const NETCURL_CURLOPT_CRLFILE = 10169;
    const NETCURL_CURLOPT_ISSUERCERT = 10170;
    const NETCURL_CURLOPT_KEYPASSWD = 10026;
    const NETCURL_CURLSSH_AUTH_ANY = -1;
    const NETCURL_CURLSSH_AUTH_DEFAULT = -1;
    const NETCURL_CURLSSH_AUTH_HOST = 4;
    const NETCURL_CURLSSH_AUTH_KEYBOARD = 8;
    const NETCURL_CURLSSH_AUTH_NONE = 0;
    const NETCURL_CURLSSH_AUTH_PASSWORD = 2;
    const NETCURL_CURLSSH_AUTH_PUBLICKEY = 1;
    const NETCURL_CURLINFO_CERTINFO = 4194338;
    const NETCURL_CURLOPT_CERTINFO = 172;
    const NETCURL_CURLOPT_PASSWORD = 10174;
    const NETCURL_CURLOPT_POSTREDIR = 161;
    const NETCURL_CURLOPT_PROXYPASSWORD = 10176;
    const NETCURL_CURLOPT_PROXYUSERNAME = 10175;
    const NETCURL_CURLOPT_USERNAME = 10173;
    const NETCURL_CURL_REDIR_POST_301 = 1;
    const NETCURL_CURL_REDIR_POST_302 = 2;
    const NETCURL_CURL_REDIR_POST_ALL = 7;
    const NETCURL_CURLAUTH_DIGEST_IE = 16;
    const NETCURL_CURLINFO_CONDITION_UNMET = 2097187;
    const NETCURL_CURLOPT_NOPROXY = 10177;
    const NETCURL_CURLOPT_PROTOCOLS = 181;
    const NETCURL_CURLOPT_REDIR_PROTOCOLS = 182;
    const NETCURL_CURLOPT_SOCKS5_GSSAPI_NEC = 180;
    const NETCURL_CURLOPT_SOCKS5_GSSAPI_SERVICE = 10179;
    const NETCURL_CURLOPT_TFTP_BLKSIZE = 178;
    const NETCURL_CURLPROTO_ALL = -1;
    const NETCURL_CURLPROTO_DICT = 512;
    const NETCURL_CURLPROTO_FILE = 1024;
    const NETCURL_CURLPROTO_FTP = 4;
    const NETCURL_CURLPROTO_FTPS = 8;
    const NETCURL_CURLPROTO_HTTP = 1;
    const NETCURL_CURLPROTO_HTTPS = 2;
    const NETCURL_CURLPROTO_LDAP = 128;
    const NETCURL_CURLPROTO_LDAPS = 256;
    const NETCURL_CURLPROTO_SCP = 16;
    const NETCURL_CURLPROTO_SFTP = 32;
    const NETCURL_CURLPROTO_TELNET = 64;
    const NETCURL_CURLPROTO_TFTP = 2048;
    const NETCURL_CURLPROXY_HTTP_1_0 = 1;
    const NETCURL_CURLFTP_CREATE_DIR = 1;
    const NETCURL_CURLFTP_CREATE_DIR_NONE = 0;
    const NETCURL_CURLFTP_CREATE_DIR_RETRY = 2;
    const NETCURL_CURLOPT_SSH_KNOWNHOSTS = 10183;
    const NETCURL_CURLINFO_RTSP_CLIENT_CSEQ = 2097189;
    const NETCURL_CURLINFO_RTSP_CSEQ_RECV = 2097191;
    const NETCURL_CURLINFO_RTSP_SERVER_CSEQ = 2097190;
    const NETCURL_CURLINFO_RTSP_SESSION_ID = 1048612;
    const NETCURL_CURLOPT_FTP_USE_PRET = 188;
    const NETCURL_CURLOPT_MAIL_FROM = 10186;
    const NETCURL_CURLOPT_MAIL_RCPT = 10187;
    const NETCURL_CURLOPT_RTSP_CLIENT_CSEQ = 193;
    const NETCURL_CURLOPT_RTSP_REQUEST = 189;
    const NETCURL_CURLOPT_RTSP_SERVER_CSEQ = 194;
    const NETCURL_CURLOPT_RTSP_SESSION_ID = 10190;
    const NETCURL_CURLOPT_RTSP_STREAM_URI = 10191;
    const NETCURL_CURLOPT_RTSP_TRANSPORT = 10192;
    const NETCURL_CURLPROTO_IMAP = 4096;
    const NETCURL_CURLPROTO_IMAPS = 8192;
    const NETCURL_CURLPROTO_POP3 = 16384;
    const NETCURL_CURLPROTO_POP3S = 32768;
    const NETCURL_CURLPROTO_RTSP = 262144;
    const NETCURL_CURLPROTO_SMTP = 65536;
    const NETCURL_CURLPROTO_SMTPS = 131072;
    const NETCURL_CURL_RTSPREQ_ANNOUNCE = 3;
    const NETCURL_CURL_RTSPREQ_DESCRIBE = 2;
    const NETCURL_CURL_RTSPREQ_GET_PARAMETER = 8;
    const NETCURL_CURL_RTSPREQ_OPTIONS = 1;
    const NETCURL_CURL_RTSPREQ_PAUSE = 6;
    const NETCURL_CURL_RTSPREQ_PLAY = 5;
    const NETCURL_CURL_RTSPREQ_RECEIVE = 11;
    const NETCURL_CURL_RTSPREQ_RECORD = 10;
    const NETCURL_CURL_RTSPREQ_SET_PARAMETER = 9;
    const NETCURL_CURL_RTSPREQ_SETUP = 4;
    const NETCURL_CURL_RTSPREQ_TEARDOWN = 7;
    const NETCURL_CURLINFO_LOCAL_IP = 1048617;
    const NETCURL_CURLINFO_LOCAL_PORT = 2097194;
    const NETCURL_CURLINFO_PRIMARY_PORT = 2097192;
    const NETCURL_CURLOPT_FNMATCH_FUNCTION = 20200;
    const NETCURL_CURLOPT_WILDCARDMATCH = 197;
    const NETCURL_CURLPROTO_RTMP = 524288;
    const NETCURL_CURLPROTO_RTMPE = 2097152;
    const NETCURL_CURLPROTO_RTMPS = 8388608;
    const NETCURL_CURLPROTO_RTMPT = 1048576;
    const NETCURL_CURLPROTO_RTMPTE = 4194304;
    const NETCURL_CURLPROTO_RTMPTS = 16777216;
    const NETCURL_CURL_FNMATCHFUNC_FAIL = 2;
    const NETCURL_CURL_FNMATCHFUNC_MATCH = 0;
    const NETCURL_CURL_FNMATCHFUNC_NOMATCH = 1;
    const NETCURL_CURLPROTO_GOPHER = 33554432;
    const NETCURL_CURLAUTH_ONLY = 2147483648;
    const NETCURL_CURLOPT_RESOLVE = 10203;
    const NETCURL_CURLOPT_TLSAUTH_PASSWORD = 10205;
    const NETCURL_CURLOPT_TLSAUTH_TYPE = 10206;
    const NETCURL_CURLOPT_TLSAUTH_USERNAME = 10204;
    const NETCURL_CURL_TLSAUTH_SRP = 1;
    const NETCURL_CURLOPT_ACCEPT_ENCODING = 10102;
    const NETCURL_CURLOPT_TRANSFER_ENCODING = 207;
    const NETCURL_CURLAUTH_NTLM_WB = 32;
    const NETCURL_CURLGSSAPI_DELEGATION_FLAG = 2;
    const NETCURL_CURLGSSAPI_DELEGATION_POLICY_FLAG = 1;
    const NETCURL_CURLOPT_GSSAPI_DELEGATION = 210;
    const NETCURL_CURLOPT_ACCEPTTIMEOUT_MS = 212;
    const NETCURL_CURLOPT_DNS_SERVERS = 10211;
    const NETCURL_CURLOPT_MAIL_AUTH = 10217;
    const NETCURL_CURLOPT_SSL_OPTIONS = 216;
    const NETCURL_CURLOPT_TCP_KEEPALIVE = 213;
    const NETCURL_CURLOPT_TCP_KEEPIDLE = 214;
    const NETCURL_CURLOPT_TCP_KEEPINTVL = 215;
    const NETCURL_CURLSSLOPT_ALLOW_BEAST = 1;
    const NETCURL_CURL_REDIR_POST_303 = 4;
    const NETCURL_CURLSSH_AUTH_AGENT = 16;
    const NETCURL_CURLMOPT_CHUNK_LENGTH_PENALTY_SIZE = 30010;
    const NETCURL_CURLMOPT_CONTENT_LENGTH_PENALTY_SIZE = 30009;
    const NETCURL_CURLMOPT_MAX_HOST_CONNECTIONS = 7;
    const NETCURL_CURLMOPT_MAX_PIPELINE_LENGTH = 8;
    const NETCURL_CURLMOPT_MAX_TOTAL_CONNECTIONS = 13;
    const NETCURL_CURLOPT_SASL_IR = 218;
    const NETCURL_CURLOPT_DNS_INTERFACE = 10221;
    const NETCURL_CURLOPT_DNS_LOCAL_IP4 = 10222;
    const NETCURL_CURLOPT_DNS_LOCAL_IP6 = 10223;
    const NETCURL_CURLOPT_XOAUTH2_BEARER = 10220;
    const NETCURL_CURL_HTTP_VERSION_2_0 = 3;
    const NETCURL_CURL_VERSION_HTTP2 = 65536;
    const NETCURL_CURLOPT_LOGIN_OPTIONS = 10224;
    const NETCURL_CURL_SSLVERSION_TLSv1_0 = 4;
    const NETCURL_CURL_SSLVERSION_TLSv1_1 = 5;
    const NETCURL_CURL_SSLVERSION_TLSv1_2 = 6;
    const NETCURL_CURLOPT_EXPECT_100_TIMEOUT_MS = 227;
    const NETCURL_CURLOPT_SSL_ENABLE_ALPN = 226;
    const NETCURL_CURLOPT_SSL_ENABLE_NPN = 225;
    const NETCURL_CURLHEADER_SEPARATE = 1;
    const NETCURL_CURLHEADER_UNIFIED = 0;
    const NETCURL_CURLOPT_HEADEROPT = 229;
    const NETCURL_CURLOPT_PROXYHEADER = 10228;
    const NETCURL_CURLAUTH_NEGOTIATE = 4;
    const NETCURL_CURLOPT_PINNEDPUBLICKEY = 10230;
    const NETCURL_CURLOPT_UNIX_SOCKET_PATH = 10231;
    const NETCURL_CURLPROTO_SMB = 67108864;
    const NETCURL_CURLPROTO_SMBS = 134217728;
    const NETCURL_CURLOPT_SSL_VERIFYSTATUS = 232;
    const NETCURL_CURLOPT_PATH_AS_IS = 234;
    const NETCURL_CURLOPT_SSL_FALSESTART = 233;
    const NETCURL_CURL_HTTP_VERSION_2 = 3;
    const NETCURL_CURLOPT_PIPEWAIT = 237;
    const NETCURL_CURLOPT_PROXY_SERVICE_NAME = 10235;
    const NETCURL_CURLOPT_SERVICE_NAME = 10236;
    const NETCURL_CURLPIPE_NOTHING = 0;
    const NETCURL_CURLPIPE_HTTP1 = 1;
    const NETCURL_CURLPIPE_MULTIPLEX = 2;
    const NETCURL_CURLSSLOPT_NO_REVOKE = 2;
    const NETCURL_CURLOPT_DEFAULT_PROTOCOL = 10238;
    const NETCURL_CURLOPT_STREAM_WEIGHT = 239;
    const NETCURL_CURLMOPT_PUSHFUNCTION = 20014;
    const NETCURL_CURL_PUSH_OK = 0;
    const NETCURL_CURL_PUSH_DENY = 1;
    const NETCURL_CURL_HTTP_VERSION_2TLS = 4;
    const NETCURL_CURLOPT_TFTP_NO_OPTIONS = 242;
    const NETCURL_CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE = 5;
    const NETCURL_CURLOPT_CONNECT_TO = 10243;
    const NETCURL_CURLOPT_TCP_FASTOPEN = 244;
    const NETCURL_CURLOPT_SAFE_UPLOAD = -1;
}
