<?php

namespace Prospektweb\UiSeoOptimT\Service;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use CIBlockProperty;
use Exception;

final class PropertyManager
{
    private const TRACKED_OPTION_KEY = 'MANAGED_PROPERTIES';

    /** @return array<string, mixed> */
    public function ensureProperties(int $productsIblockId, int $offersIblockId = 0): array
    {
        if (!Loader::includeModule('iblock')) {
            throw new Exception('Модуль iblock не подключен.');
        }

        $created = [];
        $updated = [];

        if ($productsIblockId > 0) {
            $result = $this->ensureProperty($productsIblockId, [
                'NAME' => 'TR Case',
                'CODE' => 'TR_CASE',
                'PROPERTY_TYPE' => 'S',
                'MULTIPLE' => 'Y',
                'IS_REQUIRED' => 'N',
                'ACTIVE' => 'Y',
            ]);
            if ($result['action'] === 'created') {
                $created[] = $result['code'];
            } else {
                $updated[] = $result['code'];
            }
        }

        // Заготовка для будущих свойств ТП
        if ($offersIblockId > 0) {
            // no-op
        }

        return ['created' => $created, 'updated' => $updated];
    }

    public function removeManagedProperties(int $productsIblockId, int $offersIblockId = 0): void
    {
        if (!Loader::includeModule('iblock')) {
            throw new Exception('Модуль iblock не подключен.');
        }

        $tracked = $this->getTrackedProperties();
        foreach ($tracked as $item) {
            $iblockId = (int)($item['IBLOCK_ID'] ?? 0);
            $code = (string)($item['CODE'] ?? '');

            if ($iblockId <= 0 || $code === '') {
                continue;
            }

            $property = $this->findProperty($iblockId, $code);
            if (!empty($property['ID'])) {
                CIBlockProperty::Delete((int)$property['ID']);
            }
        }

        \Bitrix\Main\Config\Option::delete(ModuleConfig::MODULE_ID, ['name' => self::TRACKED_OPTION_KEY]);
    }

    /** @param array<string, string> $fields */
    private function ensureProperty(int $iblockId, array $fields): array
    {
        $code = (string)$fields['CODE'];
        $property = $this->findProperty($iblockId, $code);
        $iblockProperty = new CIBlockProperty();

        if (!empty($property['ID'])) {
            $ok = $iblockProperty->Update((int)$property['ID'], array_merge($fields, ['IBLOCK_ID' => $iblockId]));
            if (!$ok) {
                throw new Exception('Не удалось обновить свойство ' . $code . ': ' . (string)$iblockProperty->LAST_ERROR);
            }

            $this->trackProperty($iblockId, $code);
            return ['action' => 'updated', 'code' => $code];
        }

        $newId = $iblockProperty->Add(array_merge($fields, ['IBLOCK_ID' => $iblockId]));
        if (!$newId) {
            throw new Exception('Не удалось создать свойство ' . $code . ': ' . (string)$iblockProperty->LAST_ERROR);
        }

        $this->trackProperty($iblockId, $code);
        return ['action' => 'created', 'code' => $code];
    }

    /** @return array<string, mixed> */
    private function findProperty(int $iblockId, string $code): array
    {
        $res = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => $code]);
        $property = $res ? $res->Fetch() : false;

        return is_array($property) ? $property : [];
    }

    private function trackProperty(int $iblockId, string $code): void
    {
        $tracked = $this->getTrackedProperties();
        foreach ($tracked as $item) {
            if ((int)$item['IBLOCK_ID'] === $iblockId && (string)$item['CODE'] === $code) {
                return;
            }
        }

        $tracked[] = ['IBLOCK_ID' => $iblockId, 'CODE' => $code];
        \Bitrix\Main\Config\Option::set(ModuleConfig::MODULE_ID, self::TRACKED_OPTION_KEY, json_encode($tracked, JSON_THROW_ON_ERROR));
    }

    /** @return array<int, array<string, mixed>> */
    private function getTrackedProperties(): array
    {
        $raw = \Bitrix\Main\Config\Option::get(ModuleConfig::MODULE_ID, self::TRACKED_OPTION_KEY, '[]');
        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : [];
        } catch (ArgumentException|Exception) {
            return [];
        }
    }
}
