<?php

namespace Crehler\BaseLinkerShopsApi\Services\Readers;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductReader
{
    private EntityRepositoryInterface $productRepository;
    private EntityRepositoryInterface $categoryRepository;
    private SalesChannelRepository $salesChannelProductRepository;
    private PropertyReader $propertyReader;

    public function __construct(
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $categoryRepository,
        SalesChannelRepository $salesChannelProductRepository,
        PropertyReader $propertyReader
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->salesChannelProductRepository = $salesChannelProductRepository;
        $this->propertyReader = $propertyReader;
    }

    public function getProductList(
        string $categoryId,
        ?string $filterLimit,
        ?string $filterSort,
        ?string $filterId,
        ?string $filterIdsList,
        ?string $filterEan,
        ?string $filterSku,
        ?string $filterName,
        ?string $filterPriceFrom,
        ?string $filterPriceTo,
        ?string $filterQuantityFrom,
        ?string $filterQuantityTo,
        ?string $filterAvailable,
        ?string $page,
        SalesChannelContext $salesChannelContext
    ): array {
        $categoryId = $categoryId ?: 'all';

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product.parentId', null));
        $criteria->addFilter(
            new EqualsFilter('product.visibilities.salesChannelId', $salesChannelContext->getSalesChannelId())
        );
        $criteria->addAssociation('product.categoriesRo');

        $criteria->setLimit($filterLimit ?: 100);
        if ($page && $page > 1) {
            $criteria->setOffset(($page-1) * $criteria->getLimit());
        }

        if ($filterAvailable) {
            $criteria->addFilter(new ProductAvailableFilter($salesChannelContext->getSalesChannelId()));
        }

        if ($filterSort) {
            $criteria = $this->addSortFilter($criteria, $filterSort);
        }

        if ($filterId) {
            $criteria->addFilter(new EqualsFilter('product.autoIncrement', $filterId));
        }

        if ($filterIdsList) {
            $productAutoIncrementIds = explode(',', $filterIdsList);
            if (!empty($productAutoIncrementIds)) {
                $criteria->addFilter(new EqualsAnyFilter('product.autoIncrement', $productAutoIncrementIds));
            }
        }

        if ($filterEan) {
            $criteria->addFilter(new ContainsFilter('product.ean', $filterEan));
        }

        if ($filterSku) {
            $criteria->addFilter(new ContainsFilter('product.productNumber', $filterSku));
        }

        if ($filterName) {
            $criteria->addFilter(new ContainsFilter('product.name', $filterName));
        }

        if ($filterPriceFrom || $filterPriceTo) {
            $criteria = $this->addRangeFilter($criteria, 'product.price', $filterPriceFrom, $filterPriceTo);
        }

        if ($filterQuantityFrom || $filterQuantityTo) {
            $criteria = $this->addRangeFilter($criteria, 'product.availableStock', $filterQuantityFrom, $filterQuantityTo);
        }

        if ($categoryId !== 'all') {
            $categoryCriteria = new Criteria();
            $categoryCriteria->addFilter(
                new EqualsFilter('autoIncrement', $categoryId)
            );

            /** @var CategoryEntity|null $category */
            $category = $this->categoryRepository->search($categoryCriteria, $salesChannelContext->getContext())->first();

            $criteria->addFilter(
                new EqualsFilter('product.categoriesRo.id', $category->getId())
            );
        }

        $productList = $this->salesChannelProductRepository->search($criteria, $salesChannelContext);

        if (!$filterLimit) {
            $totalCriteria = clone $criteria;
            $totalCriteria->setLimit(null);
            $totalCriteria->setOffset(null);
            $total = $this->salesChannelProductRepository->searchIds($totalCriteria, $salesChannelContext)->getTotal();
            $pages = (int) ceil($total / $criteria->getLimit());
        } else {
            $pages = 0;
        }

        return $this->formatProductList($productList->getEntities(), $pages);
    }

