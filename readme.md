# Domain Validation module for Silverstripe

Minimise the hassle of domain and email address typos using this module!
Some services, e.g Mailgun reject requests if the domain is invalid e.g user@examplecom, with this module you can check for existing DNS records to minimise these issues.

## Requirements
Silverstripe 3, currently. SS4 upgrade PRs welcome :)

## Features
+ Plugs into Cloudflare DNS over HTTPS
+ Plugs into Google DNS over HTTPS
+ Does Caching based on TTL (Flysystem middleware)
+ Provides a ValidatableEmailField (MX record check on domain part) for use in forms
+ Provides a ValidatableDomainField (A + CNAME record check on domain part)

## Install
```
/path/to/php /path/to/composer require codem/silverstripe-domain-validation:~n.N
```

# Author
Codem

## License
MIT