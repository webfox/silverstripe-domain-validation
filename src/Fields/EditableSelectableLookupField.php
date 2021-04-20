<?php
namespace Codem\DomainValidation;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\UserForms\Model\EditableFormField;

/**
 * EditableSelectableLookupField
 *
 * Allow users to define a validating editable domain validator field with applicable DNS checks
 */

class EditableSelectableLookupField extends EditableFormField
{
    private static $singular_name = 'Text Field with custom DNS validation';

    private static $plural_name = 'Text Fields with custom DNS validation';

    private static $has_placeholder = true;

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'EditableSelectableLookupField';

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
    private static $defaults = [
        'StrictCheck' => 0,
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $title = _t('DomainValidation.CHECK_INSTRUCTIONS_MULTI', 'Selectable DNS checks. Separate each value by a comma.');
        $description = _t('DomainValidation.CHECK_EXAMPLE_MULTI', "Any supported DNS record type (https://en.wikipedia.org/wiki/List_of_DNS_record_types)");

        $fields->addFieldToTab("Root.Main", TextField::create('DnsChecks', $title)->setDescription($description));

        $title = _t('DomainValidation.STRICT_CHECK_INSTRUCTIONS', 'Perform a strict check.');
        $description = _t('DomainValidation.STRICT_CHECK_EXAMPLE', "When checked, all DNS checks must return a valid, non-empty response for the field to validate.");
        $fields->addFieldToTab("Root.Main", CheckboxField::create('StrictCheck', $title)->setDescription($description));

        return $fields;
    }

    public function getDnsConfiguredChecks()
    {
        $dns_checks = trim($this->DnsChecks);
        $result = [];
        if (strpos($dns_checks, ",") !== false) {
            $checks = explode(",", $dns_checks);
            foreach ($checks as $check) {
                $result[$check] = $check;
            }
        } else {
            // only one
            $result[$dns_checks] = $dns_checks;
        }
        return $result;
    }

    public function getFormField()
    {
        $checks = $this->getDnsConfiguredChecks();
        $field = SelectableLookupField::create(null)
            ->setFields($this->Name, $this->Value, $checks, (bool) $this->StrictCheck)
            ->setFieldHolderTemplate('UserFormsGroupField_holder')
            ->setTemplate('UserFormsField');
        $field->setName($this->Name);
        $this->doUpdateFormField($field);
        return $field;
    }

    public function getValueFromData($data)
    {
        $entries = (isset($data[$this->Name])) ? $data[$this->Name] : false;
        if ($entries) {
            return json_encode($entries);
        }
        return "";
    }
}
