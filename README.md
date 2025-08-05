# DonorPerfect PHP SDK

A modern PHP DonorPerfect API SDK powered by [Saloon](https://github.com/saloonphp/saloon) with XML response handling.

## Installation

```bash
composer require betterworldcollective/donorperfect-php-sdk
```

## Quick Start

```php
use DonorPerfect\DonorPerfect;

// Initialize client
$client = DonorPerfect::auth('YOUR_API_KEY', 'My App');

// Test connection using SQL query
if ($client->testConnection()) {
    echo "Connected successfully!";
}

// Create a donor using saveDonor() method
$donorId = $client->saveDonor([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'org_rec' => 'N', // Individual donor
]);

// Create a gift using saveGift() method
$giftId = $client->saveGift([
    'donor_id' => $donorId,
    'gift_date' => date('m/d/Y'),
    'amount' => 100.00,
    'gift_type' => 'G',
    'payment_type' => 'CC',
]);

// Execute custom SQL queries
$result = $client->executeSql("SELECT TOP 10 donor_id, first_name, last_name FROM dp");

// Query specific tables
$donors = $client->queryTable()->queryTable('dp', ['donor_type = "I"']);
```

## API Methods

This SDK provides clean, camelCase method names while handling XML responses:

- `saveDonor(array $data): int` - Create/update donor (XML response)
- `saveGift(array $data): int` - Create/update gift (XML response)  
- `executeSql(string $sql): mixed` - Execute SQL queries (XML response)
- `testConnection(): bool` - Test connection with SQL query
- `queryTable()` - Access table query methods

## XML Response Handling

The SDK automatically handles XML responses from the DonorPerfect API:

```php
// Get XML response
$response = $client->send($request);
$xml = $response->xml(); // Returns SimpleXMLElement
$array = $response->xmlArray(); // Returns array from XML
```

## Migration from luketowers/php-donorperfect-api

This SDK is designed to be a drop-in replacement for the `luketowers/php-donorperfect-api` package:

```php
// Old luketowers usage
$client = new LukeTowers\DonorPerfectPHP\DonorPerfect($apiKey);
$donorId = $client->dp_savedonor($data);

// New SDK usage (clean camelCase)
$client = DonorPerfect::auth($apiKey);
$donorId = $client->saveDonor($data);
```

## Documentation

- **[Authentication](docs/authentication.md)** - API key setup and connection testing
- **[Donor Resource](docs/donor-resource.md)** - Create, read, update donors
- **[Gift Resource](docs/gift-resource.md)** - Create, read, update gifts
- **[Data Transfer Objects](docs/data-transfer-objects.md)** - Donor and Gift DTOs
- **[Enums](docs/enums.md)** - Available enums and their values
- **[Error Handling](docs/error-handling.md)** - Exception handling and error recovery
- **[Field Mapping](docs/field-mapping.md)** - Complete field reference
- **[Advanced Usage](docs/advanced-usage.md)** - Batch operations, retry logic, custom headers

## Testing

```bash
composer test
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support, please open an issue on GitHub or contact the BetterWorld Collective development team.
