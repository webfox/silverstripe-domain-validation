<?php

namespace Codem\DomainValidation\Tests;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Validator;

/**
 * Validator for test
 */
class DomainValidation_Validator extends Validator implements TestOnly
{
    public function javascript()
    {
    }

    public function php($data)
    {
    }
}
