<?php declare(strict_types=1);

use Core\Http;

beforeEach(function () {
    // Сохраняем оригинальные значения
    $this->originalServer = $_SERVER;
});

afterEach(function () {
    // Восстанавливаем оригинальные значения
    $_SERVER = $this->originalServer;
});

describe('HTTP Basic Methods', function () {
    test('getMethod returns request method', function () {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        expect(Http::getMethod())->toBe('POST');
    });

    test('getMethod returns GET by default', function () {
        unset($_SERVER['REQUEST_METHOD']);
        
        expect(Http::getMethod())->toBe('GET');
    });

    test('getUri returns request URI', function () {
        $_SERVER['REQUEST_URI'] = '/users/123?sort=name';
        
        expect(Http::getUri())->toBe('/users/123?sort=name');
    });

    test('getPath returns path without query string', function () {
        $_SERVER['REQUEST_URI'] = '/users/123?sort=name';
        
        expect(Http::getPath())->toBe('/users/123');
    });

    test('getQueryString returns query string', function () {
        $_SERVER['QUERY_STRING'] = 'sort=name&order=asc';
        
        expect(Http::getQueryString())->toBe('sort=name&order=asc');
    });

    test('getProtocol returns server protocol', function () {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/2.0';
        
        expect(Http::getProtocol())->toBe('HTTP/2.0');
    });

    test('getHost returns HTTP host', function () {
        $_SERVER['HTTP_HOST'] = 'example.com';
        
        expect(Http::getHost())->toBe('example.com');
    });

    test('getPort returns server port', function () {
        $_SERVER['SERVER_PORT'] = '8080';
        
        expect(Http::getPort())->toBe(8080);
    });

    test('getPort returns 80 by default', function () {
        unset($_SERVER['SERVER_PORT']);
        
        expect(Http::getPort())->toBe(80);
    });
});

describe('HTTP Scheme Detection', function () {
    test('detects https from HTTPS variable', function () {
        $_SERVER['HTTPS'] = 'on';
        
        expect(Http::getScheme())->toBe('https');
        expect(Http::isSecure())->toBeTrue();
    });

    test('detects https from port 443', function () {
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = '443';
        
        expect(Http::getScheme())->toBe('https');
        expect(Http::isSecure())->toBeTrue();
    });

    test('detects https from X-Forwarded-Proto header', function () {
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        
        expect(Http::getScheme())->toBe('https');
        expect(Http::isSecure())->toBeTrue();
    });

    test('returns http by default', function () {
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_PORT'] = '80';
        
        expect(Http::getScheme())->toBe('http');
        expect(Http::isSecure())->toBeFalse();
    });
});

describe('URL Generation', function () {
    test('getFullUrl generates complete URL', function () {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/users/123?sort=name';
        
        expect(Http::getFullUrl())->toBe('http://example.com/users/123?sort=name');
    });

    test('getFullUrl includes non-standard port', function () {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '8080';
        $_SERVER['REQUEST_URI'] = '/test';
        
        expect(Http::getFullUrl())->toBe('http://example.com:8080/test');
    });

    test('getFullUrl handles HTTPS correctly', function () {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['REQUEST_URI'] = '/secure';
        
        expect(Http::getFullUrl())->toBe('https://example.com/secure');
    });

    test('getBaseUrl returns URL without path', function () {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '80';
        
        expect(Http::getBaseUrl())->toBe('http://example.com');
    });

    test('getBaseUrl includes non-standard port', function () {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '8080';
        
        expect(Http::getBaseUrl())->toBe('http://example.com:8080');
    });
});

