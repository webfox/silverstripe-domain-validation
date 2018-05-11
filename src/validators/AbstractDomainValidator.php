<?php
namespace Codem\DomainValidation;
use League\Flysystem\Adapter\Local as FlysystemAdapterLocal;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Kevinrob\GuzzleCache\Storage\FlysystemStorage;
use Kevinrob\GuzzleCache\CacheMiddleware;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;

/**
 * Provides common methods for performing lookups and defining method
 * @author James
 *
 * @package validatable-emailfield
 * @subpackage validators
 */
abstract class AbstractDomainValidator extends \Object {
	
	protected $domain;
	protected $protocol = "https";
	protected $host = "";
	protected $path = "";
	protected $timeout = 5;
	abstract public function performLookup($type);
	
	
	protected function getCacheDir() {
		$cachedir = TEMP_FOLDER . DIRECTORY_SEPARATOR . 'domainvalidation';
		return $cachedir;
	}
	
	/**
	 * Refer: http://guzzle3.readthedocs.io/plugins/cache-plugin.html
	 */
	protected function getCachePlugin() {
		$stack = HandlerStack::create();
		$stack->push(
			new CacheMiddleware(
				new PrivateCacheStrategy(
					new FlysystemStorage(
						new FlysystemAdapterLocal( $this->getCacheDir() )
						)
					)
				), 
				'cache'
		);
		// Add this middleware to the top with `push`
		$stack->push(new CacheMiddleware(), 'cache');
		return $stack;
	}
	
	/**
	 * Do a GET request
	 * @returns 
	 */
	protected function doGet(array $args) {
		$query = http_build_query($args);
		$client = new HttpClient([
			'timeout'  => $this->timeout,
			'handler' => $this->getCachePlugin()
		]);
		$url = $this->protocol . "://" . $this->host . $this->path . ($query ? "?{$query}" : "");
		$response = $client->get($url);
		// allow calling method to handle responses
		return $response;
	}
		
	public function setDomain($domain) {
		$this->domain = $domain;
	}
	
	/**
	 * @returns boolean
	 * @param string $compare e.g "1 aspmx.l.google.com." optional
	 * @returns mixed either the answer section, the compare answer or false if no answer
	 * Checks whether the current domain has at least one MX response. If $compare is set, an answer must equal that response
	 */
	public function hasMxRecord($compare = null) {
		if(!$this->domain) {
			return false;
		}
		$answers = $this->performLookup('MX');
		if(!empty($answers)) {
			if(is_null($compare)) {
				return $answers;
			}
			foreach($answers as $answer) {
				if(isset($answer->data) && $answer->data == $compare) {
					return $compare;	
				}
			}
		}
		return false;
	}
}