<?php
namespace Codem\DomainValidation;
/**
 * EditableValidatedDomainField
 *
 * Allow users to define a validating editable domain validator field for a UserDefinedForm
 */

class EditableValidatedDomainField extends \EditableFormField {
	private static $singular_name = 'Text Field with DNS validation';

    private static $plural_name = 'Text Fields with DNS validation';

    private static $has_placeholder = true;

    public function getFormField()
    {
        $field = ValidatedDomainField::create($this->Name, $this->EscapedTitle, $this->Default)
            ->setFieldHolderTemplate('UserFormsField_holder')
            ->setTemplate('UserFormsField');

        $this->doUpdateFormField($field);

        return $field;
    }
}