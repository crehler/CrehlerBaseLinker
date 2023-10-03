<?php

namespace Crehler\BaseLinkerShopsApi\Component\BaseLinker\StoreApiStruct\Order;


/**
 * Class OrderProduct
 */
class OrderAddProduct
{
    /**
     * identyfikator produktu w podłączonym sklepie
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * identyfikator wariantu produktu (0 jeśli produkt główny)
     * @var int|null
     */
    protected ?int $variant_id = null;

    /**
     * SKU produktu (opcjonalnie)
     * @var string|null
     */
    protected ?string $sku = null;

    /**
     * nazwa produktu (używana jeśli nie można pobrać jej z bazy na podstawie id)
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * jednostkowa cena brutto produktu
     * @var float|null
     */
    protected ?float $price = null;

    /**
     * akupiona ilość sztuk
     * @var int|null
     */
    protected ?int $quantity = null;

    /**
     * numer aukcji
     * @var int|null
     */
    protected ?int $auction_id = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return OrderAddProduct
     */
    public function setId(?int $id): OrderAddProduct
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getVariantId(): ?int
    {
        return $this->variant_id;
    }

    /**
     * @param int|null $variant_id
     * @return OrderAddProduct
     */
    public function setVariantId(?int $variant_id): OrderAddProduct
    {
        $this->variant_id = $variant_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSku(): ?string
    {
        return $this->sku;
    }

    /**
     * @param string|null $sku
     * @return OrderAddProduct
     */
    public function setSku(?string $sku): OrderAddProduct
    {
        $this->sku = $sku;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return OrderAddProduct
     */
    public function setName(?string $name): OrderAddProduct
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * @param float|null $price
     * @return OrderAddProduct
     */
    public function setPrice(?float $price): OrderAddProduct
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /**
     * @param int|null $quantity
     * @return OrderAddProduct
     */
    public function setQuantity(?int $quantity): OrderAddProduct
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAuctionId(): ?int
    {
        return $this->auction_id;
    }

    /**
     * @param int|null $auction_id
     * @return OrderAddProduct
     */
    public function setAuctionId(?int $auction_id): OrderAddProduct
    {
        $this->auction_id = $auction_id;
        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function assign(array $options)
    {
        foreach ($options as $key => $value) {
            if ($key === 'id' && method_exists($this, 'setId')) {
                $this->setId((int) $value);
                continue;
            }

            if (in_array($key, [
                'variant_id',
                'quantity',
                'auction_id',
            ])) {
                $value = $value ? (int)$value : null;
            }

            if (in_array($key, [
                'price'
            ])) {
                $value = $value ? (float)$value : null;
            }

            try {
                $this->$key = $value;
            } catch (\Exception $error) {
                // nth
            }
        }

        return $this;

    }
}