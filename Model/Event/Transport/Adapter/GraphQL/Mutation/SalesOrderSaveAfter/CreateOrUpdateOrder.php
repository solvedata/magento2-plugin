<?php

declare(strict_types=1);

namespace SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\SalesOrderSaveAfter;

use SolveData\Events\Model\Event\Transport\Adapter\GraphQL\Mutation\MutationAbstract;

class CreateOrUpdateOrder extends MutationAbstract
{
    const QUERY = <<<'GRAPHQL'
mutation create_or_update_order($input: CreateOrUpdateOrderInput!) {
    create_or_update_order(input: $input) {
        profile_id
    }
}
GRAPHQL;

    /**
     * Get variables for GraphQL request
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getVariables(): array
    {
        $payload = $this->getEvent()['payload'];

        return [
            'input' => $this->payloadConverter->convertOrderData(
                $payload['order'],
                $payload['orderAllVisibleItems'],
                $payload['area']
            ),
        ];
    }
}
