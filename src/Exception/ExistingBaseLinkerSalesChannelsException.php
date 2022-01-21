<?php declare(strict_types=1);

namespace Crehler\BaseLinkerShopsApi\Exception;

use Shopware\Core\Framework\ShopwareHttpException;

class ExistingBaseLinkerSalesChannelsException extends ShopwareHttpException
{
    /**
     * @param string[] $names
     */
    public function __construct(int $amount, array $names)
    {
        $quantityWords = [
            $amount === 1 ? 'is' : 'are',
            $amount === 1 ? 'channel' : 'channels',
        ];

        parent::__construct(sprintf(
            'There %s still %d BaseLinker sales %s left. [%s]',
            $quantityWords[0],
            $amount,
            $quantityWords[1],
            implode(', ', $names)
        ));
    }

    public function getErrorCode(): string
    {
        return 'CREHLER_BASE_LINKER__EXISTING_SALES_CHANNELS';
    }
}
