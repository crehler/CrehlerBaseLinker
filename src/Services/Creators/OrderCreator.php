<?php

namespace Crehler\BaseLinkerShopsApi\Services\Creators;

use Crehler\BaseLinkerShopsApi\Component\BaseLinker\StoreApiStruct\Order\OrderAddProduct;
use Crehler\BaseLinkerShopsApi\Component\BaseLinker\StoreApiStruct\Order\OrderAddRequest;
use Crehler\BaseLinkerShopsApi\Services\Helper\PaymentMethodHelper;
use Crehler\BaseLinkerShopsApi\Services\Helper\ShippingMethodHelper;
use Crehler\BaseLinkerShopsApi\Services\Readers\CustomerReader;
use Crehler\BaseLinkerShopsApi\Services\Readers\ProductReader;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Shopware\Core\System\Tax\TaxEntity;

class OrderCreator
{
    private EntityRepository $currencyRepository;
    private PaymentMethodHelper $paymentMethodHelper;
    private ShippingMethodHelper $shippingMethodHelper;
    private CustomerReader $customerReader;
    private ProductReader $productReader;
    private StateMachineRegistry $stateMachineRegistry;
    private NumberRangeValueGeneratorInterface $numberRangeValueGenerator;
    private LoggerInterface $logger;

    public function __construct(
        EntityRepository $currencyRepository,
        PaymentMethodHelper $paymentMethodHelper,
        ShippingMethodHelper $shippingMethodHelper,
        CustomerReader $customerReader,
        ProductReader $productReader,
        StateMachineRegistry $stateMachineRegistry,
        NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        LoggerInterface $logger
    ) {
        $this->currencyRepository = $currencyRepository;
        $this->paymentMethodHelper = $paymentMethodHelper;
        $this->shippingMethodHelper = $shippingMethodHelper;
        $this->customerReader = $customerReader;
        $this->productReader = $productReader;
        $this->stateMachineRegistry = $stateMachineRegistry;
        $this->numberRangeValueGenerator = $numberRangeValueGenerator;
        $this->logger = $logger;
    }

