<?php
namespace Codem\DomainValidation;
use Exception;
use SS_Log;

/**
 * The Cloudflare DNS over HTTPS (DOH) validator can perform various DNS looksup on domains and domain parts of email addresses
 * Note that their specification follows Google's - "For lack of an agreed upon JSON schema for DNS over HTTPS in the IETF, Cloudflare has chosen to follow the same schema as Google’s DNS over HTTPS resolver." ... but that there is no accepted standard at the moment
 * @see https://developers.cloudflare.com/1.1.1.1/dns-over-https/json-format/
 */
class CloudflareDnsOverHttps extends AbstractDomainValidator {

	protected $domain;
	protected $protocol = "https";
	protected $host = "cloudflare-dns.com";
	protected $path = "/dns-query";

	/**
	 * @returns GuzzleHttp\Psr7\Stream
	 */
	public function performLookup($type = 'MX') {
		if(!$this->domain) {
			throw new Exception("No domain provided for {$type} lookup");
		}

		try {

			// will throw an Exception
			$response = $this->doGet([
				'type' => $type,
				'name' => $this->domain,
			],  ['headers' => [
                'Accept' => 'application/dns-json'
            ]]);

			$body = (string)$response->getBody();
			if(!$body) {
				throw new Exception("CloudflareDnsOverHttps request returned empty body for {$this->domain}/{$type}");
			}
			$decoded = json_decode($body, false);

			if(!isset($decoded->Status)) {
				throw new Exception("No 'Status' response from Cloudflare for {$this->domain}/{$type}");
			}

			// https://www.iana.org/assignments/dns-parameters/dns-parameters.xhtml#dns-parameters-6
			if($decoded->Status != 0) {
				throw new Exception("Cloudflare responded with a non-zero Status response for {$this->domain}/{$type}");
			}

			if(!isset($decoded->Answer)) {
				throw new Exception("Cloudflare responded without an answer for {$this->domain}/{$type}");
			}

			return $decoded->Answer;


		} catch (Exception $e) {
			$error = "CloudflareDnsOverHttps lookup failed with error: {$e->getMessage()} for {$this->domain}/{$type}. Exception=" . get_class($e);
			SS_Log::log($error, SS_Log::INFO);
		}

		return false;

	}

}
