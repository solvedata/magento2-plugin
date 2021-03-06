<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\CreateOrUpdateOrder;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;
use SolveData\Events\Model\Logger;

class CreateOrUpdateOrderTest extends TestCase
{
    public function testIsAlwaysAllowed(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {},
    "area": {}
}
PAYLOAD;

        $mutation = new CreateOrUpdateOrder(
            $this->createPayloadConverter(),
            $this->createLogger()
        );
        $mutation->setEvent(['created_at' => '2021-06-01 01:23:00', 'payload' => $payload]);

        $this->assertTrue($mutation->isAllowed());
    }

    public function testMuationInput(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "q-123",
        "customer_email": "jane@example.com",
        "order_currency_code": "USD",
        "store_id": 1,
        "shipping_amount": "1.0000",
        "tax_amount": "0.2000",
        "addresses": []
    },
    "orderAllVisibleItems": [],
    "area": {
        "website (Magento\\Store\\Model\\Website\\Interceptor)": {
            "code": "unit_test_store"
        }
    }
}
PAYLOAD;

        $mutation = new CreateOrUpdateOrder(
            $this->createPayloadConverter(),
            $this->createLogger()
        );
        $mutation->setEvent(['created_at' => '2021-06-01 01:23:00', 'payload' => $payload]);

        $variables = $mutation->getVariables();

        $this->assertArraySubset(
            [
                'id'              => '1001',
                'provider'        => 'unit_test_store',
                'status'          => 'CREATED',
                'currency'        => 'USD',
                'items'           => [],
                'storeIdentifier' => '1',
            ],
            $variables['input']
        );

        $this->assertArraySubset(
            [
                'magento_quote_id'       => 'q-123',
                'magento_customer_email' => 'jane@example.com'
            ],
            json_decode($variables['input']['attributes'], true)
        );
    }

    public function testImportedOrderAttribute(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "quote_id": "q-123",
        "customer_email": "jane@example.com",
        "order_currency_code": "USD",
        "store_id": 1,
        "shipping_amount": "1.0000",
        "tax_amount": "0.2000",
        "addresses": [],
        "extension_attributes": {
            "is_import_to_solve_data": true
        }
    },
    "orderAllVisibleItems": [],
    "area": {
        "website (Magento\\Store\\Model\\Website\\Interceptor)": {
            "code": "unit_test_store"
        }
    }
}
PAYLOAD;

        $mutation = new CreateOrUpdateOrder(
            $this->createPayloadConverter(),
            $this->createLogger()
        );
        $mutation->setEvent(['created_at' => '2021-06-01 01:23:00', 'payload' => $payload]);

        $variables = $mutation->getVariables();

        $orderAttributes = json_decode($variables['input']['attributes'], true);
        $this->assertArrayHasKey('imported_at', $orderAttributes);
    }

    private function createPayloadConverter(): PayloadConverter
    {
        $config = $this->getMockBuilder('SolveData\Events\Model\Config')
            ->disableOriginalConstructor()
            ->getMock();

        $countryFactory = $this->getMockBuilder('Magento\Directory\Model\CountryFactory')
            ->disableOriginalConstructor()
            ->getMock();
        
        $profileHelper = $this->getMockBuilder('SolveData\Events\Helper\Profile')
            ->disableOriginalConstructor()
            ->getMock();
        
        $regionFactory = $this->getMockBuilder('Magento\Directory\Model\RegionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        
        $storeManager = $this->getMockBuilder('Magento\Store\Model\StoreManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        
        $quoteIdMaskFactory = $this->getMockBuilder('Magento\Quote\Model\QuoteIdMaskFactory')
            ->disableOriginalConstructor()
            ->getMock();
        
        $logger = $this->createLogger();

        return new PayloadConverter(
            $config,
            $countryFactory,
            $profileHelper,
            $regionFactory,
            $storeManager,
            $quoteIdMaskFactory,
            $logger
        );
    }

    private function createLogger(): Logger {
        return $this->getMockBuilder('SolveData\Events\Model\Logger')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
