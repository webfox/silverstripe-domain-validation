<?php
namespace Codem\DomainValidation;
use Form;
use FieldList;
use FormAction;
use Exception;
use ValidationException;
use Session;

/*
 * DomainLookupForm performs multiple DNS checks of one domain value using one DNS client,
 * then returns the results.
 * Sample controller:


   class DomainLookupPage_Controller extends Page_Controller {

     private static $allowed_actions = array(
       'DomainLookup',
       'results',
     );

     public function Form() {
       return $this->DomainLookUp($this->getRequest());
     }

     public function DomainLookup(SS_HTTPRequest $request) {
       $form = new DomainLookupForm($this, 'DomainLookup', FieldList::create(), FieldList::create());
       return $form;
     }

     public function results(SS_HTTPRequest $request) {
       $answers = Session::get("DomainValidation.Codem_DomainValidation_DomainLookupForm_DomainLookup.answers");
       // handle the answers
     }

   }


 */
class DomainLookupForm extends Form {

  /**
   * @var array
   * Default checks to perform, override for your own use
   */
  private static $checks = [
    "A",
  ];

  private $answers;

  /**
   * @var string
   * A single client that extends {@link Codem\DomainValidation\AbstractDomainValidator}
   */
  private static $dns_client = 'Codem\DomainValidation\CloudflareDnsOverHttps';

  private static $allowed_actions = [
    'doDomainValidation'
  ];

  public function __construct($controller, $name, FieldList $fields, FieldList $actions, $validator = null) {
    $fields = $this->getDomainValidationFields();
    $actions = $this->getDomainValidationActions();
    parent::__construct($controller, $name, $fields, $actions, $validator);
  }

  protected function getDomainValidationFields() {
    $fields = FieldList::create(
      ValidatedDomainField::create('domain', _t('DomainValidation.DOMAIN', 'Domain'))
                          ->setAttribute('required','required')
    );
    $this->extend('updateDomainValidationFields', $fields);
    return $fields;
  }

  protected function getDomainValidationActions() {
    $fields = FieldList::create(
      FormAction::create("doDomainValidation", _t('DomainValidation.VALIDATE', 'Validate'))
    );
    $this->extend('updateDomainValidationActions', $fields);
    return $fields;
  }

  public function doDomainValidation(array $data, DomainLookupForm $form) {
    $this->extend('onBeforeDomainValidation', $data, $form);
    $this->performDnsChecks($data, $form);
    $this->extend('onAfterDomainValidation', $data, $form);
  }

  protected function performDnsChecks(array $data, DomainLookupForm $form) {
    Session::clear("DomainValidation.{$this->FormName()}.answers");

    $checks = $this->config()->get('checks');
    if(!is_array($checks) || empty($checks)) {
      throw new Exception("No DNS checks configured");
    }

    $client = $this->config()->get('dns_client');
    if(!$client) {
      throw new Exception("No DNS client configured");
    }

    $inst = new $client;
    if(!($inst instanceof AbstractDomainValidator)) {
      throw new Exception("Invalid DNS client configured");
    }

    if(empty($data['domain'])) {
      throw new ValidationException("No domain provided");
    }

    $this->answers = [];
    $inst->setDomain($data['domain']);// set a domain by email address
    foreach($checks as $check) {
      try {
        $answer = $inst->performLookup($check);
        if($answer && !empty($answer)) {
          $this->answers[ $check ] = $answer;
        }
      } catch (Exception $e) {}
    }
    $this->handleAnswers();
  }

  /**
   * handle how answers are stored/saved
   */
  protected function handleAnswers() {
    Session::set("DomainValidation.{$this->FormName()}.answers", $this->answers);
    return $this->getController()->redirect('results');
  }

}
