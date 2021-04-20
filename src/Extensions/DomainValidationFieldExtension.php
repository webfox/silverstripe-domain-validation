<?php
namespace Codem\DomainValidation;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\Validator;

/**
 * Provides common methods for domain validation fields
 */
class FieldExtension extends Extension
{
    public function addCustomDnsCheck($dns_check)
    {
        $this->owner->custom_dns_checks[$dns_check] = $dns_check;
        return $this->owner;
    }

    public function clearCustomDnsChecks()
    {
        $this->owner->custom_dns_checks = [];
        return $this->owner;
    }

    public function addDnsClient(AbstractDomainValidator $validator)
    {
        $this->owner->custom_clients[get_class($validator)] = $validator;
        return $this->owner;
    }

    public function clearDnsClients()
    {
        $this->owner->custom_clients = [];
        return $this->owner;
    }

    /**
     * Add a validation error when no Domain Validators can be found
     */
    public function noValidators(Validator $validator, $type = 'value')
    {
        $message = sprintf(
            _t('DomainValidation.CANNOT_DOMAIN_VALIDATE', "Sorry, we could not validate the %s '%s'"),
            $type,
            $this->owner->value
        );
        $validator->validationError(
            $this->owner->name,
            $message,
            'validation'
        );
        return false;
    }

    /**
     * Returns {@link Codem\DomainValidation\AbstractDomainValidator} instances to use for validation
     * @returns mixed
     */
    public function getDnsClients(Validator $validator, $type)
    {
        if (!empty($this->owner->custom_clients)) {
            // any custom validators set override domain_validators in config
            $validators = $this->owner->custom_clients;
        } else {
            $validators = $this->owner->config()->get('dns_clients');
        }

        if (!is_array($validators) || empty($validators)) {
            return $this->owner->noValidators($validator, $type);
        }

        $domain_validators = [];
        foreach ($validators as $domain_validator) {
            $inst = new $domain_validator;
            if (!($inst instanceof AbstractDomainValidator)) {
                continue;
            }
            $domain_validators[get_class($inst)] = $inst;
        }

        // check for no validators
        if (empty($domain_validators)) {
            return $this->owner->noValidators($validator, $type);
        }

        return $domain_validators;
    }

    /**
     * Returns the DNS checks to perform
     * @returns mixed
     */
    public function getDnsChecks(Validator $validator, $lang_type)
    {
        if (!empty($this->owner->custom_dns_checks)) {
            // any custom DNS checks set will override dns_checks in config
            $dns_checks = $this->owner->custom_dns_checks;
        } else {
            $dns_checks = $this->owner->config()->get('dns_checks');
        }
        if (!is_array($dns_checks) || empty($dns_checks)) {
            $message = sprintf(
                _t('DomainValidation.NO_CHECKS', "Sorry, we could not validate the %s '%s' at this time"),
                $lang_type,
                $this->owner->value
            );
            $validator->validationError(
                $this->owner->name,
                $message,
                'validation'
            );
            return false;
        }
        $dns_checks = array_unique($dns_checks);
        return $dns_checks;
    }

    /**
     * Perform all DNS checks on the field value using all Domain Validators
     * @param string $domain  the domain to check
     * @param \Validator $validator
     * @param string $lang_type language string for validation
     */
    public function performDnsChecks($domain, Validator $validator, $lang_type)
    {
        $dns_checks = $this->owner->getDnsChecks($validator, $lang_type);
        $domain_validators = $this->owner->getDnsClients($validator, $lang_type);
        $answers = [];

        foreach ($domain_validators as $domain_validator) {
            $domain_validator->setDomain($domain); // set a domain by email address
            foreach ($dns_checks as $dns_check) {
                // TODO response can be an empty array
                $answer = $domain_validator->performLookup($dns_check);
                if ($answer && !empty($answer)) {
                    $answers[$dns_check] = $answer;
                }
            }
        }

        return $answers;
    }

    /**
     * Perform MX checks on the field value using all Domain Validators
     * @param string $domain  the domain to check
     * @param \Validator $validator
     * @param string $lang_type language string for validation
     */
    public function performMxRecordCheck($domain, Validator $validator, $lang_type)
    {
        $domain_validators = $this->owner->getDnsClients($validator, $lang_type);
        $answers = [];
        foreach ($domain_validators as $domain_validator) {
            $domain_validator->setDomain($domain); // set a domain by email address
            $answer = $domain_validator->performLookup('MX');
            if ($answer && !empty($answer)) {
                $answers['MX'] = $answer;
            }
        }

        return $answers;
    }

    /**
     * Read this first: https://en.wikipedia.org/wiki/Email_address
     * @param string $email_address accepted values are anything with an @
     * @returns mixed
     */
    public function getDomainByEmailAddress($email_address)
    {
        if (strpos($email_address, "@") === false) {
            return false;
        }
        $parts = explode("@", $email_address);
        if (empty($parts)) {
            return false;
        }
        return array_pop($parts);
    }

    /**
     * As there is no spec for response formats
     * Use {@link getAnswers} to get raw answers from services
     * At the moment Cloudflare uses the Google response schema, but this may change, use this method to get responses from checks.
     * https://developers.google.com/speed/public-dns/docs/dns-over-https
     * https://developers.cloudflare.com/1.1.1.1/dns-over-https/json-format/
     */
    public function getResults()
    {
        return $this->owner->getAnswers();
    }
}
