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