describe('Client Information', function () {
    test('getClientIp returns REMOTE_ADDR', function () {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        
        expect(Http::getClientIp())->toBe('192.168.1.1');
    });

    test('getClientIp handles X-Forwarded-For', function () {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 192.168.1.1';
        $_SERVER['REMOTE_ADDR'] = '172.16.0.1';
        
        expect(Http::getClientIp())->toBe('10.0.0.1');
    });

    test('getClientIp validates IP address', function () {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'invalid-ip';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        
        expect(Http::getClientIp())->toBe('192.168.1.1');
    });

    test('getUserAgent returns user agent string', function () {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Test Browser';
        
        expect(Http::getUserAgent())->toBe('Mozilla/5.0 Test Browser');
    });

    test('getReferer returns referer URL', function () {
        $_SERVER['HTTP_REFERER'] = 'https://google.com';
        
        expect(Http::getReferer())->toBe('https://google.com');
    });

    test('getRequestTime returns request timestamp', function () {
        $_SERVER['REQUEST_TIME_FLOAT'] = 1234567890.1234;
        
        expect(Http::getRequestTime())->toBe(1234567890.1234);
    });
});

describe('HTTP Headers', function () {
    test('getHeaders returns all HTTP headers', function () {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token123';
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'custom-value';
        
        $headers = Http::getHeaders();
        
        expect($headers)->toBeArray();
        expect($headers)->toHaveKey('Accept');
        expect($headers)->toHaveKey('Authorization');
        expect($headers)->toHaveKey('X-Custom-Header');
    });

    test('getHeader returns specific header', function () {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer token123';
        
        expect(Http::getHeader('Authorization'))->toBe('Bearer token123');
    });

    test('getHeader is case insensitive', function () {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        
        expect(Http::getHeader('content-type'))->toBe('application/json');
        expect(Http::getHeader('Content-Type'))->toBe('application/json');
        expect(Http::getHeader('CONTENT_TYPE'))->toBe('application/json');
    });

    test('getAcceptedContentTypes parses Accept header', function () {
        $_SERVER['HTTP_ACCEPT'] = 'text/html, application/json;q=0.9, */*;q=0.8';
        
        $types = Http::getAcceptedContentTypes();
        
        expect($types)->toContain('text/html');
        expect($types)->toContain('application/json');
        expect($types)->toContain('*/*');
    });
});

describe('Method Checks', function () {
    test('isMethod checks request method', function () {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        expect(Http::isMethod('POST'))->toBeTrue();
        expect(Http::isMethod('post'))->toBeTrue();
        expect(Http::isMethod('GET'))->toBeFalse();
    });

    test('isGet checks GET method', function () {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        expect(Http::isGet())->toBeTrue();
    });

    test('isPost checks POST method', function () {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        expect(Http::isPost())->toBeTrue();
    });

    test('isPut checks PUT method', function () {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        
        expect(Http::isPut())->toBeTrue();
    });

    test('isPatch checks PATCH method', function () {
        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        
        expect(Http::isPatch())->toBeTrue();
    });

    test('isDelete checks DELETE method', function () {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        
        expect(Http::isDelete())->toBeTrue();
    });
});

describe('Request Type Checks', function () {
    test('isAjax detects AJAX requests', function () {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        
        expect(Http::isAjax())->toBeTrue();
    });

    test('isAjax is case insensitive', function () {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        
        expect(Http::isAjax())->toBeTrue();
    });

    test('isJson detects JSON content type', function () {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        expect(Http::isJson())->toBeTrue();
    });

    test('isJson detects JSON with charset', function () {
        $_SERVER['CONTENT_TYPE'] = 'application/json; charset=utf-8';
        
        expect(Http::isJson())->toBeTrue();
    });

    test('acceptsJson checks Accept header', function () {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        
        expect(Http::acceptsJson())->toBeTrue();
    });

    test('acceptsJson works with wildcard', function () {
        $_SERVER['HTTP_ACCEPT'] = '*/*';
        
        expect(Http::acceptsJson())->toBeTrue();
    });

    test('acceptsHtml checks Accept header', function () {
        $_SERVER['HTTP_ACCEPT'] = 'text/html';
        
        expect(Http::acceptsHtml())->toBeTrue();
    });
});

