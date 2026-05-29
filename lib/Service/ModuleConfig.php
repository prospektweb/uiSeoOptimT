<?php

namespace Prospektweb\UiSeoOptimT\Service;

use Bitrix\Main\Config\Option;

final class ModuleConfig
{
    public const MODULE_ID = 'prospektweb.uiseooptimt';

    public const ENABLED = 'ENABLED';
    public const PRODUCTS_IBLOCK_ID = 'PRODUCTS_IBLOCK_ID';
    public const OFFERS_IBLOCK_ID = 'OFFERS_IBLOCK_ID';

    public static function isEnabled(): bool
    {
        return Option::get(self::MODULE_ID, self::ENABLED, 'Y') === 'Y';
    }

    public static function setEnabled(bool $enabled): void
    {
        Option::set(self::MODULE_ID, self::ENABLED, $enabled ? 'Y' : 'N');
    }

    public static function getProductsIblockId(): int
    {
        return (int)Option::get(self::MODULE_ID, self::PRODUCTS_IBLOCK_ID, '0');
    }

    public static function setProductsIblockId(int $iblockId): void
    {
        Option::set(self::MODULE_ID, self::PRODUCTS_IBLOCK_ID, (string)max(0, $iblockId));
    }

    public static function getOffersIblockId(): int
    {
        return (int)Option::get(self::MODULE_ID, self::OFFERS_IBLOCK_ID, '0');
    }

    public static function setOffersIblockId(int $iblockId): void
    {
        Option::set(self::MODULE_ID, self::OFFERS_IBLOCK_ID, (string)max(0, $iblockId));
    }
}
