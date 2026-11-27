# Orders

Acest modul acoperă sincronizarea comenzilor, statusul, feed-uri și statistici ecommerce.

## Access the module

```php
$ordersApi = $client->orders();
```

Nu există metode `save()` sau aliasuri scurtate — folosește `saveOrder()` / `saveOrderRetail()` cu câmpurile din DTO (`number`, `email_address`, linii produs cu `variation` `{id, sku}`, etc.). O cheie necunoscută aruncă `ValidationException` (`unknown field(s): …`).

## saveOrder (exemplu minim)

```php
$result = $ordersApi->saveOrder([
    'number' => 1001,
    'email_address' => 'john@doe.com',
    'phone' => '+40123456789',
    'firstname' => 'John',
    'lastname' => 'Doe',
    'city' => 'Bucharest',
    'county' => 'RO',
    'address' => 'Street 1',
    'discount_value' => 0.0,
    'discount_code' => '-',
    'shipping' => 0.0,
    'tax' => 0.0,
    'total_value' => 99.99,
    'products' => [
        [
            'product_id' => 123,
            'price' => 99.99,
            'quantity' => 1,
            'variation_sku' => 'SKU-123',
        ],
    ],
]);
```

## Alte metode

Vezi documentația extinsă în site: `website/docs/orders.md` (sau sursa `src/Api/OrdersApi.php`) pentru `saveOrderRetail`, `updateFeedUrl`, `updateOrderFeedUrl`, `updateOrderStatus`, `getEcommerceStats`.

## Validation and error flow

1. DTO-ul validează local câmpurile.
2. Dacă validarea pică, apare `ValidationException`.
3. Dacă API răspunde cu eroare, apare excepția mapată de client.

## Best practices

- Loghează payload minim + răspunsul API (fără date sensibile).
- Folosește idempotență în apeluri duplicate din job-uri/retry.
- Rulează teste de integrare cu payload-uri reale în mediu non-production.
