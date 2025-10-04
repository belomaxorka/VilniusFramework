<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\HttpInterface;

/**
 * HTTP Facade
 * 
 * Статический фасад для HttpService
 * Все методы делегируются к HttpInterface через DI контейнер
 * 
 * @method static string getMethod()
 * @method static string getActualMethod()
 * @method static string getProtocol()
 * @method static float getRequestTime()
 * @method static string getUri()
 * @method static string getPath()
 * @method static string getQueryString()
 * @method static string getFullUrl()
 * @method static string getBaseUrl()
 * @method static string getUrlWithParams(array $params, bool $merge = true)
 * @method static array parseQueryString(?string $queryString = null)
 * @method static string buildQueryString(array $params)
 * @method static string getScheme()
 * @method static bool isSecure()
 * @method static string getHost()
 * @method static int getPort()
 * @method static bool isMethod(string $method)
 * @method static bool isGet()
 * @method static bool isPost()
 * @method static bool isPut()
 * @method static bool isPatch()
 * @method static bool isDelete()
 * @method static bool isSafe()
 * @method static bool isIdempotent()
 * @method static string getClientIp()
 * @method static string getUserAgent()
 * @method static string getReferer()
 * @method static array getHeaders()
 * @method static string|null getHeader(string $name)
 * @method static string|null getCookie(string $name)
 * @method static array getCookies()
 * @method static array all()
 * @method static mixed input(string $key, mixed $default = null)
 * @method static bool has(string $key)
 * @method static array only(array $keys)
 * @method static array except(array $keys)
 * @method static bool isEmpty(string $key)
 * @method static bool filled(string $key)
 * @method static array getQueryParams()
 * @method static array getPostData()
 * @method static mixed getJsonData(bool $assoc = true)
 * @method static bool isJson()
 * @method static bool acceptsJson()
 * @method static bool acceptsHtml()
 * @method static array getAcceptedContentTypes()
 * @method static array getFiles()
 * @method static bool hasFiles()
 * @method static array|null getFile(string $name)
 * @method static bool isValidUpload(string $name)
 * @method static int getFileSize(string $name)
 * @method static string getFileExtension(string $name)
 * @method static string getFileMimeType(string $name)
 * @method static bool isAjax()
 * @method static bool isMobile()
 * @method static bool isBot()
 * @method static bool isPrefetch()
 * @method static int getContentLength()
 * @method static string getContentType()
 * @method static string getMimeType()
 * @method static string getCharset()
 * @method static bool isMultipart()
 * @method static bool isFormUrlEncoded()
 * @method static string|null getBearerToken()
 * @method static array|null getBasicAuth()
 * @method static string getPreferredLanguage(array $supportedLanguages = [])
 * @method static array getAcceptedLanguages()
 * @method static string|null getEtag()
 * @method static int|null getIfModifiedSince()
 * @method static string getInputData()
 * 
 * @see \Core\Services\HttpService
 */
class Http extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HttpInterface::class;
    }
}
