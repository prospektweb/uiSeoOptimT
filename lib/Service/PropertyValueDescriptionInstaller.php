<?php

namespace Prospektweb\PropValManager\Service;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\UserFieldTable;
use CUserTypeEntity;
use Exception;

final class PropertyValueDescriptionInstaller
{
    public const HL_BLOCK_NAME = 'SkuPropertyValueDescriptions';
    public const HL_BLOCK_TABLE_NAME = 'b_sku_property_value_descriptions';
    /** @return array<string, mixed> */
    public function ensure(): array
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new Exception('Модуль highloadblock не подключен.');
        }

        $hlBlock = $this->getHighloadBlock();
        $created = false;

        if (!$hlBlock) {
            $result = HighloadBlockTable::add([
                'NAME' => self::HL_BLOCK_NAME,
                'TABLE_NAME' => self::HL_BLOCK_TABLE_NAME,
            ]);

            if (!$result->isSuccess()) {
                throw new Exception('Не удалось создать HL-блок описаний значений свойств: ' . implode('; ', $result->getErrorMessages()));
            }

            $hlBlock = [
                'ID' => (int)$result->getId(),
                'NAME' => self::HL_BLOCK_NAME,
                'TABLE_NAME' => self::HL_BLOCK_TABLE_NAME,
            ];
            $created = true;
        }

        $hlBlockId = (int)$hlBlock['ID'];
        ModuleConfig::setPropertyDescriptionsHlBlockId($hlBlockId);

        $createdFields = [];
        foreach ($this->getUserFields() as $fieldName => $field) {
            if ($this->ensureUserField($hlBlockId, $fieldName, $field) === 'created') {
                $createdFields[] = $fieldName;
            }
        }

        return [
            'hl_block_id' => $hlBlockId,
            'created' => $created,
            'created_fields' => $createdFields,
        ];
    }

    public function uninstall(bool $removeData = false): void
    {
        if (!$removeData || !Loader::includeModule('highloadblock')) {
            return;
        }

        $hlBlock = $this->getHighloadBlock();
        if ($hlBlock) {
            HighloadBlockTable::delete((int)$hlBlock['ID']);
        }

        ModuleConfig::setPropertyDescriptionsHlBlockId(0);
    }

    /** @return array<string, mixed>|null */
    public function getHighloadBlock(): ?array
    {
        if (!Loader::includeModule('highloadblock')) {
            return null;
        }

        $id = ModuleConfig::getPropertyDescriptionsHlBlockId();
        if ($id > 0) {
            $row = HighloadBlockTable::getById($id)->fetch();
            if ($row) {
                return $row;
            }
        }

        $row = HighloadBlockTable::getList([
            'filter' => ['=NAME' => self::HL_BLOCK_NAME],
            'limit' => 1,
        ])->fetch();

        if ($row) {
            ModuleConfig::setPropertyDescriptionsHlBlockId((int)$row['ID']);
            return $row;
        }

        return null;
    }

    /** @return array<string, array<string, mixed>> */
    private function getUserFields(): array
    {
        return [
            'UF_ACTIVE' => $this->booleanField('Активность', 10, 1),
            'UF_IBLOCK_ID' => $this->integerField('ID инфоблока', 20),
            'UF_PROPERTY_ID' => $this->integerField('ID свойства', 30),
            'UF_PROPERTY_CODE' => $this->stringField('Код свойства', 40),
            'UF_VALUE_ID' => $this->integerField('ID значения списка', 50),
            'UF_VALUE_XML_ID' => $this->stringField('XML_ID значения списка', 60),
            'UF_VALUE_NAME' => $this->stringField('Название значения списка', 70),
            'UF_TITLE' => $this->stringField('Заголовок описания', 100),
            'UF_SHORT_TEXT' => $this->textareaField('Краткое описание', 110),
            'UF_DESCRIPTION' => $this->textareaField('Подробное описание', 120),
            'UF_HINT' => $this->textareaField('Подсказка', 130),
            'UF_LINK' => $this->stringField('Ссылка', 140),
            'UF_LINK_TEXT' => $this->stringField('Текст ссылки', 150),
            'UF_COLOR' => $this->stringField('Цвет', 160),
            'UF_TEXT_COLOR' => $this->stringField('Цвет текста', 170),
            'UF_ICON' => $this->fileField('Иконка', 180),
            'UF_IMAGE' => $this->fileField('Изображение', 185),
            'UF_DOCUMENT' => $this->fileField('Документ', 186),
            'UF_SORT' => $this->integerField('Сортировка', 190),
            'UF_EXTRA_JSON' => $this->textareaField('Дополнительные параметры JSON', 200),
        ];
    }

    /** @param array<string, mixed> $field */
    private function ensureUserField(int $hlBlockId, string $fieldName, array $field): string
    {
        $entityId = 'HLBLOCK_' . $hlBlockId;
        $existing = UserFieldTable::getList([
            'filter' => ['=ENTITY_ID' => $entityId, '=FIELD_NAME' => $fieldName],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        if ($existing) {
            return 'exists';
        }

        $entity = new CUserTypeEntity();
        $id = $entity->Add(array_merge($field, [
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $fieldName,
        ]));

        if (!$id) {
            throw new Exception('Не удалось создать поле ' . $fieldName . ' HL-блока описаний.');
        }

        return 'created';
    }

    /** @return array<string, mixed> */
    private function stringField(string $label, int $sort, bool $mandatory = false): array
    {
        return $this->baseField('string', $label, $sort, $mandatory);
    }

    /** @return array<string, mixed> */
    private function textareaField(string $label, int $sort): array
    {
        return array_merge($this->baseField('string', $label, $sort), [
            'SETTINGS' => ['ROWS' => 6],
        ]);
    }

    /** @return array<string, mixed> */
    private function integerField(string $label, int $sort, bool $mandatory = false): array
    {
        return $this->baseField('integer', $label, $sort, $mandatory);
    }

    /** @return array<string, mixed> */
    private function booleanField(string $label, int $sort, int $default = 0): array
    {
        return array_merge($this->baseField('boolean', $label, $sort), [
            'SETTINGS' => ['DEFAULT_VALUE' => $default, 'DISPLAY' => 'CHECKBOX'],
        ]);
    }

    /** @return array<string, mixed> */
    private function fileField(string $label, int $sort): array
    {
        return $this->baseField('file', $label, $sort);
    }

    /** @return array<string, mixed> */
    private function baseField(string $type, string $label, int $sort, bool $mandatory = false): array
    {
        return [
            'USER_TYPE_ID' => $type,
            'XML_ID' => '',
            'SORT' => $sort,
            'MULTIPLE' => 'N',
            'MANDATORY' => $mandatory ? 'Y' : 'N',
            'SHOW_FILTER' => 'I',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'EDIT_FORM_LABEL' => ['ru' => $label, 'en' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label, 'en' => $label],
            'LIST_FILTER_LABEL' => ['ru' => $label, 'en' => $label],
        ];
    }
}