    public function getProductsData(string $productIds, SalesChannelContext $salesChannelContext): array
    {
        $productIds = array_filter(explode(',', $productIds));
        if (empty($productIds)) return [];

        $productsData = [];

        foreach ($productIds as $productId) {
            $product = $this->readStorefrontProduct(null, $productId, $salesChannelContext);
            if (!$product instanceof SalesChannelProductEntity) continue;
            $product->setSortedProperties(
                $this->propertyReader->sortProperties($product)
            );

            $manName = '';
            $manImage = '';

            if ($product->getManufacturer() instanceof ProductManufacturerEntity) {
                $manName = $product->getManufacturer()->getTranslation('name');
                if ($product->getManufacturer()->getMedia()) {
                    $manImage = $product->getManufacturer()->getMedia()->getUrl();
                }
            }

            $images = $this->prepareImages($product->getCover(), $product->getMedia());
            $properties = $this->prepareFeatures($product->getSortedProperties());

            if ($product->getCategories()->first() === null) continue;

            $productsData[$product->getAutoIncrement()] = [
                'sku' => $product->getProductNumber(),
                'ean' => $product->getEan(),
                'name' => $product->getTranslation('name'),
                'quantity' => $product->getAvailableStock(),
                'price' => $product->getCalculatedPrice()->getUnitPrice(),
                'tax' => $product->getCalculatedPrice()->getTaxRules()->first()->getTaxRate(),
                'weight' => $product->getWeight(),
                'description' => $product->getTranslation('description'),
                'description_extra1' => $product->getTranslation('metaDescription'),
                'description_extra2' => '',
                'man_name' => $manName,
                'man_image' => $manImage,
                'category_id' => $product->getCategories()->first()->getAutoIncrement(),
                'category_name' => $product->getCategories()->first()->getTranslation('name'),
                'images' => $images,
                'features' => $properties,
            ];

            $variantsIds = $this->getVariants($product->getId(), $salesChannelContext->getContext());

            if (!empty($variantsIds)) {
                $sumAvailableStock = 0;

                foreach ($variantsIds as $variantsId) {
                    $variant = $this->readStorefrontProduct($variantsId,null, $salesChannelContext);

                    if (!$variant instanceof SalesChannelProductEntity) {
                        continue;
                    }

                    if ($variant->getAvailableStock() > 0) {
                        $sumAvailableStock += $variant->getAvailableStock();
                    }

                    $images = $this->prepareImages($variant->getCover(), $variant->getMedia());
                    $properties = $this->prepareFeatures($variant->getSortedProperties());

                    $variantNames = [];
                    foreach ($variant->getOptions() as $option) {
                        $variantNames[] = $option->getTranslation('name');
                    }
                    $productsData[$product->getAutoIncrement()]['variants'][$variant->getAutoIncrement()] = [
                        'full_name' => $variant->getTranslation('name'),
                        'name' => implode(' ', $variantNames),
                        'price' => $variant->getCalculatedPrice()->getUnitPrice(),
                        'quantity' => $variant->getAvailableStock(),
                        'sku' => $variant->getProductNumber(),
                        'ean' => $variant->getEan(),
                        'features' => $properties,
                        'images' => $images,
                    ];
                }

                $productsData[$product->getAutoIncrement()]['quantity'] = $sumAvailableStock;
            }
        }

        return $productsData;
    }

    public function getProductsPrices(?string $page, SalesChannelContext $salesChannelContext): array
    {
        return $this->getProductsInformation($page, 'price', $salesChannelContext);
    }

    public function getProductsQuantity(?string $page, SalesChannelContext $salesChannelContext): array
    {
        return $this->getProductsInformation($page, 'quantity', $salesChannelContext);
    }

    public function getProduct(string $productId, Context $context): ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria
            ->addAssociation('tax')
            ->addAssociation('media')
            ->addAssociation('categories')
            ->addAssociation('mainCategories')
            ->addAssociation('properties')
            ->addAssociation('properties.group')
            ->addAssociation('manufacturer');

