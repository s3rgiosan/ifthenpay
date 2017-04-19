# IfthenPay Helpers

[IfthenPay](https://ifthenpay.com/) PHP helper classes.

## Install

```bash
$ composer require s3rgiosan/ifthenpay-helpers
```

## Usage

```php
use s3rgiosan\IfthenPay\Multibanco;

class FooBar {
    public function process_payment() {
        $reference = Multibanco::generateReference( $ent_id, $subent_id, $order_id, $order_value );
    }
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