describe('Data Access', function () {
    test('getQueryParams returns GET parameters', function () {
        $_GET = ['id' => '123', 'sort' => 'name'];
        
        $params = Http::getQueryParams();
        
        expect($params)->toBe(['id' => '123', 'sort' => 'name']);
    });

    test('getPostData returns POST data', function () {
        $_POST = ['username' => 'john', 'email' => 'john@example.com'];
        
        $data = Http::getPostData();
        
        expect($data)->toBe(['username' => 'john', 'email' => 'john@example.com']);
    });

    test('getCookies returns cookies', function () {
        $_COOKIE = ['session_id' => 'abc123', 'user_lang' => 'ru'];
        
        $cookies = Http::getCookies();
        
        expect($cookies)->toBe(['session_id' => 'abc123', 'user_lang' => 'ru']);
    });

    test('getCookie returns specific cookie', function () {
        $_COOKIE = ['session_id' => 'abc123'];
        
        expect(Http::getCookie('session_id'))->toBe('abc123');
        expect(Http::getCookie('non_existent'))->toBeNull();
    });

    test('getFiles returns uploaded files', function () {
        $_FILES = ['avatar' => ['name' => 'photo.jpg', 'size' => 1024]];
        
        $files = Http::getFiles();
        
        expect($files)->toHaveKey('avatar');
    });
});

describe('Method Override', function () {
    test('getActualMethod returns POST by default', function () {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        expect(Http::getActualMethod())->toBe('POST');
    });

    test('getActualMethod respects _method field', function () {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_method'] = 'PUT';
        
        expect(Http::getActualMethod())->toBe('PUT');
    });

    test('getActualMethod respects X-HTTP-Method-Override header', function () {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'DELETE';
        
        expect(Http::getActualMethod())->toBe('DELETE');
    });

    test('getActualMethod header takes precedence over _method', function () {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_method'] = 'PUT';
        $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'DELETE';
        
        expect(Http::getActualMethod())->toBe('DELETE');
    });
});

describe('File Operations', function () {
    test('hasFiles returns true when files present', function () {
        $_FILES = ['upload' => ['name' => 'test.txt']];
        
        expect(Http::hasFiles())->toBeTrue();
    });

    test('hasFiles returns false when no files', function () {
        $_FILES = [];
        
        expect(Http::hasFiles())->toBeFalse();
    });

    test('getFile returns specific file', function () {
        $_FILES = [
            'avatar' => ['name' => 'photo.jpg', 'size' => 1024]
        ];
        
        $file = Http::getFile('avatar');
        
        expect($file)->toHaveKey('name');
        expect($file['name'])->toBe('photo.jpg');
    });

    test('getFile returns null for non-existent file', function () {
        expect(Http::getFile('non_existent'))->toBeNull();
    });

    test('isValidUpload checks upload validity', function () {
        $_FILES['valid'] = ['error' => UPLOAD_ERR_OK];
        $_FILES['invalid'] = ['error' => UPLOAD_ERR_NO_FILE];
        
        expect(Http::isValidUpload('valid'))->toBeTrue();
        expect(Http::isValidUpload('invalid'))->toBeFalse();
        expect(Http::isValidUpload('not_exists'))->toBeFalse();
    });

    test('getFileSize returns file size', function () {
        $_FILES['file'] = ['size' => 2048];
        
        expect(Http::getFileSize('file'))->toBe(2048);
    });

    test('getFileSize returns 0 for non-existent', function () {
        expect(Http::getFileSize('not_exists'))->toBe(0);
    });

    test('getFileExtension returns extension', function () {
        $_FILES['doc'] = ['name' => 'document.PDF'];
        
        expect(Http::getFileExtension('doc'))->toBe('pdf');
    });

    test('getFileMimeType returns mime type', function () {
        $_FILES['image'] = ['type' => 'image/jpeg'];
        
        expect(Http::getFileMimeType('image'))->toBe('image/jpeg');
    });
});

