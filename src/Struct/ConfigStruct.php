<?php

namespace Crehler\BaseLinkerShopsApi\Struct;


use DateTime;

class ConfigStruct
{
    protected ?array $codPaymentMethodIds = null;
    protected ?string $shopsApiPassword = null;
    protected ?DateTime $orderStartDate = null;

    /**
     * @return array|null
     */
    public function getCodPaymentMethodIds(): ?array
    {
        return $this->codPaymentMethodIds;
    }

    /**
     * @param array|null $codPaymentMethodIds
     * @return ConfigStruct
     */
    public function setCodPaymentMethodIds(?array $codPaymentMethodIds): ConfigStruct
    {
        $this->codPaymentMethodIds = $codPaymentMethodIds;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShopsApiPassword(): ?string
    {
        return $this->shopsApiPassword;
    }

    /**
     * @param string|null $shopsApiPassword
     * @return ConfigStruct
     */
    public function setShopsApiPassword(?string $shopsApiPassword): ConfigStruct
    {
        $this->shopsApiPassword = $shopsApiPassword;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getOrderStartDate(): ?DateTime
    {
        return $this->orderStartDate;
    }

    /**
     * @param DateTime|null $orderStartDate
     * @return ConfigStruct
     */
    public function setOrderStartDate(?DateTime $orderStartDate): ConfigStruct
    {
        $this->orderStartDate = $orderStartDate;
        return $this;
    }
}