    public function orderAdd(string $orderId, OrderAddRequest $orderAddRequest, SalesChannelContext $salesChannelContext): ?array
    {
        $context = $salesChannelContext->getContext();
        $lineItems = [];
        $totalPrice = 0;
        $totalPriceNet = 0;
        $orderTaxData = [];

        /** @var OrderAddProduct $product */
        foreach ($orderAddRequest->getProducts() as $product) {
            $productId = $product->getVariantId() ?? $product->getId();
            if (!$productId) continue;

            $shopwareProduct = $this->productReader->readStorefrontProduct(
                null,
                (string) $productId,
                $salesChannelContext
            );

            if (!$shopwareProduct instanceof SalesChannelProductEntity) {
                throw new \Exception('Not found product');
            }

            $taxRate = 23;
            if ($shopwareProduct->getTax() instanceof TaxEntity) {
                $taxRate = $shopwareProduct->getTax()->getTaxRate();
            } elseif ($shopwareProduct->getParentId() !== null) {
                $shopwareProductParent = $this->productReader->readStorefrontProduct(
                    $shopwareProduct->getParentId(),
                    null,
                    $salesChannelContext
                );
                if ($shopwareProductParent->getTax() instanceof TaxEntity) {
                    $taxRate = $shopwareProductParent->getTax()->getTaxRate();
                }
            }

            $priceGross = $product->getPrice();
            $taxAmount = ($product->getPrice() * $product->getQuantity() * ($taxRate / 100))
                / (1 + ($taxRate / 100));

            $calculatedTax = new CalculatedTax(
                $taxAmount,
                $taxRate,
                $priceGross * $product->getQuantity()
            );
            $taxRule = new TaxRule($taxRate);

            if (isset($orderTaxData[$taxRate])) {
                $orderTaxData[$taxRate]['price'] += $priceGross * $product->getQuantity();
                $orderTaxData[$taxRate]['tax'] += $taxAmount;
            } else {
                $orderTaxData[$taxRate]['price'] = $priceGross * $product->getQuantity();
                $orderTaxData[$taxRate]['tax'] = $taxAmount;
            }

            $lineItems[] = [
                'id' => Uuid::randomHex(),
                'identifier' => $shopwareProduct->getId(),
                'productId' => $shopwareProduct->getId(),
                'referencedId' => $shopwareProduct->getId(),
                'payload' => [
                    'productNumber' => $shopwareProduct->getProductNumber(),
                ],
                'quantity' => $product->getQuantity(),
                'type' => 'product',
                'label' => $product->getName(),
                'price' => new CalculatedPrice(
                    $priceGross,
                    $priceGross * $product->getQuantity(),
                    new CalculatedTaxCollection([$calculatedTax]),
                    new TaxRuleCollection([$taxRule]),
                    $product->getQuantity()
                ),
                'priceDefinition' => new QuantityPriceDefinition(
                    $product->getPrice(),
                    new TaxRuleCollection([new TaxRule($taxRate)]),
                    $product->getQuantity()
                )
            ];

            $totalPrice += $product->getPrice() * $product->getQuantity();
            $totalPriceNet += ($product->getPrice() * $product->getQuantity()) - $taxAmount;
        }

        $totalPrice += $orderAddRequest->getDeliveryPrice();

        if (empty($lineItems)) {
            return null;
        }

        $currency = $this->getCurrency($orderAddRequest->getCurrency(), $context);

        if (null === $currency) throw new \Exception('Shopware store does not have ' . $orderAddRequest->getCurrency());

        if ($orderAddRequest->getDateAdd() !== null) {
            $orderDateTime = (new \DateTimeImmutable())->setTimestamp($orderAddRequest->getDateAdd());
        } else {
            $orderDateTime = (new \DateTimeImmutable());
        }

        $order = [
            'id' => $orderId,
            'orderNumber' => $this->numberRangeValueGenerator->getValue(
                'order',
                $context,
                $salesChannelContext->getSalesChannelId()
            ),
            'customerComment' => $orderAddRequest->getUserComments(),
            'orderDateTime' => $orderDateTime,
            'price' => $this->preparePrice($totalPriceNet, $totalPrice, $orderTaxData),
            'shippingCosts' => new CalculatedPrice(
                $orderAddRequest->getDeliveryPrice(),
                $orderAddRequest->getDeliveryPrice(),
                new CalculatedTaxCollection(),
                new TaxRuleCollection()
            ),
            'stateId' => $orderAddRequest->getStatusId() ?? $this->stateMachineRegistry->getInitialState(OrderStates::STATE_MACHINE, $context)->getId(),
            'currencyId' => $currency->getId(),
            'currencyFactor' => $currency->getFactor(),
            'salesChannelId' => $salesChannelContext->getSalesChannelId(),
            'lineItems' => $lineItems,
            'deepLinkCode' => Random::getBase64UrlString(32),
            'customFields' => [
                'orderSource' => $orderAddRequest->getService(),
                'baseLinkerId' => $orderAddRequest->getBaselinkerId()
            ]
        ];

        if ($orderAddRequest->getPaymentMethodId()) {
            $paymentMethod = [
                'paymentMethodId' => $orderAddRequest->getPaymentMethodId(),
                'amount' => new CalculatedPrice(
                    $totalPrice,
                    $totalPrice,
                    new CalculatedTaxCollection(),
                    new TaxRuleCollection()
                ),
                'stateId' => $orderAddRequest->getPaid()
                    ? $this->getPaidStatusId($context)
                    : $this->stateMachineRegistry->getInitialState(
                        OrderTransactionStates::STATE_MACHINE,
                        $context
                    )->getId()
            ];

        } else {
            $paymentMethod = $this->preparePaymentMethod(
                $orderAddRequest->getPaymentMethod(),
                $orderAddRequest->getPaid(),
                $totalPrice,
                $salesChannelContext->getSalesChannel(),
                $context
            );
        }

        $order = array_merge($order, ['transactions' => [$paymentMethod]]);

        $customer = $this->customerReader->getCustomer($orderAddRequest->getEmail(), $context);

        if ($customer) {
            $customerOrder = $this->prepareOrderCustomer($customer, $orderAddRequest, $salesChannelContext);
        } else {
            $customerOrder = $this->createOrderCustomer($orderAddRequest, $salesChannelContext);
        }

        return array_merge($order, $customerOrder);
    }

