<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter\CreateOrUpdatePayment;
use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\PayloadConverter;

class CreateOrUpdatePaymentTest extends TestCase
{
    public function testNotAllowedIfPaymentDataDoesIsNotPresent(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {},
    "area": {}
}
PAYLOAD;

        $mutation = new CreateOrUpdatePayment(
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testNotAllowedIfAmountPaidIsAbsent(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "payment (Magento\\Sales\\Model\\Order\\Payment\\Interceptor)": {}
    },
    "area": {}
}
PAYLOAD;

        $mutation = new CreateOrUpdatePayment(
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testNotAllowedIfAmountPaidIsZero(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "payment (Magento\\Sales\\Model\\Order\\Payment\\Interceptor)": {
            "amount_paid": "0.0000"
        }
    },
    "area": {}
}
PAYLOAD;

        $mutation = new CreateOrUpdatePayment(
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertFalse($mutation->isAllowed());
    }

    public function testAllowedIfAmountPaidIsNonZero(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "payment (Magento\\Sales\\Model\\Order\\Payment\\Interceptor)": {
            "amount_paid": "1.0000"
        }
    },
    "area": {}
}
PAYLOAD;

        $mutation = new CreateOrUpdatePayment(
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $this->assertTrue($mutation->isAllowed());
    }

    public function testMuationInput(): void
    {
        $payload = <<<'PAYLOAD'
{
    "order": {
        "increment_id": "1001",
        "payment (Magento\\Sales\\Model\\Order\\Payment\\Interceptor)": {
            "amount_paid": "1.0000"
        }
    },
    "area": {
        "website (Magento\\Store\\Model\\Website\\Interceptor)": {
            "code": "unit_test_store"
        }
    }
}
PAYLOAD;

        $mutation = new CreateOrUpdatePayment(
            $this->createPayloadConverter()
        );
        $mutation->setEvent(['payload' => $payload]);

        $variables = $mutation->getVariables();

        $this->assertArraySubset(
            [
                'id'       => '1001-payment',
                'order_id' => '1001',
                'provider' => 'unit_test_store',
                'amount'   => '1.0000',
            ],
            $variables['input']
        );
    }

    private function createPayloadConverter(): PayloadConverter
    {
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
        
        $logger = $this->getMockBuilder('SolveData\Events\Model\Logger')
            ->disableOriginalConstructor()
            ->getMock();
        
        return new PayloadConverter(
            $countryFactory,
            $profileHelper,
            $regionFactory,
            $storeManager,
            $logger
        );
    }
}
