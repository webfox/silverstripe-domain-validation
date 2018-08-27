<?php
namespace Codem\DomainValidation;
use CompositeField;
use ListboxField;
use FieldList;

/**
 * Provides a field containing a text field that will hold a domain and a selector for one or more DNS checks
 */
class SelectableLookupField extends CompositeField implements FieldInterface {

	public function setFields($name, $value, $dns_checks, $strict_checking = false) {
		var_dump($name);
		$this->setName($name);
		$domain_field_title = _t("DomainValidation.DOMAIN", "Domain");
		$domain_field = ValidatedDomainField::create($this->name . "[domain]", $domain_field_title, $value);
		$domain_field->beStrict($strict_checking);
		$lookup_field_title = _t("DomainValidation.CHECKS_TO_PERFORM", "Check");
		$lookup_field = ListboxField::create($this->name . "[lookup]", $lookup_field_title, $dns_checks)->setMultiple(true);
		$children = FieldList::create(
			$domain_field,
			$lookup_field
		);
		parent::__construct($children);
		return $this;
	}
	/**
	 * {@inheritdoc}
	 */
	public function Type() {
		return 'domainvalidation text';
	}



	private $answers = [];
	public function getAnswers() {
		$name = $this->getName();
		$answers = [];
		if($domain_field = $this->children->dataFieldByName($name . "[domain]")) {
			$answers = $domain_field->getAnswers();
		}
		return $answers;
	}

	/**
	 * Validate this field
	 *
	 * @param Validator $validator
	 * @return bool
	 */
	public function validate($validator) {

		$dns_checks_requested = [];
		$name = $this->getName();

		$lookup_field = $this->children->dataFieldByName($name . "[lookup]");
		$domain_field = $this->children->dataFieldByName($name . "[domain]");
		if(!$lookup_field || !$domain_field) {
			$validator->validationError(
				$this->name,
				_t('DomainValidation.MISSING_FIELDS', "Sorry, this request cannot be processed due to an error."),
				'validation'
			);
			return false;
		}

		$domain_field_value = $domain_field->Value();
		if($domain_field_value == "") {
			$validator->validationError(
				$name . "[domain]",
				sprintf(_t('DomainValidation.NO_DOMAIN_VALUE', "Please provide a %s"),  _t('DomainValidation.DOMAIN', 'domain')),
				'validation'
			);
			return false;
		}

		$dns_checks_requested = $lookup_field->Value();
		$lookup_field_title = $lookup_field->Title();
		if(!is_array($dns_checks_requested) || empty($dns_checks_requested) || !$dns_checks_requested) {
			$validator->validationError(
				$name . "[lookup]",
				sprintf(_t('DomainValidation.MISSING_CHECKS', "Please select at least one value from the '%' field."), $lookup_field_title),
				'validation'
			);
			return false;
		}

		// add checks to the domain field, prior to validation
		foreach($dns_checks_requested as $dns_check) {
			$domain_field->addCustomDnsCheck($dns_check);
		}

		if(empty($dns_checks_requested)) {
			$validator->validationError(
				$name . "[lookup]",
				_t('DomainValidation.NO_CHECKS_SELECTED', "Please select at least one DNS check"),
				'validation'
			);
			return false;
		}

		$valid = true;
		foreach($this->children as $idx => $child) {
			$valid = ($child && $child->validate($validator) && $valid);
		}

		if(!$valid) {

			// check what validation error we need to show
			$answers = $this->getAnswers();
			$answer_keys = array_keys($answers);
			$domain = $domain_field->Value();
			if(empty($answer_keys)) {
				$validator->validationError(
					$name . "[domain]",
					sprintf(
						_t('DomainValidation.ALL_CHECKS_FAILED', "The domain '%s' returned no matching DNS records."),
						$domain
					),
					'validation'
				);
			} else {
				$answer_keys_string = implode(",", $answer_keys);
				$validator->validationError(
					$name . "[domain]",
					sprintf(
						_t('DomainValidation.SOME_CHECKS_FAILED', "Some of the DNS checks failed for the domain '%s'. The domain has records for the following types: %s"),
						$domain,
						$answer_keys_string
					),
					'validation'
				);
			}
		}

		return $valid;
	}
}
