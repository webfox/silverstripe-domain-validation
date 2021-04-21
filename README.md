# Domain Validation module for Silverstripe

This module provides a set of fields to allow domain lookup via DNS over HTTPS (DoH) services, currently Cloudflare and Google.

+ Validated Email Field - validates the domain part of a provided email address via MX lookup
+ Validated Domain Field - validates any domain via the configured record type (default: A)
+ Selectable Lookup Field -  validates any domain via multiple, selectable record types

Some APIs reject requests if a domain or domain part is invalid e.g user@examplecom or user@hotmailcom
With this module you can check values using DoH prior to submitting or saving them.

## Requirements
Silverstripe 4 / see composer.json

## Features
+ Plugs into Cloudflare DNS over HTTPS
+ Plugs into Google Public DNS over HTTPS
+ Does Caching based on TTL (Flysystem middleware)
+ Provides a Validatable Email Form Field (MX record check on domain part) for use in forms
+ Provides a Validatable Domain Form Field (A record check on domain)
+ Provides a Form Field for selecting which DNS records to check against a domain
+ Provides EditableFormFields for the ```silverstripe/userforms``` module

### Basic MX Validation
```
use Codem\DomainValidation\CloudflareDnsOverHttps;
...
$domain = "google.com"
$service = new CloudflareDnsOverHttps();
$service->setDomain($domain);
$answers = $service->performLookup('MX');
//OR shortcut
$service->hasMxRecord();
//OR compare priority and hostname exactly
$service->hasMxRecord('1 some.host');
```

### Basic A + AAAA Validation
```
use Codem\DomainValidation\CloudflareDnsOverHttps;
...
$domain = "google.com"
$service = new CloudflareDnsOverHttps();
$service->setDomain($domain);
$results = [];
$results['AAAA'] = $service->performLookup('AAAA');
$results['A']= $service->performLookup('A');
```
### Form field usage
See DomainValidationForm in the source for a full example

### UserForm field
Editable form fields are available for the silverstripe/userforms module

The ```EditableSelectableLookupField``` field allows for DnsChecks to be configured along with strict checking.
The ```ValidatedDomainField``` allows for configurable record type and strict/non-strict checking.

+ Strict Checking - the lookup must return entries for each record type requested
+ Non Strict Checking - the lookup can return any number of entries for the field to validate as OK

## Install

```
/path/to/php /path/to/composer require codem/silverstripe-domain-validation:^n.N
```

This is a pre-release, not currently in Packagist.

# Author
Codem

## License
See LICENCE
