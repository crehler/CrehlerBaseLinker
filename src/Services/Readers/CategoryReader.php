<?php

namespace Crehler\BaseLinkerShopsApi\Services\Readers;


use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CategoryReader
{
    private EntityRepository $categoryRepository;

    public function __construct(EntityRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function getProductsCategories(SalesChannelContext $salesChannelContext): array
    {
        $navigationId = $salesChannelContext->getSalesChannel()->getNavigationCategoryId();

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('path', $navigationId));
        $categoryList = $this->categoryRepository->search($criteria, $salesChannelContext->getContext());

        return $this->formatCategoryList($categoryList->getEntities());
    }

    private function formatCategoryList(EntityCollection $categoryCollection): array
    {
        $data = [];

        /** @var CategoryEntity $categoryEntity */
        foreach ($categoryCollection as $categoryEntity) {
            $categoryName = $this->prepareCategoryName($categoryEntity->getBreadcrumb());
            $data[$categoryEntity->getAutoIncrement()] = $categoryName;
        }

        ksort($data);

        return $data;
    }

    private function prepareCategoryName(array $breadcrumb): string
    {
        unset($breadcrumb[0]);
        return implode('/', $breadcrumb);
    }
}
