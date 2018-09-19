<?php
namespace Codem\DomainValidation;
use SilverStripe\Forms\EmailField;
use Exception;


/**
 * An Email field that does MX record validation, after the standard Email validation
 */
class MxValidatedEmailField extends EmailField implements FieldInterface {

	public $custom_dns_checks = [];
	public $custom_clients = [];

	/**
	 * @var array
	 * @note one or more domain validation class that extends Codem\DomainValidation\AbstractDomainValidator
	 */
	private static $dns_clients = [
		'Codem\DomainValidation\CloudflareDnsOverHttps',
	];

	private $answers = [];

	/**
	 * {@inheritdoc}
	 */
	public function Type() {
		return 'email text';
	}

	/**
	 * Returns answers, if any, of a domain validation check
	 */
	public function getAnswers() {
		return $this->answers;
	}

	/**
	 * Validate the email address value
	 */
	public function validate($validator) {
		$this->answers = [];
		$lang_type = _t('DomainValidation.EMAIL_ADDRESS', 'e-mail address');
		if($this->value == "") {
			$validator->validationError(
				$this->name,
				sprintf(_t('DomainValidation.NO_VALUE', "Please provide an %s"), $lang_type),
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
			$result = $this->performMxRecordCheck( $this->getDomainByEmailAddress($this->value), $validator, $lang_type);
			if(!$result) {
				throw new Exception("Domain validation lookup did not return answers for all request checks");
			}
			$this->answers = $result;
			$validated = true;
		} catch (Exception $e) {
			$message = sprintf(
							_t('DomainValidation.NO_MX_RECORD', "The e-mail address '%s' does not appear to be valid"),
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