    public function changeOrderStatus(string $orderId, string $transitionName, Context $context)
    {
        $orderStateTransition = new Transition(
            OrderDefinition::ENTITY_NAME, $orderId, $transitionName, 'stateId');
        $this->stateMachineRegistry->transition($orderStateTransition, $context);
    }

    public function changeDeliveryStatus(string $orderDeliveryId, string $transitionName, Context $context)
    {
        $orderDeliveryTransition = new Transition(
            OrderDeliveryDefinition::ENTITY_NAME, $orderDeliveryId, $transitionName, 'stateId');
        $this->stateMachineRegistry->transition($orderDeliveryTransition, $context);
    }

    public function changePaymentStatus(string $transactionId, string $transitionName, Context $context)
    {
        try {
            $orderDeliveryTransition = new Transition(
                OrderTransactionDefinition::ENTITY_NAME, $transactionId, $transitionName, 'stateId');
            $this->stateMachineRegistry->transition($orderDeliveryTransition, $context);
        } catch (\Throwable $e) {
            $this->logger->error('Change order payment status error: ' . $e->getMessage());
        }
    }

    public function updateOrderStatusTranslation(
        string $orderId,
        string $orderStatusName,
        string $newOrderStatusName,
        string $orderDeliveryId,
        Context $context
    ) {
        try {
            if ($orderStatusName == 'open' && $newOrderStatusName == 'in_progress') {
                $this->changeOrderStatus($orderId, 'process', $context);
            } elseif ($orderStatusName == 'open' && $newOrderStatusName == 'completed') {
                $this->changeOrderStatus($orderId, 'process', $context);
                $this->changeOrderStatus($orderId, 'complete', $context);
                $this->changeDeliveryStatus($orderDeliveryId, 'ship', $context);
            } elseif ($orderStatusName == 'in_progress' && $newOrderStatusName == 'completed') {
                $this->changeOrderStatus($orderId, 'complete', $context);
                $this->changeDeliveryStatus($orderDeliveryId, 'ship', $context);
            } elseif ($orderStatusName == 'completed' && $newOrderStatusName == 'open') {
                $this->changeOrderStatus($orderId, 'reopen', $context);
                $this->changeDeliveryStatus($orderDeliveryId, 'reopen', $context);
            } elseif ($orderStatusName == 'completed' && $newOrderStatusName == 'in_progress') {
                $this->changeOrderStatus($orderId, 'reopen', $context);
                $this->changeDeliveryStatus($orderDeliveryId, 'reopen', $context);
                $this->changeOrderStatus($orderId, 'process', $context);
            } elseif (in_array($orderStatusName, ['open', 'in_progress']) && $newOrderStatusName == 'cancelled') {
                $this->changeOrderStatus($orderId, 'cancel', $context);
                $this->changeDeliveryStatus($orderDeliveryId, 'cancel', $context);
            } elseif ($orderStatusName == 'completed' && $newOrderStatusName == 'cancelled') {
                $this->changeOrderStatus($orderId, 'reopen', $context);
                $this->changeOrderStatus($orderId, 'cancel', $context);
                $this->changeDeliveryStatus($orderDeliveryId, 'retour', $context);
            } elseif ($orderStatusName == 'cancelled' && $newOrderStatusName == 'open') {
                $this->changeOrderStatus($orderId, 'reopen', $context);
                $this->changeDeliveryStatus($orderDeliveryId, 'reopen', $context);
            }
        } catch (\Exception $e) {
            $this->logger->error('Change order status error: ' . $e->getMessage());
        }
    }

