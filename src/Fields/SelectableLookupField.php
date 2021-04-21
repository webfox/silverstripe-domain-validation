<?php
namespace Codem\DomainValidation;

use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ListboxField;

/**
 * Provides a field containing a text field that will hold a domain and a selector for one or more DNS checks
 */
class SelectableLookupField extends CompositeField implements FieldInterface
{
    private $answers = [];

    public function setFields($name, $value, $dns_checks, $strict_checking = false)
    {
        $this->setName($name);
        $domain_field_title = _t("DomainValidation.DOMAIN", "Domain");
        $domain_field = ValidatedDomainField::create($this->name . "[domain]", $domain_field_title, $value);
        $domain_field->beStrict($strict_checking);
        $lookup_field_title = _t("DomainValidation.CHECKS_TO_PERFORM", "Check");
        $lookup_field = CheckboxSetField::create($this->name . "[lookup]", $lookup_field_title, $dns_checks);
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
    public function Type()
    {
        return 'domainvalidation text';
    }

    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        $this->answers = [];

        $dns_checks_requested = [];
        $name = $this->getName();

        $lookup_field = $this->children->dataFieldByName($name . "[lookup]");
        $domain_field = $this->children->dataFieldByName($name . "[domain]");
        if (!$lookup_field || !$domain_field) {
            $validator->validationError(
                $this->name,
                _t('DomainValidation.MISSING_FIELDS', "Sorry, this request cannot be processed due to an error."),
                'validation'
            );
            return false;
        }

        $domain_field_value = $domain_field->Value();

        if (!$domain_field->Required() && $domain_field_value == "") {
            // ignore
            return false;
        }

        if ($domain_field_value == "") {
            $validator->validationError(
                $name . "[domain]",
                _t(
                    'DomainValidation.NO_DOMAIN_VALUE',
                    "Please provide a domain"
                ),
                'validation'
            );
            return false;
        }

        $dns_checks_requested = $lookup_field->Value();
        $lookup_field_title = $lookup_field->Title();
        if (!is_array($dns_checks_requested) || empty($dns_checks_requested) || !$dns_checks_requested) {
            $validator->validationError(
                $name . "[lookup]",
                _t(
                    'DomainValidation.MISSING_CHECKS',
                    "Please select at least one value from the '{lookup_field_title}' field.",
                    [
                        'lookup_field_title' => $lookup_field_title
                    ]
                ),
                'validation'
            );
            return false;
        }

        // add checks to the domain field, prior to validation
        foreach ($dns_checks_requested as $dns_check) {
            $domain_field->addCustomDnsCheck($dns_check);
        }

        if (empty($dns_checks_requested)) {
            $validator->validationError(
                $name . "[lookup]",
                _t(
                    'DomainValidation.NO_CHECKS_SELECTED',
                    "Please select at least one DNS check"
                ),
                'validation'
            );
            return false;
        }

        // validate the domain field
        $valid = $domain_field->validate($validator);

        if (!$valid) {

            // check what validation error we need to show
            $this->answers = $domain_field->getAnswers();
            $answer_keys = array_keys($this->answers);
            $domain = $domain_field->Value();
            if (empty($answer_keys)) {
                $validator->validationError(
                    $name . "[domain]",
                    _t(
                        'DomainValidation.ALL_CHECKS_FAILED',
                        "The domain '{domain}' returned no matching DNS records.",
                        [
                            'domain' => $domain
                        ]
                    ),
                    'validation'
                );
            } else {
                $missing = array_diff($dns_checks_requested, $answer_keys);
                $validator->validationError(
                    $name . "[domain]",
                    _t(
                        'DomainValidation.SOME_CHECKS_FAILED',
                        "Some of the DNS checks failed for the domain '{domain}'. The domain is missing records for the following types: {missing}",
                        [
                            'domain' => $domain,
                            'missing' => implode(", ", $missing)
                        ]
                    ),
                    'validation'
                );
            }
        }

        return $valid;
    }
}
