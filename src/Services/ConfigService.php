<?php

namespace Crehler\BaseLinkerShopsApi\Services;

use Crehler\BaseLinkerShopsApi\Struct\ConfigStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Class ConfigService
 */
class ConfigService
{
    protected string $pluginName;
    private SystemConfigService $systemConfigService;

    public function __construct(
        string $pluginName,
        SystemConfigService $systemConfigService
    ) {
        $this->pluginName = $pluginName;
        $this->systemConfigService = $systemConfigService;
    }

    public function getShopsConfig(?string $salesChannelId = null): ConfigStruct
    {
        $config = ($this->systemConfigService->get($this->pluginName, $salesChannelId))['config'];

        return (new ConfigStruct())
            ->setCodPaymentMethodIds($config['codPaymentMethodIds'])
            ->setShopsApiPassword($config['shopsApiPassword']);
    }
}
