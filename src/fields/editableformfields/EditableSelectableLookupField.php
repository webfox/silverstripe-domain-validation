<?php
namespace Codem\DomainValidation;
use TextField;
use CheckboxField;

/**
 * EditableSelectableLookupField
 *
 * Allow users to define a validating editable domain validator field with applicable DNS checks
 */

class EditableSelectableLookupField extends \EditableFormField {
	private static $singular_name = 'Text Field with custom DNS validation';

    private static $plural_name = 'Text Fields with custom DNS validation';

    private static $has_placeholder = true;

	/**
	 * Database fields
	 * @var array
	 */
	private static $db = [
		'DnsChecks' => 'Varchar(255)',
		'StrictCheck' => 'Boolean',
	];

	/**
	 * Add default values to database
	 * @var array
	 */
	private static $defaults = array(
		'StrictCheck' => 0
	);

	/**
	 * CMS Fields
	 * @return FieldList
	 */
	public function getCMSFields()
	{
		$fields = parent::getCMSFields();

		$title = _t('DomainValidation.CHECK_INSTRUCTIONS', 'Perform these DNS checks. Separate each value by a comma.');
		$description = _t('DomainValidation.CHECK_EXAMPLE', "Example: A,AAAA,MX");

		$fields->addFieldToTab( "Root.Main", TextField::create('DnsChecks', $title)->setDescription($description) );

		$title = _t('DomainValidation.STRICT_CHECK_INSTRUCTIONS', 'Perform a strict check.');
		$description = _t('DomainValidation.STRICT_CHECK_EXAMPLE', "When checked, all DNS checks must return a valid, non-empty response for the field to validate.");
		$fields->addFieldToTab( "Root.Main", CheckboxField::create('StrictCheck', $title)->setDescription($description) );

		return $fields;
	}

	public function getDnsConfiguredChecks() {
		$dns_checks = trim($this->DnsChecks);
		$result = [];
		if(strpos($dns_checks, ",") !== false) {
			$checks = explode(",", $dns_checks);
			foreach($checks as $check) {
				$result[ $check ] = $check;
			}
		}
		return $result;
	}

	public function getFormField()
	{
		$checks = $this->getDnsConfiguredChecks();
		$field = SelectableLookupField::create(null)
					->setFields($this->Name, $this->Value, $checks, (bool)$this->StrictCheck)
					->setFieldHolderTemplate('UserFormsGroupField_holder')
					->setTemplate('UserFormsField');
		$field->setName($this->Name);
		$this->doUpdateFormField($field);
		return $field;
	}
}