        return $this->productRepository
            ->search($criteria, $context)
            ->get($productId);
    }

    private function formatProductList(EntityCollection $productList, int $pages): array
    {
        $data = [];

        /** @var SalesChannelProductEntity $productEntity */
        foreach ($productList as $productEntity) {
            $data[$productEntity->getAutoIncrement()] = [
                'name' => $productEntity->getTranslation('name'),
                'quantity' => $productEntity->getAvailableStock(),
                'price' => $productEntity->getCalculatedPrice()->getUnitPrice(),
                'ean' => $productEntity->getEan(),
                'sku' => $productEntity->getProductNumber()
            ];
        }

        if ($pages) {
            $data['pages'] = $pages;
        }

        return $data;
    }

    public function getProductsInformation(?string $page, string $type, SalesChannelContext $salesChannelContext): array
    {
        $productsPrices = [];
        $parentIds = [];

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product.parentId', null));
        $criteria->addFilter(
            new EqualsFilter('product.visibilities.salesChannelId', $salesChannelContext->getSalesChannelId())
        );
        $mainProducts = $this->salesChannelProductRepository->search($criteria, $salesChannelContext)->getEntities();
        /** @var SalesChannelProductEntity $mainProduct */
        foreach ($mainProducts as $mainProduct) {
            $data = '';
            if ($type === 'price') {
                $data = $mainProduct->getCalculatedPrice()->getUnitPrice();
            } elseif ($type === 'quantity') {
                $data = $mainProduct->getAvailableStock();
            }
            $productsPrices[$mainProduct->getAutoIncrement()][0] = $data;
            $parentIds[$mainProduct->getId()] = $mainProduct->getAutoIncrement();
        }

        $variantCriteria = new Criteria();
        $variantCriteria->addFilter(
            new NotFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('product.parentId', null)
            ]
        ));
        $variantCriteria->addFilter(
            new EqualsFilter('product.visibilities.salesChannelId', $salesChannelContext->getSalesChannelId())
        );
        $variantProducts = $this->salesChannelProductRepository->search($variantCriteria, $salesChannelContext)->getEntities();
        /** @var SalesChannelProductEntity $variantProduct */
        foreach ($variantProducts as $variantProduct) {
            $parentAutoIncrementId = $parentIds[$variantProduct->getParentId()] ?? null;
            if (null === $parentAutoIncrementId) continue;
            $variantData = '';
            if ($type === 'price') {
                $variantData = $variantProduct->getCalculatedPrice()->getUnitPrice();
            } elseif ($type === 'quantity') {
                $variantData = $variantProduct->getAvailableStock();
            }
            $productsPrices[$parentAutoIncrementId][$variantProduct->getAutoIncrement()] = $variantData;
        }

        return $productsPrices;
    }

    public function readStorefrontProduct(
        ?string $productId,
        ?string $productAutoIncrementId,
        SalesChannelContext $salesChannelContext
    ): ?SalesChannelProductEntity {
        if ($productId) {
            $criteria = new Criteria([$productId]);
        } elseif ($productAutoIncrementId) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('product.autoIncrement', $productAutoIncrementId));
        } else {
            return null;
        }

        $criteria->addAssociation('media');
        $criteria->addAssociation('options');
        $criteria->getAssociation('properties')->addAssociation('group');
        $criteria->addAssociation('categories');
        $criteria->getAssociation('manufacturer')->addAssociation('media');

        return $this->salesChannelProductRepository->search($criteria, $salesChannelContext)->first();
    }

    private function prepareFeatures(?PropertyGroupCollection $propertyGroupCollection): array
    {
        $features = [];

        /** @var PropertyGroupEntity $propertyGroupEntity */
        foreach ($propertyGroupCollection as $propertyGroupEntity) {
            $feature = [];

            $feature[0] = $propertyGroupEntity->getTranslation('name');
            $options = [];
            foreach ($propertyGroupEntity->getOptions() as $option) {
                $options[] = $option->getTranslation('name');
            }

            $feature[1] = implode('|',$options);

            if (!empty($feature)) {
                $features[] = $feature;
            }
        }

        return $features;
    }

    private function prepareImages(?ProductMediaEntity $cover, ProductMediaCollection $productMediaCollection): array
    {
        $images = [];
        if ($cover instanceof ProductMediaEntity && $cover->getMedia() !== null) {
            $images[] = $cover->getMedia()->getUrl();
        }

        foreach ($productMediaCollection as $mediaEntity) {
            if ($mediaEntity->getMedia() !== null) {
                if (!in_array($mediaEntity->getMedia()->getUrl(), $images)) {
                    $images[] = $mediaEntity->getMedia()->getUrl();
                }
            }
        }

        return $images;
    }

    private function addRangeFilter(Criteria $criteria, string $field, string $form, string $to): Criteria
    {
        $params = [];

        if ($form) {
            $params[RangeFilter::GTE] = $form;
        }

        if ($to) {
            $params[RangeFilter::LTE] = $to;
        }

        $criteria->addFilter(
            new RangeFilter($field , $params)
        );

        return $criteria;
    }

    private function addSortFilter(Criteria $criteria, string $filterSort): Criteria
    {
        $criteria->addSorting(
            new FieldSorting($this->getFieldName($filterSort), $this->getFieldOrder($filterSort))
        );

        return $criteria;
    }

    private function getFieldName(string $fieldSort): string
    {
        if (strpos($fieldSort, 'id') !== false) {
            return 'product.autoIncrement';
        } elseif (strpos($fieldSort, 'quantity') !== false) {
            return 'product.quantity';
        } elseif (strpos($fieldSort, 'price') !== false) {
            return 'product.price';
        }

        return 'product.autoIncrement';
    }

    private function getFieldOrder(string $fieldSort): string
    {
        if (strpos($fieldSort, 'DESC') !== false) {
            return FieldSorting::DESCENDING;
        }

        return FieldSorting::ASCENDING;
    }

    private function getVariants(string $parentId, Context $context): ?array
    {
        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('parentId', $parentId));

        return $this->productRepository
            ->search($criteria, $context)
            ->getIds();
    }
}


