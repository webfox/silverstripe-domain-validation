<?php
namespace Codem\DomainValidation;
/**
 * The Google DNS over HTTPS (DOH) validator can perform various DNS looksup on domains and domain parts of email addresses
 * @see https://developers.google.com/speed/public-dns/docs/dns-over-https
 */
class GoogleDnsOverHttps extends AbstractDomainValidator {
	
	protected $domain;
	protected $protocol = "https";
	protected $host = "dns.google.com";
	protected $path = "/resolve";
	
	/**
	 * @returns GuzzleHttp\Psr7\Stream
	 */
	public function performLookup($type = 'MX') {
		if(!$this->domain) {
			throw new \Exception("No domain provided for lookup");
		}
		
		try {
			
			// will throw an Exception
			$response = $this->doGet([
				'type' => $type,
				'name' => $this->domain,
			]);
			
			$body = (string)$response->getBody();
			if(!$body) {
				throw new \Exception("GoogleDnsOverHttps request returned empty body");
			}
			$decoded = json_decode($body, false);
			
			if(!isset($decoded->Status)) {
				throw new \Exception("No 'Status' response from Cloudflare");
			}
			
			// https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#dns-parameters-6
			if($decoded->Status != 0) {
				throw new \Exception("Google DNS resolver responded with a non-zero Status response");
			}
			
			if(!isset($decoded->Answer)) {
				throw new \Exception("Google DNS resolver responded without an answer");
			}
			
			return $decoded->Answer;
			
			
		} catch (\Exception $e) {
			$error = "GoogleDnsOverHttps lookup failed with error: {$e->getMessage()}. Exception=" . get_class($e);
			\SS_Log::log($error, \SS_Log::INFO);
		}
		
		return false;
		
	}

}