describe('Authentication', function () {
    test('getBearerToken extracts token from header', function () {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc123token';
        
        expect(Http::getBearerToken())->toBe('abc123token');
    });

    test('getBearerToken returns null if no header', function () {
        expect(Http::getBearerToken())->toBeNull();
    });

    test('getBearerToken handles case insensitive', function () {
        $_SERVER['HTTP_AUTHORIZATION'] = 'bearer xyz789';
        
        expect(Http::getBearerToken())->toBe('xyz789');
    });

    test('getBasicAuth from PHP_AUTH_USER', function () {
        $_SERVER['PHP_AUTH_USER'] = 'admin';
        $_SERVER['PHP_AUTH_PW'] = 'password123';
        
        $auth = Http::getBasicAuth();
        
        expect($auth)->toBe(['username' => 'admin', 'password' => 'password123']);
    });

    test('getBasicAuth from Authorization header', function () {
        $credentials = base64_encode('user:pass');
        $_SERVER['HTTP_AUTHORIZATION'] = "Basic $credentials";
        
        $auth = Http::getBasicAuth();
        
        expect($auth)->toBe(['username' => 'user', 'password' => 'pass']);
    });

    test('getBasicAuth returns null if not set', function () {
        expect(Http::getBasicAuth())->toBeNull();
    });
});

describe('Content Type Operations', function () {
    test('getContentLength returns content length', function () {
        $_SERVER['CONTENT_LENGTH'] = '1024';
        
        expect(Http::getContentLength())->toBe(1024);
    });

    test('getContentType returns content type', function () {
        $_SERVER['CONTENT_TYPE'] = 'application/json; charset=utf-8';
        
        expect(Http::getContentType())->toBe('application/json; charset=utf-8');
    });

    test('getMimeType extracts mime without charset', function () {
        $_SERVER['CONTENT_TYPE'] = 'text/html; charset=utf-8';
        
        expect(Http::getMimeType())->toBe('text/html');
    });

    test('isMultipart detects multipart forms', function () {
        $_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundary';
        
        expect(Http::isMultipart())->toBeTrue();
    });

    test('isFormUrlEncoded detects form encoding', function () {
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        
        expect(Http::isFormUrlEncoded())->toBeTrue();
    });

    test('getCharset extracts charset', function () {
        $_SERVER['CONTENT_TYPE'] = 'text/html; charset=ISO-8859-1';
        
        expect(Http::getCharset())->toBe('ISO-8859-1');
    });

    test('getCharset returns UTF-8 by default', function () {
        $_SERVER['CONTENT_TYPE'] = 'text/html';
        
        expect(Http::getCharset())->toBe('UTF-8');
    });
});

describe('Input Operations', function () {
    test('all merges GET and POST', function () {
        $_GET = ['a' => '1', 'b' => '2'];
        $_POST = ['c' => '3', 'd' => '4'];
        
        $all = Http::all();
        
        expect($all)->toBe(['a' => '1', 'b' => '2', 'c' => '3', 'd' => '4']);
    });

    test('input gets value from POST first', function () {
        $_GET['key'] = 'from_get';
        $_POST['key'] = 'from_post';
        
        expect(Http::input('key'))->toBe('from_post');
    });

    test('input falls back to GET', function () {
        $_GET['key'] = 'value';
        
        expect(Http::input('key'))->toBe('value');
    });

    test('input returns default if not found', function () {
        expect(Http::input('not_found', 'default'))->toBe('default');
    });

    test('has checks both GET and POST', function () {
        $_GET['get_key'] = 'value';
        $_POST['post_key'] = 'value';
        
        expect(Http::has('get_key'))->toBeTrue();
        expect(Http::has('post_key'))->toBeTrue();
        expect(Http::has('not_exists'))->toBeFalse();
    });

    test('only returns specified keys', function () {
        $_GET = ['a' => '1', 'b' => '2', 'c' => '3'];
        
        $result = Http::only(['a', 'c']);
        
        expect($result)->toBe(['a' => '1', 'c' => '3']);
    });

    test('except returns all except specified keys', function () {
        $_GET = ['a' => '1', 'b' => '2', 'c' => '3'];
        
        $result = Http::except(['b']);
        
        expect($result)->toBe(['a' => '1', 'c' => '3']);
    });

    test('isEmpty checks if value is empty', function () {
        $_POST['empty'] = '';
        $_POST['filled'] = 'value';
        
        expect(Http::isEmpty('empty'))->toBeTrue();
        expect(Http::isEmpty('filled'))->toBeFalse();
    });

    test('filled checks if value is not empty', function () {
        $_POST['filled'] = 'value';
        
        expect(Http::filled('filled'))->toBeTrue();
        expect(Http::filled('empty'))->toBeFalse();
    });
});

