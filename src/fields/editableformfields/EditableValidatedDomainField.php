<?php
namespace Codem\DomainValidation;
use SilverStripe\UserForms\Model\EditableFormField;

/**
 * EditableValidatedDomainField
 *
 * Allow users to define a validating editable domain validator field for a UserDefinedForm
 */

class EditableValidatedDomainField extends EditableFormField {
	private static $singular_name = 'Text Field with DNS validation';

    private static $plural_name = 'Text Fields with DNS validation';

    private static $has_placeholder = true;

		/**
		 * Defines the database table name
		 * @var string
		 */
		private static $table_name = 'EditableValidatedDomainField';

    public function getFormField()
    {
        $field = ValidatedDomainField::create($this->Name, $this->EscapedTitle, $this->Default)
            ->setFieldHolderTemplate('UserFormsField_holder')
            ->setTemplate('UserFormsField');

        $this->doUpdateFormField($field);

        return $field;
    }
}
