# Frame PHP SDK

This is a lightweight PHP SDK for the Frame payment gateway. It uses Guzzle for making API requests and returns the responses as an array/object by default. The SDK communicates with the `https://api.framepayments.com` endpoint.

## Installation

To install the SDK, add the following to your `composer.json` file:

```json
"require": {
    "frame-payments/frame-php": "^1.0"
}
```

Then run `composer install` to add the SDK to your project.

## Usage

First, set your API key using the `setApiKey` method in the `Auth` class:

```php
use Frame\Auth;

Auth::setApiKey('YOUR_API_KEY');
```

After setting the API key, use the endpoint classes under the `Frame\Endpoints` namespace to interact with the API:

```php
use Frame\Endpoints\Customers;
use Frame\Endpoints\ChargeIntents;
use Frame\Endpoints\Subscriptions;

// Create a customer
$customers = new Customers();
$customer = $customers->create([
    'name'  => 'Alice Johnson',
    'email' => 'alice@example.com',
]);

// Create a charge intent
$chargeIntents = new ChargeIntents();
$intent = $chargeIntents->create([
    'amount'         => 10000,
    'currency'       => 'usd',
    'customer'       => $customer['id'],
    'payment_method' => 'pm_123456789',
]);
$chargeIntents->confirm($intent['id']);

// Create a subscription
$subscriptions = new Subscriptions();
$subscription = $subscriptions->create([
    'customer'      => $customer['id'],
    'product_phase' => 'pph_123456789',
    'payment_method' => 'pm_123456789',
]);
```

## Error Handling

This SDK uses exceptions for error handling. If an error occurs during a request, an `Exception` will be thrown. You can catch these exceptions to handle errors in your application.

```php
use Frame\Endpoints\Charges;

try {
    $charges = new Charges();
    $charge = $charges->retrieve('ch_123456789');
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

## Helpers

The SDK also includes a `Helpers` class with useful methods. For example, you can use the `convertAlpha2ToAlpha3` method to convert a country code from ISO 3166-1 alpha-2 to ISO 3166-1 alpha-3.

```php
$alpha3 = Frame\Helpers::convertAlpha2ToAlpha3('US');
```

## Testing

The SDK includes a comprehensive test suite using PHPUnit. To run the tests:

### Prerequisites
- PHP 8.1 or higher
- Composer
- Docker (for containerized testing)

### Running Tests

#### Option 1: Using Docker (Recommended)
```bash
# Build the test image
docker build -t frame-php-sdk-tests .

# Run all tests
docker run --rm frame-php-sdk-tests ./vendor/bin/phpunit

# Run tests with pretty output
docker run --rm frame-php-sdk-tests ./vendor/bin/phpunit --testdox

# Run specific test suites
docker run --rm frame-php-sdk-tests ./vendor/bin/phpunit tests/Unit/
docker run --rm frame-php-sdk-tests ./vendor/bin/phpunit tests/Integration/
```

#### Option 2: Local PHP/Composer
```bash
# Install dependencies
composer install

# Run all tests
composer test
# or
./vendor/bin/phpunit

# Run tests with coverage
composer test-coverage
# or
./vendor/bin/phpunit --coverage-html coverage
```

## Contributing

Contributions are welcome. Please submit a pull request or create an issue if you have any improvements or find any bugs.
