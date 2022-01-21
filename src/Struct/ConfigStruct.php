<?php

namespace Crehler\BaseLinkerShopsApi\Struct;


class ConfigStruct
{
    protected ?array $codPaymentMethodIds = null;
    protected ?string $shopsApiPassword = null;

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
}