describe('Query String Operations', function () {
    test('parseQueryString parses query string', function () {
        $result = Http::parseQueryString('a=1&b=2&c=3');
        
        expect($result)->toBe(['a' => '1', 'b' => '2', 'c' => '3']);
    });

    test('parseQueryString uses current query by default', function () {
        $_SERVER['QUERY_STRING'] = 'x=10&y=20';
        
        $result = Http::parseQueryString();
        
        expect($result)->toBe(['x' => '10', 'y' => '20']);
    });

    test('buildQueryString builds query string', function () {
        $result = Http::buildQueryString(['a' => '1', 'b' => '2']);
        
        expect($result)->toBe('a=1&b=2');
    });

    test('getUrlWithParams modifies URL params', function () {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/test?a=1';
        
        $url = Http::getUrlWithParams(['b' => '2']);
        
        expect($url)->toContain('a=1');
        expect($url)->toContain('b=2');
    });

    test('getUrlWithParams replaces params when merge is false', function () {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/test?a=1';
        
        $url = Http::getUrlWithParams(['b' => '2'], false);
        
        expect($url)->not->toContain('a=1');
        expect($url)->toContain('b=2');
    });
});

describe('Detection Methods', function () {
    test('isPrefetch detects prefetch requests', function () {
        $_SERVER['HTTP_PURPOSE'] = 'prefetch';
        
        expect(Http::isPrefetch())->toBeTrue();
    });

    test('isBot detects bot user agents', function () {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Googlebot/2.1)';
        
        expect(Http::isBot())->toBeTrue();
    });

    test('isBot returns false for regular browsers', function () {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0) Chrome/91.0';
        
        expect(Http::isBot())->toBeFalse();
    });

    test('isMobile detects mobile devices', function () {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0)';
        
        expect(Http::isMobile())->toBeTrue();
    });

    test('isMobile returns false for desktop', function () {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0) Chrome/91.0';
        
        expect(Http::isMobile())->toBeFalse();
    });

    test('isSafe checks safe methods', function () {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        expect(Http::isSafe())->toBeTrue();
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        expect(Http::isSafe())->toBeFalse();
    });

    test('isIdempotent checks idempotent methods', function () {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        expect(Http::isIdempotent())->toBeTrue();
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        expect(Http::isIdempotent())->toBeFalse();
    });
});

describe('Language Detection', function () {
    test('getPreferredLanguage from Accept-Language', function () {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';
        
        expect(Http::getPreferredLanguage())->toBe('ru');
    });

    test('getPreferredLanguage with supported languages', function () {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9,en-US;q=0.8';
        
        $lang = Http::getPreferredLanguage(['en', 'ru', 'es']);
        
        expect($lang)->toBe('en'); // fr не поддерживается, fallback на первый
    });

    test('getAcceptedLanguages returns all languages with quality', function () {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru;q=0.9,en;q=0.8';
        
        $languages = Http::getAcceptedLanguages();
        
        expect($languages)->toHaveKey('ru');
        expect($languages)->toHaveKey('en');
        expect($languages['ru'])->toBe(0.9);
    });
});

describe('Caching Headers', function () {
    test('getEtag returns If-None-Match header', function () {
        $_SERVER['HTTP_IF_NONE_MATCH'] = '"abc123"';
        
        expect(Http::getEtag())->toBe('"abc123"');
    });

    test('getIfModifiedSince parses date header', function () {
        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'Wed, 21 Oct 2015 07:28:00 GMT';
        
        $timestamp = Http::getIfModifiedSince();
        
        expect($timestamp)->toBeInt();
        expect($timestamp)->toBeGreaterThan(0);
    });
});
