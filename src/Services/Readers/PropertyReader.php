<?php


namespace Crehler\BaseLinkerShopsApi\Services\Readers;


use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Content\Property\PropertyGroupCollection;

class PropertyReader
{
    public function sortProperties(SalesChannelProductEntity $product): PropertyGroupCollection
    {
        $properties = $product->getProperties();
        if ($properties === null) {
            return new PropertyGroupCollection();
        }

        $sorted = [];
        foreach ($properties as $option) {
            $origin = $option->getGroup();

            if (!$origin || !$origin->getVisibleOnProductDetailPage()) {
                continue;
            }
            $group = clone $origin;

            $groupId = $group->getId();
            if (\array_key_exists($groupId, $sorted)) {
                \assert($sorted[$groupId]->getOptions() !== null);
                $sorted[$groupId]->getOptions()->add($option);

                continue;
            }

            if ($group->getOptions() === null) {
                $group->setOptions(new PropertyGroupOptionCollection());
            }

            \assert($group->getOptions() !== null);
            $group->getOptions()->add($option);

            $sorted[$groupId] = $group;
        }

        $collection = new PropertyGroupCollection($sorted);
        $collection->sortByPositions();
        $collection->sortByConfig();

        return $collection;
    }
}
