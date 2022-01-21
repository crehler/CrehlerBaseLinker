<?php

namespace Crehler\BaseLinkerShopsApi\Services\Readers;

use Crehler\BaseLinkerShopsApi\Component\BaseLinker\StoreApiStruct\Order\OrderAddRequest;
use Crehler\BaseLinkerShopsApi\Services\Creators\OrderCreator;
use Crehler\BaseLinkerShopsApi\Struct\ConfigStruct;
use Monolog\Logger;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;

class OrderReader
{
    private const UPDATE_STATUS = 'status';
    private const UPDATE_PAID = 'paid';
    private const UPDATE_DELIVERY_NUMBER = 'delivery_number';

    private EntityRepositoryInterface $orderRepository;
    private EntityRepositoryInterface $shippingMethodRepository;
    private EntityRepositoryInterface $paymentMethodRepository;
    private EntityRepositoryInterface $stateMachineStateRepository;
    private ProductReader $productReader;
    private OrderCreator $orderService;
    private Logger $logger;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $shippingMethodRepository,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $stateMachineStateRepository,
        ProductReader $productReader,
        OrderCreator $orderService,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->stateMachineStateRepository = $stateMachineStateRepository;
        $this->productReader = $productReader;
        $this->orderService = $orderService;
        $this->logger = $logger;
    }

    public function ordersGet(
        ?string $timeFrom,
        ?string $idFrom,
        ?string $onlyPaid,
        ConfigStruct $configStruct,
        SalesChannelContext $salesChannelContext
    ): array {
        $ordersResponse = [];

        if (!$timeFrom && !$idFrom) return $ordersResponse;

        $criteria = new Criteria();
        $criteria->getAssociation('lineItems')->addAssociation('product');
        $criteria->getAssociation('transactions')->addAssociation('paymentMethod');
        $criteria->addAssociation('currency');
        $criteria->addAssociation('orderCustomer.customer');
        $criteria->getAssociation('billingAddress')->addAssociation('country');
        $criteria->getAssociation('addresses')->addAssociation('country');
        $criteria->getAssociation('deliveries')
            ->addAssociation('shippingMethod')
            ->addAssociation('country');

        $criteria->addFilter(
            new EqualsFilter(
                'order.salesChannelId',
                $salesChannelContext->getSalesChannel()->getId()
            )
        );

        if ($timeFrom) {
            $orderDate = (new \DateTime())->setTimestamp($timeFrom)->format('Y-m-d H:i:s');
            $criteria->addFilter(new RangeFilter('createdAt', ['gte' => $orderDate]));
        }

        if ($idFrom) {
            $criteria->addFilter(new RangeFilter('orderNumber', ['gte' => $idFrom]));
        }

        if ($onlyPaid) {
            $criteria->addFilter(
                new EqualsFilter(
                    'transactions.stateMachineState.technicalName',
                    OrderTransactionStates::STATE_PAID
                )
            );
        }

        $orders = $this->orderRepository
            ->search($criteria, $salesChannelContext->getContext())->getEntities();

        /** @var OrderEntity $order */
        foreach ($orders as $order) {
            if ($order->getDeliveries()->first() instanceof OrderDeliveryEntity) {
                $delivery = $order->getDeliveries()->first();
                $deliveryAddressId = $delivery->getShippingOrderAddressId();
                $deliveryAddress = $order->getAddresses()->get($deliveryAddressId);
            } else {
                $delivery = null;
                $deliveryAddress = $order->getBillingAddress();
            }

            $transaction = null;
            $isPaymentCod = 0;
            if ($order->getTransactions()->first() instanceof OrderTransactionEntity) {
                $transaction = $order->getTransactions()->first();

                if (in_array($transaction->getPaymentMethodId(), $configStruct->getCodPaymentMethodIds())) {
                    $isPaymentCod = 1;
                }
            }

            $products = [];
            $order->getLineItems()->sortByPosition();

            foreach ($order->getLineItems() as $lineItem) {
                try {
                    if ($lineItem->getProduct() instanceof ProductEntity) {
                        $product = $lineItem->getProduct();

                        if ($product->getParentId() === null) {
                            $productId = $product->getAutoIncrement();
                            $variantId = 0;
                        } else {
                            $productId = $this->productReader->getProduct(
                                $product->getParentId(),
                                $salesChannelContext->getContext()
                            )->getAutoIncrement();
                            $variantId = $product->getAutoIncrement();
                        }

                        $products[] = [
                            'id' => $productId,
                            'variant_id' => $variantId,
                            'name' => $lineItem->getLabel(),
                            'quantity' => $lineItem->getQuantity(),
                            'price' => $lineItem->getUnitPrice(),
                            'weight' => $product->getWeight(),
                            'tax' => $lineItem->getPrice()->getCalculatedTaxes()->first()->getTaxRate(),
                            'ean' => $product->getEan(),
                            'sku' => $product->getProductNumber(),
                        ];
                    } elseif ($lineItem->getType() === LineItem::PROMOTION_LINE_ITEM_TYPE) {
                        $tax = $lineItem->getPrice()->getCalculatedTaxes()->first();
                        $products[] = [
                            'name' => $lineItem->getLabel(),
                            'quantity' => $lineItem->getQuantity(),
                            'price' => $lineItem->getUnitPrice(),
                            'tax' => $tax instanceof CalculatedTax ? $tax->getTaxRate() : 0
                        ];
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('OrdersGet - prepare position error: ' . $e->getMessage());
                }
            }

            if (empty($products)) {
                //skip order without products
                continue;
            }

            $deliveryPoint = ['name' => '', 'address' => '', 'postCode' => '', 'city' => ''];
            if (isset($deliveryAddress->getCustomFields()['inpostId'])) {
                $deliveryPoint = [
                    'name' => $deliveryAddress->getCustomFields()['inpostId'],
                    'address' => $deliveryAddress->getStreet(),
                    'postCode' => $deliveryAddress->getZipcode(),
                    'city' => $deliveryAddress->getCity(),
                ];
            }

            $ordersResponse[$order->getOrderNumber()] = [
                'delivery_fullname' => $deliveryAddress->getFirstName() . ' ' . $deliveryAddress->getLastName(),
                'delivery_company' => $deliveryAddress->getCompany(),
                'delivery_address' => implode(' ', [
                    $deliveryAddress->getStreet(),
                    $deliveryAddress->getAdditionalAddressLine1(),
                    $deliveryAddress->getAdditionalAddressLine2()
                ]),
                'delivery_city' => $deliveryAddress->getCity(),
                'delivery_postcode' => $deliveryAddress->getZipcode(),
                'delivery_state_code' => '',
                'delivery_country' => $deliveryAddress->getCountry()->getTranslation('name'),
                'delivery_country_code' => $deliveryAddress->getCountry()->getIso(),
                'invoice_fullname' => $order->getBillingAddress()->getFirstName()
                    . ' ' . $order->getBillingAddress()->getLastName(),
                'invoice_company' => $order->getBillingAddress()->getCompany(),
                'invoice_address' => implode(' ', [
                    $order->getBillingAddress()->getStreet(),
                    $order->getBillingAddress()->getAdditionalAddressLine1(),
                    $order->getBillingAddress()->getAdditionalAddressLine2()
                ]),
                'invoice_city' => $order->getBillingAddress()->getCity(),
                'invoice_state_code' => '',
                'invoice_postcode' => $order->getBillingAddress()->getZipcode(),
                'invoice_country' => $order->getBillingAddress()->getCountry()->getTranslation('name'),
                'invoice_country_code' => $order->getBillingAddress()->getCountry()->getIso(),
                'invoice_nip' => $order->getOrderCustomer()->getVatIds() ?
                    implode('', $order->getOrderCustomer()->getVatIds()) : '',
                'delivery_point_name' => $deliveryPoint['name'],
                'delivery_point_address' => $deliveryPoint['address'],
                'delivery_point_postcode' => $deliveryPoint['postCode'],
                'delivery_point_city' => $deliveryPoint['city'],
                'phone' => $deliveryAddress->getPhoneNumber() ?? $order->getBillingAddress()->getPhoneNumber(),
                'email' => $order->getOrderCustomer()->getEmail(),
                'date_add' => $order->getCreatedAt()->getTimestamp(),
                'payment_method' => $transaction ? $transaction->getPaymentMethod()->getTranslation('name') : '',
                'payment_method_cod' => $isPaymentCod,
                'currency' => $order->getCurrency()->getIsoCode(),
                'user_comments' => $order->getCustomerComment(),
                'user_comments_long' => '',
                'admin_comments' => '',
                'status_id' => $order->getStateId(),
                'delivery_method_id' => $delivery ? $delivery->getId() : '',
                'delivery_method' => $delivery ? $delivery->getShippingMethod()->getTranslation('name') : '',
                'delivery_price' => $delivery ? $delivery->getShippingCosts()->getTotalPrice() : 0.0,
                'paid' => $transaction->getStateMachineState()->getTechnicalName() === OrderTransactionStates::STATE_PAID,
                'want_invoice' => 0,
                'extra_field_1' => '',
                'extra_field_2' => '',
                'products' => $products,
            ];
        }

        return $ordersResponse;
    }

    public function orderUpdate(
        string $orderNumbers,
        string $updateType,
        string $updateValue,
        SalesChannelContext $salesChannelContext
    ): array {
        $counter = 0;
        $context = $salesChannelContext->getContext();

        $orderNumbers = array_filter(explode(',', $orderNumbers));
        if (empty($orderNumbers)) return ['counter' => $counter];

        $criteria = (new Criteria())
            ->addAssociation('transactions')
            ->addAssociation('deliveries');

        $criteria->addFilter(
            new EqualsAnyFilter('orderNumber', $orderNumbers)
        );

        $orders = $this->orderRepository
            ->search($criteria, $context)->getEntities();

        /** @var OrderEntity $order */
        foreach ($orders as $order) {
            switch ($updateType) {
                case self::UPDATE_STATUS:
                    $this->updateOrderStatus($order, $updateValue, $context);
                    break;
                case self::UPDATE_PAID:
                    $this->updateOrderPaid($order, (bool) $updateValue, $context);
                    break;
                case self::UPDATE_DELIVERY_NUMBER:
                    $this->updateOrderDeliveryNumber($order, $updateValue, $context);
                    break;
            }
            $counter++;
        }

        return ['counter' => $counter];
    }

    private function updateOrderStatus(OrderEntity $order, string $newStatusId, Context $context): void
    {
        $orderStatus = $order->getStateMachineState();

        if ($orderStatus->getId() === $newStatusId) return;

        $newStatus = $this->stateMachineStateRepository
            ->search(new Criteria([$newStatusId]), $context)->first();

        if ($newStatus === null) return;

        $orderStatusName = $orderStatus->getTechnicalName();
        $newStatusName = $newStatus->getTechnicalName();
        $orderId = $order->getId();
        $orderDeliveryId = $order->getDeliveries()->first()->getId();

        $this->orderService->updateOrderStatusTranslation(
            $orderId,
            $orderStatusName,
            $newStatusName,
            $orderDeliveryId,
            $context
        );
    }

    private function updateOrderPaid(OrderEntity $order, bool $paid, Context $context): void
    {
        $orderTransaction = $order->getTransactions()->first();
        if ($orderTransaction === null) return;

        $transactionStatusName = $orderTransaction->getStateMachineState()->getTechnicalName();
        if ($paid && $transactionStatusName !== OrderTransactionStates::STATE_PAID) {
            $this->orderService->changePaymentStatus(
                $orderTransaction->getId(),
                OrderTransactionStates::STATE_PAID,
                $context
            );
        }
    }

    private function updateOrderDeliveryNumber(OrderEntity $order, string $trackingNumber, Context $context)
    {
        $deliver = $order->getDeliveries()->first();

        if (!in_array($trackingNumber, $deliver->getTrackingCodes())) {
            $trackingCodes = $deliver->getTrackingCodes();
            $trackingCodes[] = $trackingNumber;
            $this->orderRepository->upsert([
                [
                    'id' => $order->getId(),
                    'deliveries' => [
                        [
                            'id' => $deliver->getId(),
                            'trackingCodes' => $trackingCodes
                        ]
                    ]
                ]
            ], $context);
        }
    }

    public function statusesList(SalesChannelContext $salesChannelContext): array
    {
        $statusesList = [];

        $criteria = new Criteria();
        $criteria->addAssociation('stateMachine');
        $criteria->addFilter(
            new EqualsFilter('stateMachine.technicalName', OrderStates::STATE_MACHINE)
        );

        $statuses = $this->stateMachineStateRepository->search($criteria, $salesChannelContext->getContext())
            ->getEntities();

        /** @var StateMachineStateEntity $status */
        foreach ($statuses as $status) {
            $statusesList[$status->getId()] = $status->getTranslation('name');
        }

        return $statusesList;
    }

    public function deliveryMethodsList(SalesChannelContext $salesChannelContext): array
    {
        $deliveryMethodsList = [];

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('active', true)
        );

        $shippingMethods = $this->shippingMethodRepository->search($criteria, $salesChannelContext->getContext())
            ->getEntities();

        /** @var StateMachineStateEntity $status */
        foreach ($shippingMethods as $shippingMethod) {
            $deliveryMethodsList[$shippingMethod->getId()] = $shippingMethod->getTranslation('name');
        }

        return $deliveryMethodsList;
    }

    public function paymentMethodsList(SalesChannelContext $salesChannelContext): array
    {
        $paymentMethodsList = [];

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('active', true)
        );

        $paymentMethods = $this->paymentMethodRepository->search($criteria, $salesChannelContext->getContext())
            ->getEntities();

        /** @var StateMachineStateEntity $status */
        foreach ($paymentMethods as $shippingMethod) {
            $paymentMethodsList[$shippingMethod->getId()] = $shippingMethod->getTranslation('name');
        }

        return $paymentMethodsList;
    }

    public function orderAdd(OrderAddRequest $orderAddRequest, SalesChannelContext $salesChannelContext)
    {
        $orderNumber = $orderAddRequest->getPreviousOrderId();

        if ($orderNumber) {
            $this->logger->info('Order update ' . $orderNumber . ' ' . $orderAddRequest->getBaselinkerId());
            return ['order_id' => $orderNumber];
        }

        $orderId = Uuid::randomHex();
        try {
            $orderData = $this->orderService->orderAdd($orderId, $orderAddRequest, $salesChannelContext);
        } catch (\Throwable $e) {
            $this->logger->error('Create order error: ' . $e->getMessage());
        }
        if (empty($orderData)) return ['order_id' => null];

        $this->orderRepository->upsert([$orderData], $salesChannelContext->getContext());

        $orderNumber = $orderData['orderNumber'];
        $this->logger->error('Create was created ' . $orderNumber);

        return ['order_id' => $orderNumber];
    }
}
