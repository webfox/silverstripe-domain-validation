<?php
namespace Codem\DomainValidation;
use SilverStripe\UserForms\Model\EditableFormField\EditableEmailField;

/**
 * EditableMxValidatedEmailField
 *
 * Allow users to define a validating editable email field for a UserDefinedForm
 */

class EditableMxValidatedEmailField extends EditableEmailField {
    private static $singular_name = 'Email Field with MX validation';

    private static $plural_name = 'Email Fields with MX validation';

    private static $has_placeholder = true;

    public function getFormField() {
        $field = MxValidatedEmailField::create($this->Name, $this->EscapedTitle, $this->Default)
            ->setFieldHolderTemplate('UserFormsField_holder')
            ->setTemplate('UserFormsField');

        $this->doUpdateFormField($field);

        return $field;
    }
}
