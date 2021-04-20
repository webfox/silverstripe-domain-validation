<?php
namespace Codem\DomainValidation;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\UserForms\Model\EditableFormField;

/**
 * EditableValidatedDomainField
 *
 * Allow users to define a validating editable domain validator field for a UserDefinedForm
 */

class EditableValidatedDomainField extends EditableFormField
{
    private static $singular_name = 'Text Field with DNS validation';

    private static $plural_name = 'Text Fields with DNS validation';

    private static $has_placeholder = true;

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'DnsCheck' => 'Varchar(16)', // the single DNS check to perform
        'StrictCheck' => 'Boolean',
    ];

    /**
     * Add default values to database
     * @var array
     */
    private static $defaults = [
        'DnsCheck' => 'A',
        'StrictCheck' => 0,
    ];

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'EditableValidatedDomainField';

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->DnsCheck = trim($this->DnsCheck);
        if ($this->DnsCheck == "") {
            $this->DnsCheck = self::$defaults['DnsCheck'];
        }
        $this->DnsCheck = strtoupper($this->DnsCheck);
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $title = _t('DomainValidation.CHECK_INSTRUCTIONS', 'DNS check to perform');
        $description = _t('DomainValidation.CHECK_EXAMPLE', "Any supported DNS record type (https://en.wikipedia.org/wiki/List_of_DNS_record_types), default 'A'");

        $fields->addFieldToTab("Root.Main", TextField::create('DnsCheck', $title)->setDescription($description));

        $title = _t('DomainValidation.STRICT_CHECK_INSTRUCTIONS', 'Perform a strict check.');
        $description = _t('DomainValidation.STRICT_CHECK_EXAMPLE', "When checked, the DNS check performed must return a valid, non-empty response for the field to validate.");
        $fields->addFieldToTab("Root.Main", CheckboxField::create('StrictCheck', $title)->setDescription($description));

        return $fields;
    }

    public function DefaultDnsCheck()
    {
        return "A";
    }

    public function getValueFromData($data)
    {
        $domain = (isset($data[$this->Name])) ? $data[$this->Name] : "";
        if ($domain) {
            $data = [
                'domain' => $domain,
                'dnscheck' => $this->DnsCheck,
            ];
            return json_encode($data);
        }
        return "";
    }

    public function getFormField()
    {
        $check = $this->DnsCheck ? $this->DnsCheck : $this->DefaultDnsCheck();
        $field = ValidatedDomainField::create($this->Name, $this->EscapedTitle, $this->Default)
            ->setFieldHolderTemplate('UserFormsField_holder')
            ->setTemplate('UserFormsField')
            ->setAttribute('data-record-type', $this->DnsCheck)
            ->beStrict($this->StrictCheck == 1)
            ->clearCustomDnsChecks()
            ->addCustomDnsCheck($this->DnsCheck);

        $this->doUpdateFormField($field);
        return $field;
    }
}
