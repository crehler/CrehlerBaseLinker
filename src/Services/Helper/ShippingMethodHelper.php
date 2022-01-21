<?php


namespace Crehler\BaseLinkerShopsApi\Services\Helper;


use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\TrueRule;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class ShippingMethodHelper
{
    /**
     * @var EntityRepositoryInterface
     */
    private $shippingMethodRepository;

    public function __construct(EntityRepositoryInterface $shippingMethodRepository)
    {
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    public function getShippingMethodByName(string $shippingMethodName, Context $context): EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addAssociation('prices');
        $criteria->addFilter(new EqualsFilter('name', $shippingMethodName));

        return $this->shippingMethodRepository->search($criteria, $context)->getEntities();
    }

    public function createShippingMethod(string $shippingMethodName, $price, Context $context): string
    {
        $id = Uuid::randomHex();

        $shipping = [
            'id' => $id,
            'type' => 0,
            'name' => $shippingMethodName,
            'bindShippingfree' => false,
            'active' => false,
            'prices' => [
                [
                    'name' => 'Std',
                    'price' => '10.00',
                    'currencyId' => $context->getCurrencyId(),
                    'calculation' => 1,
                    'quantityStart' => 1,
                    'currencyPrice' => [
                        [
                            'currencyId' => $context->getCurrencyId(),
                            'net' => $price,
                            'gross' => $price,
                            'linked' => false,
                        ],
                    ],
                ],
            ],
            'deliveryTime' => [
                'id' => Uuid::randomHex(),
                'name' => 'test',
                'min' => 1,
                'max' => 90,
                'unit' => 'day',
            ],
            'availabilityRule' => [
                'id' => Uuid::randomHex(),
                'name' => 'true',
                'priority' => 1,
                'conditions' => [
                    [
                        'type' => 'cartCartAmount',
                        'value' => [
                            'operator' => '>=',
                            'amount' => 0,
                        ],
                    ],
                ],
            ]
        ];

        $this->shippingMethodRepository->upsert([$shipping], $context);

        return $id;
    }
}
