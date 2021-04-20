<?php
namespace Codem\DomainValidation;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\FlysystemStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use League\Flysystem\Adapter\Local as FlysystemAdapterLocal;
use SilverStripe\Core\Config\Configurable;

/**
 * Provides common methods for performing lookups and defining method
 * @author James
 *
 * @package validatable-emailfield
 * @subpackage validators
 */
abstract class AbstractDomainValidator
{
    use Configurable;

    private static $ttl = 0;

    protected $domain;
    protected $protocol = "https";
    protected $host = "";
    protected $path = "";
    protected $timeout = 5;
    abstract public function performLookup($type);

    protected function getCacheDir()
    {
        $cachedir = TEMP_FOLDER . DIRECTORY_SEPARATOR . 'domainvalidation';
        return $cachedir;
    }

    /**
     * Refer: http://guzzle3.readthedocs.io/plugins/cache-plugin.html
     */
    protected function getCachePlugin()
    {
        $stack = HandlerStack::create();
        $ttl = (int) $this->config()->get('ttl');
        if ($ttl > 0) {
            $stack->push(
                new CacheMiddleware(
                    new GreedyCacheStrategy(
                        new FlysystemStorage(
                            new FlysystemAdapterLocal($this->getCacheDir())
                        ),
                        $ttl
                    )
                ),
                'cache'
            );
        } else {
            $stack->push(
                new CacheMiddleware(
                    new PrivateCacheStrategy(
                        new FlysystemStorage(
                            new FlysystemAdapterLocal($this->getCacheDir())
                        )
                    )
                ),
                'cache'
            );
        }
        // Add this middleware to the top with `push`
        $stack->push(new CacheMiddleware(), 'cache');
        return $stack;
    }

    /**
     * Do a GET request
     * @returns
     */
    protected function doGet(array $args)
    {
        $query = http_build_query($args);
        $client = new HttpClient([
            'timeout' => $this->timeout,
            'handler' => $this->getCachePlugin(),
        ]);
        $url = $this->protocol . "://" . $this->host . $this->path . ($query ? "?{$query}" : "");
        $response = $client->get($url);
        // $this->logHitMiss($response, $url);
        // allow calling method to handle responses
        return $response;
    }

    private function logHitMiss($response, $url)
    {
        if ($response && ($cache_info = $response->getHeader(CacheMiddleware::HEADER_CACHE_INFO))) {
            Log::log("CACHE|{$cache_info[0]}|{$url}", 'INFO');
        }
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @returns boolean
     * @param string $compare e.g "1 aspmx.l.google.com." optional
     * @returns mixed either the answer section, the compare answer or false if no answer
     * Checks whether the current domain has at least one MX response. If $compare is set, an answer must equal that response
     */
    public function hasMxRecord($compare = null)
    {
        if (!$this->domain) {
            return false;
        }
        $answers = $this->performLookup('MX');
        if (!empty($answers)) {
            if (is_null($compare)) {
                return $answers;
            }
            foreach ($answers as $answer) {
                if (isset($answer->data) && $answer->data == $compare) {
                    return $compare;
                }
            }
        }
        return false;
    }
}
