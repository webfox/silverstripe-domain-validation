# Domain Validation module for Silverstripe

Minimise the hassle of domain and email address typos using this module!

Some APIs reject requests if the domain is invalid e.g user@examplecom, with this module you can check for existing DNS records to minimise these issues.

## Requirements
Silverstripe 3, see the feature-ss4 branch for Silverstripe 4.

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
$service = new GoogleDnsOverHttps();
$service->setDomain($domain);
$results = [];
$results['AAAA'] = $service->performLookup('AAAA');
$results['A']= $service->performLookup('A');
```
### Form field usage
See DomainValidationForm in the source for a full example

### UserForm field
Editable form fields are available. The ```EditableSelectableLookupField``` field allows for DnsChecks to be configured and strict checking.

The ```ValidatedDomainField``` allows for strict and non-strict checking (default)
+ Strict Checking - the lookup must return entries for each record type requested
+ Non Strict Checking - the lookup can return any number of entries for the field to validate as OK

## Install
```
/path/to/php /path/to/composer require codem/silverstripe-domain-validation:dev-master
```

This is a pre-release and is not in Packagist just yet.

# Author
Codem

## License
See LICENCE
