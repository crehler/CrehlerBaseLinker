<?php

namespace Crehler\BaseLinkerShopsApi\Services\Readers;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class CustomerReader
{
    private EntityRepositoryInterface $countryRepository;
    private EntityRepositoryInterface $customerRepository;
    private EntityRepositoryInterface $salutationRepository;

    public function __construct(
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $customerRepository,
        EntityRepositoryInterface $salutationRepository
    ) {
        $this->countryRepository = $countryRepository;
        $this->customerRepository = $customerRepository;
        $this->salutationRepository = $salutationRepository;
    }

    public function getCustomer(string $email, Context $context): ?CustomerEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $criteria->addAssociation('addresses');
        $criteria->addAssociation('defaultBillingAddress');
        $criteria->addAssociation('defaultShippingAddress');

        return $this->customerRepository->search($criteria, $context)->first();
    }

    public function getCountryId(string $iso, Context $context): ?string
    {
        $criteria = new Criteria();

        if (strlen($iso) === 3) {
            $criteria->addFilter(new EqualsFilter('iso3', $iso));
        } elseif (strlen($iso) === 2) {
            $criteria->addFilter(new EqualsFilter('iso', $iso));
        } else {
            return null;
        }

        return $this->countryRepository->searchIds($criteria, $context)->firstId();
    }

    public function getDefaultSalutation(Context $context): ?string
    {
        return $this->salutationRepository->searchIds(new Criteria(), $context)->firstId();
    }
}