    private function prepareName(string $originalFullName)
    {
        $name = [];

        $fullName = explode(' ', $originalFullName);
        if (count($fullName) >= 2) {
            foreach ($fullName as $str) {
                if (!isset($name['firstName'])) {
                    $name['firstName'] = $str;
                    continue;
                }

                if (!isset($name['lastName'])) {
                    $name['lastName'] = $str . ' ';
                } else {
                    $name['lastName'] .= $str . ' ';
                }
            }
        } else {
            $name['firstName'] = $originalFullName;
            $name['lastName'] = ' ';
        }

        foreach ($name as &$namePart) {
            $namePart = trim($namePart);
            if (!$namePart) {
                $namePart = '-';
            }
        }

        return $name;
    }

    private function preparePaymentMethod(
        string $paymentMethodName, float $paymentDone, float $orderPrice, SalesChannelEntity $salesChannel, Context $context): array
    {
        $stateId = $paymentDone ? $this->getPaidStatusId($context) : $this->stateMachineRegistry->getInitialState(OrderTransactionStates::STATE_MACHINE, $context)->getId();

        if ($paymentMethodName !== '') {
            $paymentMethod = $this->paymentMethodHelper->getPaymentMethodByName($paymentMethodName, $context);

            if ($paymentMethod instanceof PaymentMethodEntity) {
                return [
                    'paymentMethodId' => $paymentMethod->getId(),
                    'amount' => new CalculatedPrice($orderPrice,$orderPrice, new CalculatedTaxCollection(), new TaxRuleCollection()),
                    'stateId' => $stateId
                ];
            } else {
                return [
                    'paymentMethodId' => $this->paymentMethodHelper->createPaymentMethod($paymentMethodName, $context),
                    'amount' => new CalculatedPrice($orderPrice,$orderPrice, new CalculatedTaxCollection(), new TaxRuleCollection()),
                    'stateId' => $stateId
                ];
            }
        }

        return [
            'paymentMethodId' => $salesChannel->getPaymentMethodId(),
            'amount' => new CalculatedPrice($orderPrice,$orderPrice, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'stateId' => $stateId
        ];
    }



    private function prepareShippingMethod(string $deliveryMethodName, string $price, SalesChannelEntity $salesChannel, Context $context): ?string
    {
        if ($deliveryMethodName !== '') {
            $shippingMethods = $this->shippingMethodHelper->getShippingMethodByName($deliveryMethodName, $context);

            /** @var ShippingMethodEntity $shippingMethod */
            foreach ($shippingMethods as $shippingMethod) {
                $prices = $shippingMethod->getPrices()->first();
                if ($prices) {
                    $shippingPrice = $prices->getCurrencyPrice()->first();

                    if ($shippingPrice) {
                        $shippingPrice = $shippingPrice->getGross();

                        if ($shippingPrice == $price) {
                            return $shippingMethod->getId();
                        }
                    }
                }
            }

            return $this->shippingMethodHelper->createShippingMethod($deliveryMethodName, $price, $context);
        }

        return $salesChannel->getShippingMethodId();
    }

    private function getPaidStatusId(Context $context)
    {
        $states = $this->stateMachineRegistry->getStateMachine(OrderTransactionStates::STATE_MACHINE, $context)->getStates();

        if (null !== $states) {
            /** @var StateMachineStateEntity $state */
            foreach ($states as $state) {
                if ($state->getTechnicalName() === 'paid') return $state->getId();
             }
        }

        return $this->stateMachineRegistry->getInitialState(OrderTransactionStates::STATE_MACHINE, $context)->getId();
    }

    private function preparePrice(float $orderPriceNet, float $orderPrice, array $orderTaxData): CartPrice
    {
        return new CartPrice(
            $orderPriceNet,
            $orderPrice,
            $orderPrice,
            new CalculatedTaxCollection($this->prepareCalculatedTaxCollection($orderTaxData)),
            new TaxRuleCollection($this->prepareTaxRuleCollection($orderTaxData)),
            CartPrice::TAX_STATE_GROSS
        );
    }

    private function prepareCalculatedTaxCollection(array $orderTaxData): array
    {
        $calculatedTaxCollection = [];

        foreach ($orderTaxData as $taxRate => $taxData) {
            $calculatedTaxCollection[] = new CalculatedTax($taxData['tax'], $taxRate, $taxData['price']);
        }

        return $calculatedTaxCollection;
    }

    private function prepareTaxRuleCollection(array $orderTaxData): array
    {
        $taxRule = [];

        foreach ($orderTaxData as $taxRate => $taxData) {
            $taxRule[] = new TaxRule($taxRate);
        }

        return $taxRule;
    }

    private function getCurrency(string $isoCode, Context $context): ?CurrencyEntity
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('isoCode', $isoCode));

