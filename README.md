# Route53

Automatic IP change code for AWS Route53 endpoint.

## Requirements

PHP 7.4/8.0

## Installation

```
mkdir route53
cd route53
git clone git@github.com:node83/route53.git .
composer install
cp .env.example .env
```
Edit .env to suit and add the route53.php process to your crons:

```
#----------------------------- minute
#    +------------------------ hour
#    |    +------------------- day of month
#    |    |    +-------------- month
#    |    |    |    +--------- day of week
#    |    |    |    |    +---- command
#    |    |    |    |    |
*/10 *    *    *    *    php /path/to/route53/route53.php
```

Now anytime your IP address changes - our select domain name will have
its DNS entry updated to reflect your new address.

