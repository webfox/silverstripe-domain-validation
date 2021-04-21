<?php
namespace Codem\DomainValidation\Tests;

use Codem\DomainValidation\MxValidatedEmailField;
use Codem\DomainValidation\ValidatedDomainField;
use SilverStripe\Dev\FunctionalTest;
use PHPUnit_Framework_AssertionFailedError;
use Exception;

/**
 * Test validation of various fields
 */
class FieldValidationTest extends FunctionalTest
{
    protected $valid_email_address= "devnull+gmailthing@codem.com.au";// valid domain has MX
    protected $invalid_mx_address = "test@test123.codem.com.au";// valid domain that has no MX
    protected $invalid_domain_email_address = "test@example.comau";// typo on the domain, but a valid email address

    protected $valid_domain = "codem.com.au";
    protected $valid_example_domain = "example.com";
    protected $invalid_domain = "example";

    public function testEmailValidation()
    {
        $emails = [
            $this->valid_email_address => true,
            $this->invalid_mx_address => false,
            $this->invalid_domain_email_address => false,
        ];

        foreach ($emails as $email => $expected_result) {
            $field = new MxValidatedEmailField("TestEmailAddress");
            $field->setValue($email);

            $validator = new DomainValidation_Validator();
            try {
                $result = $field->validate($validator);
                $error_string = "";
                if (!$result) {
                    $errors = $validator->getErrors();
                    if (!empty($errors)) {
                        foreach ($errors as $error) {
                            $error_string .= $error['message'] . " ";
                        }
                    }
                }
                // assert that what we expect
                $this->assertEquals($expected_result, $result, "Failed on email {$email} error:" . trim($error_string));
            } catch (Exception $e) {
                if ($expected_result) {
                    $this->assertTrue(false, "Email {$email} was expected to pass but failed and validation threw an exception: {$e->getMessage()}");
                }
            }
        }
    }

    public function testDomainValidation()
    {
        $domains = [
            $this->valid_domain => true,
            $this->valid_example_domain => true,
            $this->invalid_domain => false,
        ];

        foreach ($domains as $domain => $expected_result) {
            $field = new ValidatedDomainField("TestDomain");
            $field->setValue($domain);

            $validator = new DomainValidation_Validator();
            try {
                $result = $field->validate($validator);
                $error_string = "";
                if (!$result) {
                    $errors = $validator->getErrors();
                    if (!empty($errors)) {
                        foreach ($errors as $error) {
                            $error_string .= $error['message'] . " ";
                        }
                    }
                }
                // assert that what we expect
                $this->assertTrue($result == $expected_result, trim($error_string));
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                // catch assertTrue exception above
                throw $e;
            } catch (Exception $e) {
                // success should not throw an Exception
                if ($expected_result) {
                    $this->assertTrue(false, "Domain {$domain} was expected to pass but failed and validation threw an exception: {$e->getMessage()}");
                }
            }
        }
    }
}
