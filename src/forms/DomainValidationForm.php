<?php
namespace Codem\DomainValidation;

class DomainValidationForm extends \Form {
	
	public function __construct($controller, $name, \FieldList $fields, \FieldList $actions, $validator = null) {
		$fields = $this->getDomainValidationFields();
		$actions = $this->getDomainValidationActions();
		parent::__construct($controller, $name, $fields, $actions, $validator);
	}
	
	protected function getDomainValidationFields() {
		
		$checks = [
			"A" => "A",
			"AAAA" => "AAAA",
			"CNAME" => "CNAME",
			"MX" => "MX"
		];
		
		$fields = \FieldList::create(
			MxValidatedEmailField::create('ValidateEmailDefault', 'E-mail')
									->setValue('example@google.com')
									->setDescription('Validate MX for e-mail using default configuration'),
									
			MxValidatedEmailField::create('ValidateEmailAlt', 'E-mail alt. check')
									->setValue('example@google.com')
									->setDescription('Validate MX for e-mail using Google DNS over HTTPS')
									->addDomainValidator( new GoogleDnsOverHttps() ),
									
			ValidatedDomainField::create('ValidateDomainDefault', 'Domain')
									->setValue('google.com')
									->setDescription('Validate domain using default configuration'),
									
			ValidatedDomainField::create('ValidateDomainAlt', 'Domain')
									->setValue('google.com')
									->setDescription('Validate domain using Google DNS over HTTPS')
									->addDomainValidator( new GoogleDnsOverHttps() ),
									
			ValidatedDomainField::create('ValidateDomainAltAAAA', 'Domain')
									->setValue('google.com')
									->setDescription('Validate AAAA record for domain using Google DNS over HTTPS')
									->clearCustomDnsChecks()
									->addCustomDnsCheck('AAAA')
									->addDomainValidator( new GoogleDnsOverHttps() ),
									
			SelectableLookupField::create()
									->setDescription('Validate AAAA record for domain using Google DNS over HTTPS')
									->setFields('SelectableValidation', 'google.com', $checks, false)
		);
		
		return $fields;
	}
	
	protected function getDomainValidationActions() {
		$fields = \FieldList::create(
			\FormAction::create("doDomainValidation")->setTitle("Check all fields")
		);
		return $fields;
	}
}