        return $this->currencyRepository
            ->search($criteria, $context)
            ->first();
    }

    private function prepareOrderCustomer(
        CustomerEntity $customer,
        OrderAddRequest $orderAddRequest,
        SalesChannelContext $salesChannelContext
    ) {
        $context = $salesChannelContext->getContext();
        $billingAddressId = Uuid::randomHex();
        $name = $this->prepareName($orderAddRequest->getDeliveryFullname());

        return [
            'orderCustomer' => [
                'email' => $customer->getEmail(),
                'firstName' => $customer->getFirstName(),
                'lastName' => $customer->getLastName(),
                'salutationId' => $customer->getSalutationId(),
                'title' => $customer->getTitle(),
                'customerNumber' => $customer->getCustomerNumber(),
                'customerId' => $customer->getId()
            ],
            'billingAddressId' => $billingAddressId,
            'addresses' => [
                [
                    'salutationId' => $customer->getDefaultBillingAddress()->getSalutationId(),
                    'firstName' => $customer->getDefaultBillingAddress()->getFirstName(),
                    'lastName' => $customer->getDefaultBillingAddress()->getLastName(),
                    'zipcode' => $customer->getDefaultBillingAddress()->getZipcode(),
                    'city' => $customer->getDefaultBillingAddress()->getCity(),
                    'street' => $customer->getDefaultBillingAddress()->getStreet(),
                    'countryId' => $customer->getDefaultBillingAddress()->getCountryId(),
                    'phoneNumber' => $orderAddRequest->getPhone(),
                    'id' => $billingAddressId,
                ]
            ],
            'deliveries' => [
                [
                    'stateId' => $this->stateMachineRegistry->getInitialState(OrderDeliveryStates::STATE_MACHINE, $context)->getId(),
                    'shippingMethodId' => $this->prepareShippingMethod($orderAddRequest->getDeliveryMethod(), $orderAddRequest->getDeliveryPrice(), $salesChannelContext->getSalesChannel(), $context),
                    'shippingCosts' => new CalculatedPrice($orderAddRequest->getDeliveryPrice(), $orderAddRequest->getDeliveryPrice(), new CalculatedTaxCollection(), new TaxRuleCollection()),
                    'shippingDateEarliest' => date(DATE_ISO8601),
                    'shippingDateLatest' => date(DATE_ISO8601),
                    'shippingOrderAddress' => [
                        'salutationId' => $customer->getSalutationId(),
                        'firstName' => $name['firstName'],
                        'lastName' => $name['lastName'],
                        'zipcode' => $orderAddRequest->getDeliveryPostcode(),
                        'city' => $orderAddRequest->getDeliveryCity(),
                        'street' => $orderAddRequest->getDeliveryAddress(),
                        'countryId' => $this->customerReader->getCountryId(
                            $orderAddRequest->getDeliveryCountryCode(),
                            $context
                        )
                    ],
                ],
            ],
        ];
    }

    private function createOrderCustomer(OrderAddRequest $orderAddRequest, SalesChannelContext $salesChannelContext)
    {
        $context = $salesChannelContext->getContext();
        $addressId = Uuid::randomHex();
        $billingAddressId = Uuid::randomHex();
        $salutationId = $this->customerReader->getDefaultSalutation($context);
        $customerNumber = $this->numberRangeValueGenerator->getValue('customer', $context, $salesChannelContext->getSalesChannelId());
        $salesChannel = $salesChannelContext->getSalesChannel();

        $name = $this->prepareName($orderAddRequest->getInvoiceFullname());
        $shippingName = $this->prepareName($orderAddRequest->getDeliveryFullname());

        return [
            'orderCustomer' => [
                'email' => $orderAddRequest->getEmail(),
                'company' => $orderAddRequest->getInvoiceCompany(),
                'firstName' => $name['firstName'],
                'lastName' => $name['lastName'],
                'salutationId' => $salutationId,
                'title' => '',
                'customerNumber' => $customerNumber,
                'customer' => [
                    'email' => $orderAddRequest->getEmail(),
                    'firstName' => $name['firstName'],
                    'lastName' => $name['lastName'],
                    'salutationId' => $salutationId,
                    'title' => '',
                    'customerNumber' => $customerNumber,
                    'guest' => true,
                    'groupId' => $salesChannel->getCustomerGroupId(),
                    'defaultPaymentMethodId' => $salesChannel->getPaymentMethodId(),
                    'salesChannelId' => $salesChannelContext->getSalesChannelId(),
                    'defaultBillingAddressId' => $addressId,
                    'defaultShippingAddressId' => $addressId,
                    'addresses' => [
                        [
                            'id' => $addressId,
                            'salutationId' => $salutationId,
                            'firstName' => $name['firstName'],
                            'lastName' => $name['lastName'],
                            'zipcode' => $orderAddRequest->getInvoicePostcode(),
                            'city' => $orderAddRequest->getInvoiceCity(),
                            'street' => $orderAddRequest->getInvoiceAddress(),
                            'phoneNumber' => $orderAddRequest->getPhone(),
                            'countryId' => $this->customerReader->getCountryId(
                                $orderAddRequest->getInvoiceCountryCode(),
                                $context
                            ),
                        ],
                    ],
                ],
            ],
            'billingAddressId' => $billingAddressId,
            'addresses' => [
                [
                    'salutationId' => $salutationId,
                    'firstName' => $name['firstName'],
                    'lastName' => $name['lastName'],
                    'zipcode' => $orderAddRequest->getInvoicePostcode(),
                    'city' => $orderAddRequest->getInvoiceCity(),
                    'street' => $orderAddRequest->getInvoiceAddress(),
                    'countryId' => $this->customerReader->getCountryId(
                        $orderAddRequest->getInvoiceCountryCode(),
                        $context
                    ),
                    'phoneNumber' => $orderAddRequest->getPhone(),
                    'id' => $billingAddressId,
                ]
            ],
            'deliveries' => [
                [
                    'stateId' => $this->stateMachineRegistry->getInitialState(OrderDeliveryStates::STATE_MACHINE, $context)->getId(),
                    'shippingMethodId' => $this->prepareShippingMethod($orderAddRequest->getDeliveryMethod(), $orderAddRequest->getDeliveryPrice(), $salesChannel, $context),
                    'shippingCosts' => new CalculatedPrice($orderAddRequest->getDeliveryPrice(), $orderAddRequest->getDeliveryPrice(), new CalculatedTaxCollection(), new TaxRuleCollection()),
                    'shippingDateEarliest' => date(DATE_ISO8601),
                    'shippingDateLatest' => date(DATE_ISO8601),
                    'shippingOrderAddress' => [
                        'salutationId' => $salutationId,
                        'firstName' => $shippingName['firstName'],
                        'lastName' => $shippingName['lastName'],
                        'zipcode' => $orderAddRequest->getDeliveryPostcode(),
                        'city' => $orderAddRequest->getDeliveryCity(),
                        'street' => $orderAddRequest->getDeliveryAddress(),
                        'phoneNumber' => $orderAddRequest->getPhone(),
                        'countryId' => $this->customerReader->getCountryId(
                            $orderAddRequest->getDeliveryCountryCode(),
                            $context
                        )
                    ],
                ],
            ]
        ];
    }
}
