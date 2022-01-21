<?php


namespace Crehler\BaseLinkerShopsApi\Services\Helper;


use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class PaymentMethodHelper
{
    private EntityRepositoryInterface $paymentMethodRepository;

    public function __construct(EntityRepositoryInterface $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function getPaymentMethodByName(string $paymentMethodName, Context $context): ?PaymentMethodEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $paymentMethodName));

        return $this->paymentMethodRepository->search($criteria, $context)->first();
    }

    public function createPaymentMethod(string $paymentMethodName, Context $context): string
    {
        $id = Uuid::randomHex();

        $payment = [
            'id' => $id,
            'handlerIdentifier' => PrePayment::class,
            'name' => $paymentMethodName,
            'description' => 'Payment from BaseLinker',
            'active' => false,
        ];

        $this->paymentMethodRepository->upsert([$payment], $context);

        return $id;
    }
}
