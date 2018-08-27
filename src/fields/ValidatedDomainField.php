<?php
namespace Codem\DomainValidation;
use TextField;
use Exception;
use SS_Log;

/**
 * A field that checks a value (a purported domain) against various DNS records
 * This field does not check the existence of a domain name, as domain names can exist without any DNS records e.g an intranet not in DNS
 */
class ValidatedDomainField extends TextField implements FieldInterface {

	public $custom_dns_checks = [];
	public $custom_clients = [];

	/**
	 * {@inheritdoc}
	 */
	public function Type() {
		return 'domainvalidated text';
	}

	/**
	 * @var array
	 * @note one or more checks to perform. Can be A, AAAA, CNAME or anything else really
	 */
	private static $checks = [
		'A', // by default only do an A record check
	];

	/**
	 * @var array
	 * @note one or more domain validation class that extends Codem\DomainValidation\AbstractDomainValidator
	 */
	private static $dns_clients = [
		'Codem\DomainValidation\CloudflareDnsOverHttps',
	];

	private $answers = [];
	private $be_strict = false;
	public function getAnswers() {
		return $this->answers;
	}

	/**
	 * If true, any empty response will cause validate to return false
	 */
	public function beStrict($is) {
		$this->be_strict = $is;
		return $this;
	}

	public function validate($validator) {
		$this->answers = [];
		$lang_type = _t('DomainValidation.DOMAIN', 'domain');
		if($this->value == "") {
			$validator->validationError(
				$this->name,
				sprintf(_t('DomainValidation.NO_DOMAIN_VALUE', "Please provide a %s"), $lang_type),
				'validation'
			);
			return false;
		}
		$result = parent::validate($validator);
		if(!$result) {
			return false;
		}

		// assume that it's not valid
		$validated = false;
		try {
			$result = $this->performDnsChecks($this->value, $validator, $lang_type);
			$this->answers = $result;
			if($this->be_strict) {
				$dns_checks = $this->getDnsChecks($validator, $lang_type);
				if(count($dns_checks) != count($this->answers)) {
					throw new Exception("Domain validation lookup did not return answers for all request checks");
				} else {
					$validated = true;
				}
			} else if(empty($this->answers)) {
				// no results returned at all :(
				throw new Exception("No answers for requested DNS checks");
			} else {
				// not strict OR string AND all results returned
				$validated = true;
			}
		} catch (Exception $e) {
			SS_Log::log("ERROR: " . $e->getMessage(), SS_Log::INFO);
			$message = sprintf(
						_t('DomainValidation.NO_MX_RECORD', "The domain '%s' could not be validated"),
						$this->value
			);
		}

		if(!$validated) {
			$validator->validationError(
				$this->name,
				$message,
				'validation'
			);
		}
		return $validated;

	}
}
