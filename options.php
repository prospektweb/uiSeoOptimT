<?php

define('ADMIN_MODULE_NAME', 'prospektweb.propvalmanager');

define('PROSPEKTWEB_PROPVALMANAGER_SELF', $APPLICATION->GetCurPage() . '?mid=' . urlencode(ADMIN_MODULE_NAME) . '&lang=' . LANGUAGE_ID);

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Main\Loader;
use Prospektweb\PropValManager\Service\ModuleConfig;
use Prospektweb\PropValManager\Service\PropertyValueDescriptionInstaller;
use Prospektweb\PropValManager\Service\PropertyValueDescriptionRepository;

if (!$USER->IsAdmin()) {
    return;
}

if (!Loader::includeModule(ADMIN_MODULE_NAME)) {
    echo CAdminMessage::ShowMessage('Не удалось подключить модуль.');
    return;
}

Loader::includeModule('iblock');
Loader::includeModule('catalog');
Loader::includeModule('highloadblock');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$errors = [];
$warnings = [];
$notes = [];
$repository = new PropertyValueDescriptionRepository();
$installer = new PropertyValueDescriptionInstaller();

try {
    $ensureResult = $installer->ensure();
    if (!empty($ensureResult['created'])) {
        $notes[] = 'HL-блок описаний значений свойств создан автоматически.';
    }
} catch (\Throwable $e) {
    $errors[] = $e->getMessage();
}

if ($request->isPost() && check_bitrix_sessid()) {
    $action = (string)$request->getPost('description_action');

    try {
        if ($request->getPost('Update')) {
            $enabled = $request->getPost('ENABLED') === 'Y';
            $productsIblockId = (int)$request->getPost('PRODUCTS_IBLOCK_ID');
            $offersIblockId = (int)$request->getPost('OFFERS_IBLOCK_ID');

            if ($productsIblockId < 0 || $offersIblockId < 0) {
                $errors[] = 'ID инфоблоков не могут быть отрицательными.';
            }

            if ($productsIblockId > 0 && $offersIblockId <= 0) {
                $offersIblockId = prospektweb_propvalmanager_resolve_offers_iblock($productsIblockId);
            }

            if (empty($errors)) {
                ModuleConfig::setEnabled($enabled);
                ModuleConfig::setProductsIblockId($productsIblockId);
                ModuleConfig::setOffersIblockId($offersIblockId);
                $notes[] = 'Сохранено';
            }
        } elseif ($action === 'create') {
            $repository->create(
                prospektweb_propvalmanager_binding_from_request($request),
                prospektweb_propvalmanager_content_from_request($request)
            );
            $notes[] = 'Описание создано и привязано к значению списка.';
        } elseif ($action === 'update') {
            $repository->update((int)$request->getPost('DESCRIPTION_ID'), prospektweb_propvalmanager_content_from_request($request));
            $notes[] = 'Описание обновлено.';
        } elseif ($action === 'unlink') {
            $repository->unlink((int)$request->getPost('DESCRIPTION_ID'));
            $notes[] = 'Описание отвязано от значения списка.';
        } elseif ($action === 'link') {
            $repository->link((int)$request->getPost('EXISTING_DESCRIPTION_ID'), prospektweb_propvalmanager_binding_from_request($request));
            $notes[] = 'Существующее описание привязано к значению списка.';
        }
    } catch (\Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

foreach ($errors as $error) {
    echo CAdminMessage::ShowMessage($error);
}
foreach ($warnings as $warning) {
    echo CAdminMessage::ShowMessage(['TYPE' => 'ERROR', 'MESSAGE' => $warning]);
}
foreach ($notes as $note) {
    echo CAdminMessage::ShowNote($note);
}

$productsIblockId = ModuleConfig::getProductsIblockId();
$offersIblockId = ModuleConfig::getOffersIblockId();
if ($productsIblockId > 0 && $offersIblockId <= 0) {
    $offersIblockId = prospektweb_propvalmanager_resolve_offers_iblock($productsIblockId);
}

$catalogIblocks = prospektweb_propvalmanager_get_iblocks($productsIblockId);
$offersIblocks = prospektweb_propvalmanager_get_iblocks($offersIblockId);
$allValues = [];
$productProperties = prospektweb_propvalmanager_get_list_properties($productsIblockId, 'product', $allValues);
$offerProperties = prospektweb_propvalmanager_get_list_properties($offersIblockId, 'offer', $allValues);
$linkedMap = $repository->getLinkedMap($allValues);
$availableDescriptions = $repository->getAvailableDescriptions();
$editId = (int)$request->getQuery('edit_description_id');
$viewId = (int)$request->getQuery('view_description_id');
$createKey = (string)$request->getQuery('create_description_key');

$aTabs = [[
    'DIV' => 'edit1',
    'TAB' => 'Настройки и описания',
    'ICON' => 'main_settings',
    'TITLE' => 'Расширенные описания значений списочных свойств',
]];
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<style>
    .pwu-property-block { margin: 22px 18px 28px; }
    .pwu-property-block .adm-list-table-cell { padding: 10px 18px; vertical-align: top; }
    .pwu-description-form { margin: 14px 0; padding: 16px 20px; background: #eef5f7; border: 1px solid #c9d7dc; }
    .pwu-description-form .adm-detail-content-table { width: 100%; }
    .pwu-description-form .adm-detail-content-table td { padding: 7px 10px; vertical-align: top; }
    .pwu-description-form-actions { margin-top: 12px; padding-left: 30%; }
</style>
<form method="post" action="<?php echo PROSPEKTWEB_PROPVALMANAGER_SELF; ?>">
    <?php
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
    <tr class="heading"><td colspan="2">Основные настройки</td></tr>
    <tr>
        <td width="40%">Включено:</td>
        <td><input type="checkbox" name="ENABLED" value="Y" <?php echo ModuleConfig::isEnabled() ? 'checked' : ''; ?>></td>
    </tr>
    <tr>
        <td>Инфоблок каталога:</td>
        <td>
            <select name="PRODUCTS_IBLOCK_ID">
                <option value="0">-- выберите каталог --</option>
                <?php foreach ($catalogIblocks as $iblock): ?>
                    <option value="<?php echo (int)$iblock['id']; ?>" <?php echo $iblock['selected'] ? 'selected' : ''; ?>>
                        [<?php echo (int)$iblock['id']; ?>] <?php echo htmlspecialcharsbx($iblock['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td>Связанный инфоблок торговых предложений:</td>
        <td>
            <select name="OFFERS_IBLOCK_ID">
                <option value="0">-- определить автоматически --</option>
                <?php foreach ($offersIblocks as $iblock): ?>
                    <option value="<?php echo (int)$iblock['id']; ?>" <?php echo $iblock['selected'] ? 'selected' : ''; ?>>
                        [<?php echo (int)$iblock['id']; ?>] <?php echo htmlspecialcharsbx($iblock['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="adm-info-message-wrap"><div class="adm-info-message">При выборе каталога модуль автоматически подставляет связанный SKU-инфоблок, но его можно изменить вручную аналогично инфоблоку товаров.</div></div>
        </td>
    </tr>
    <tr>
        <td>HL-блок описаний:</td>
        <td><?php echo (int)ModuleConfig::getPropertyDescriptionsHlBlockId(); ?> / <?php echo htmlspecialcharsbx(PropertyValueDescriptionInstaller::HL_BLOCK_NAME); ?></td>
    </tr>
    <?php
    $tabControl->Buttons();
    echo bitrix_sessid_post();
    ?>
    <input type="submit" name="Update" value="Сохранить" class="adm-btn-save">
    <?php
    $tabControl->End();
    ?>
</form>

<?php if ($editId > 0 || $viewId > 0): ?>
    <?php prospektweb_propvalmanager_render_description_card($editId ?: $viewId, $repository, $viewId > 0); ?>
<?php endif; ?>

<?php prospektweb_propvalmanager_render_properties_block('Свойства товара типа «Список» с CALC_', $productProperties, $linkedMap, $availableDescriptions, $repository, $createKey); ?>
<?php prospektweb_propvalmanager_render_properties_block('Свойства торговых предложений типа «Список» с CALC_', $offerProperties, $linkedMap, $availableDescriptions, $repository, $createKey); ?>

<?php
/** @return array<int, array{id:int,name:string,selected:bool}> */
function prospektweb_propvalmanager_get_iblocks(int $selectedId): array
{
    $items = [];
    if (!Loader::includeModule('iblock') || !class_exists('CIBlock')) {
        return $items;
    }

    $rows = CIBlock::GetList(['ID' => 'ASC'], [], false);
    while ($row = $rows->Fetch()) {
        $iblockId = (int)$row['ID'];
        $items[] = [
            'id' => $iblockId,
            'name' => (string)$row['NAME'],
            'selected' => $iblockId === $selectedId,
        ];
    }

    return $items;
}

function prospektweb_propvalmanager_resolve_offers_iblock(int $productsIblockId): int
{
    if ($productsIblockId <= 0 || !Loader::includeModule('catalog')) {
        return 0;
    }

    if (class_exists('CCatalogSKU')) {
        $sku = CCatalogSKU::GetInfoByProductIBlock($productsIblockId);
        if (is_array($sku) && !empty($sku['IBLOCK_ID'])) {
            return (int)$sku['IBLOCK_ID'];
        }
    }

    $row = CatalogIblockTable::getList([
        'select' => ['IBLOCK_ID'],
        'filter' => ['=PRODUCT_IBLOCK_ID' => $productsIblockId],
        'limit' => 1,
    ])->fetch();

    return $row ? (int)$row['IBLOCK_ID'] : 0;
}

/** @param array<int, array<string, mixed>> $allValues @return array<int, array<string, mixed>> */
function prospektweb_propvalmanager_get_list_properties(int $iblockId, string $entityType, array &$allValues): array
{
    $properties = [];
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return $properties;
    }

    $propertyRows = CIBlockProperty::GetList(['SORT' => 'ASC', 'NAME' => 'ASC'], [
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'PROPERTY_TYPE' => 'L',
    ]);

    while ($property = $propertyRows->Fetch()) {
        $propertyName = (string)$property['NAME'];
        $propertyCode = (string)$property['CODE'];
        if (stripos($propertyName, 'CALC_') === false && stripos($propertyCode, 'CALC_') === false) {
            continue;
        }

        $propertyId = (int)$property['ID'];
        $values = [];
        $enumRows = CIBlockPropertyEnum::GetList(['SORT' => 'ASC', 'VALUE' => 'ASC'], ['PROPERTY_ID' => $propertyId]);
        while ($enum = $enumRows->Fetch()) {
            $value = [
                'IBLOCK_ID' => $iblockId,
                'PROPERTY_ID' => $propertyId,
                'PROPERTY_CODE' => $propertyCode,
                'ID' => (int)$enum['ID'],
                'XML_ID' => (string)$enum['XML_ID'],
                'VALUE' => (string)$enum['VALUE'],
            ];
            $values[] = $value;
            $allValues[] = $value;
        }

        $properties[] = [
            'IBLOCK_ID' => $iblockId,
            'ID' => $propertyId,
            'CODE' => $propertyCode,
            'NAME' => $propertyName,
            'ENTITY_TYPE' => $entityType,
            'VALUES' => $values,
        ];
    }

    return $properties;
}

/** @return array<string, mixed> */
function prospektweb_propvalmanager_binding_from_request($request): array
{
    return [
        'UF_IBLOCK_ID' => (int)$request->getPost('UF_IBLOCK_ID'),
        'UF_PROPERTY_ID' => (int)$request->getPost('UF_PROPERTY_ID'),
        'UF_PROPERTY_CODE' => (string)$request->getPost('UF_PROPERTY_CODE'),
        'UF_VALUE_ID' => (int)$request->getPost('UF_VALUE_ID'),
        'UF_VALUE_XML_ID' => (string)$request->getPost('UF_VALUE_XML_ID'),
        'UF_VALUE_NAME' => (string)$request->getPost('UF_VALUE_NAME'),
    ];
}

/** @return array<string, mixed> */
function prospektweb_propvalmanager_content_from_request($request): array
{
    $content = [
        'UF_ACTIVE' => $request->getPost('UF_ACTIVE') === 'Y' ? 1 : 0,
        'UF_TITLE' => (string)$request->getPost('UF_TITLE'),
        'UF_SHORT_TEXT' => (string)$request->getPost('UF_SHORT_TEXT'),
        'UF_DESCRIPTION' => (string)$request->getPost('UF_DESCRIPTION'),
        'UF_HINT' => (string)$request->getPost('UF_HINT'),
        'UF_LINK' => (string)$request->getPost('UF_LINK'),
        'UF_LINK_TEXT' => (string)$request->getPost('UF_LINK_TEXT'),
        'UF_COLOR' => (string)$request->getPost('UF_COLOR'),
        'UF_TEXT_COLOR' => (string)$request->getPost('UF_TEXT_COLOR'),
        'UF_SORT' => (int)$request->getPost('UF_SORT'),
        'UF_EXTRA_JSON' => (string)$request->getPost('UF_EXTRA_JSON'),
    ];

    foreach (['UF_ICON', 'UF_IMAGE', 'UF_DOCUMENT'] as $fieldName) {
        $file = prospektweb_propvalmanager_uploaded_file($fieldName . '_FILE');
        if ($file !== null) {
            $content[$fieldName] = $file;
        }
    }

    return $content;
}

/** @return array<string, mixed>|null */
function prospektweb_propvalmanager_uploaded_file(string $inputName): ?array
{
    if (empty($_FILES[$inputName]) || !is_array($_FILES[$inputName])) {
        return null;
    }

    $file = $_FILES[$inputName];
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (string)($file['tmp_name'] ?? '') === '') {
        return null;
    }

    return $file;
}

/** @param array<int, array<string, mixed>> $properties @param array<string, array<string, mixed>> $linkedMap @param array<int, array<string, mixed>> $availableDescriptions */
function prospektweb_propvalmanager_render_properties_block(string $title, array $properties, array $linkedMap, array $availableDescriptions, PropertyValueDescriptionRepository $repository, string $createKey = ''): void
{
    echo '<div class="pwu-property-block">';
    echo '<h2>' . htmlspecialcharsbx($title) . '</h2>';
    if (empty($properties)) {
        echo '<p>Списочные свойства с CALC_ в названии или коде не найдены.</p>';
        echo '</div>';
        return;
    }

    foreach ($properties as $property) {
        echo '<h3>' . htmlspecialcharsbx($property['NAME']) . ' [' . htmlspecialcharsbx($property['CODE']) . '] ID ' . (int)$property['ID'] . ' — ' . ($property['ENTITY_TYPE'] === 'offer' ? 'ТП' : 'товар') . '</h3>';
        echo '<table class="adm-list-table" style="width:100%">';
        echo '<tr class="adm-list-table-header"><td class="adm-list-table-cell">Значение</td><td class="adm-list-table-cell">ID</td><td class="adm-list-table-cell">XML_ID</td><td class="adm-list-table-cell">Статус</td><td class="adm-list-table-cell">Действия</td></tr>';
        foreach ($property['VALUES'] as $value) {
            $key = $repository->makeKey((int)$value['IBLOCK_ID'], (int)$value['PROPERTY_ID'], (string)$value['XML_ID']);
            $description = $linkedMap[$key] ?? null;
            echo '<tr class="adm-list-table-row">';
            echo '<td class="adm-list-table-cell">' . htmlspecialcharsbx($value['VALUE']) . '</td>';
            echo '<td class="adm-list-table-cell">' . (int)$value['ID'] . '</td>';
            echo '<td class="adm-list-table-cell">' . htmlspecialcharsbx($value['XML_ID']) . '</td>';
            echo '<td class="adm-list-table-cell">';
            if ($description) {
                echo '<b style="color:green">Есть описание</b><br>' . htmlspecialcharsbx((string)($description['UF_TITLE'] ?: $description['UF_VALUE_NAME']));
                if (!empty($description['UF_MODIFIED'])) {
                    echo '<br>Изменено: ' . htmlspecialcharsbx((string)$description['UF_MODIFIED']);
                }
            } else {
                echo '<span style="color:#999">Нет описания</span>';
            }
            echo '</td><td class="adm-list-table-cell">';
            prospektweb_propvalmanager_render_actions($value, $description, $availableDescriptions, $repository);
            echo '</td></tr>';
            if (!$description && $createKey === $key) {
                echo '<tr class="adm-list-table-row"><td class="adm-list-table-cell" colspan="5">';
                prospektweb_propvalmanager_render_description_form('create', 0, [], $value);
                echo '</td></tr>';
            }
        }
        echo '</table>';
    }
    echo '</div>';
}

/** @param array<string, mixed> $value @param array<string, mixed>|null $description @param array<int, array<string, mixed>> $availableDescriptions */
function prospektweb_propvalmanager_render_actions(array $value, ?array $description, array $availableDescriptions, PropertyValueDescriptionRepository $repository): void
{
    $hidden = prospektweb_propvalmanager_hidden_binding($value);
    if ($description) {
        $id = (int)$description['ID'];
        echo '<a class="adm-btn" href="' . PROSPEKTWEB_PROPVALMANAGER_SELF . '&view_description_id=' . $id . '">Просмотреть описание</a> ';
        echo '<a class="adm-btn adm-btn-save" href="' . PROSPEKTWEB_PROPVALMANAGER_SELF . '&edit_description_id=' . $id . '">Редактировать описание</a> ';
        echo '<form method="post" action="' . PROSPEKTWEB_PROPVALMANAGER_SELF . '" style="display:inline">' . bitrix_sessid_post() . '<input type="hidden" name="description_action" value="unlink"><input type="hidden" name="DESCRIPTION_ID" value="' . $id . '"><input type="submit" class="adm-btn" value="Отвязать описание"></form>';
        return;
    }

    $createUrl = prospektweb_propvalmanager_admin_url([
        'create_description_key' => $repository->makeKey((int)$value['IBLOCK_ID'], (int)$value['PROPERTY_ID'], (string)$value['XML_ID']),
    ]);
    echo '<a class="adm-btn adm-btn-save" href="' . htmlspecialcharsbx($createUrl) . '">Создать описание</a>';

    if (!empty($availableDescriptions)) {
        echo '<form method="post" action="' . PROSPEKTWEB_PROPVALMANAGER_SELF . '" style="margin-top:6px">' . bitrix_sessid_post() . $hidden . '<input type="hidden" name="description_action" value="link"><select name="EXISTING_DESCRIPTION_ID">';
        foreach ($availableDescriptions as $item) {
            echo '<option value="' . (int)$item['ID'] . '">#' . (int)$item['ID'] . ' ' . htmlspecialcharsbx((string)($item['UF_TITLE'] ?: $item['UF_VALUE_NAME'] ?: $item['UF_PROPERTY_CODE'])) . '</option>';
        }
        echo '</select> <input type="submit" class="adm-btn" value="Выбрать существующее описание"></form>';
    }
}


/** @param array<string, mixed> $params */
function prospektweb_propvalmanager_admin_url(array $params = []): string
{
    $baseParams = [
        'mid' => ADMIN_MODULE_NAME,
        'lang' => LANGUAGE_ID,
    ];

    return $GLOBALS['APPLICATION']->GetCurPage() . '?' . http_build_query(array_merge($baseParams, $params));
}

/** @param array<string, mixed> $row @param array<string, mixed> $binding */
function prospektweb_propvalmanager_render_description_form(string $action, int $descriptionId, array $row, array $binding = [], bool $readonly = false): void
{
    $isCreate = $action === 'create';
    $submitLabel = $isCreate ? 'Сохранить описание' : 'Сохранить изменения';

    echo '<div class="pwu-description-form">';
    if ($isCreate && !empty($binding)) {
        echo '<div class="adm-info-message">Создание описания для значения ' . htmlspecialcharsbx((string)$binding['VALUE']) . ' (#' . (int)$binding['ID'] . ', XML_ID: ' . htmlspecialcharsbx((string)$binding['XML_ID']) . ').</div>';
    }

    echo '<form method="post" enctype="multipart/form-data" action="' . PROSPEKTWEB_PROPVALMANAGER_SELF . '">';
    echo bitrix_sessid_post();
    echo '<input type="hidden" name="description_action" value="' . htmlspecialcharsbx($action) . '">';
    if ($descriptionId > 0) {
        echo '<input type="hidden" name="DESCRIPTION_ID" value="' . $descriptionId . '">';
    }
    if (!empty($binding)) {
        echo prospektweb_propvalmanager_hidden_binding($binding);
    }

    prospektweb_propvalmanager_render_content_fields($row, $readonly);
    if (!$readonly) {
        echo '<div class="pwu-description-form-actions"><input type="submit" class="adm-btn-save" value="' . htmlspecialcharsbx($submitLabel) . '"></div>';
    }
    echo '</form>';
    echo '</div>';
}

/** @param array<string, mixed> $value */
function prospektweb_propvalmanager_hidden_binding(array $value): string
{
    return '<input type="hidden" name="UF_IBLOCK_ID" value="' . (int)$value['IBLOCK_ID'] . '">' .
        '<input type="hidden" name="UF_PROPERTY_ID" value="' . (int)$value['PROPERTY_ID'] . '">' .
        '<input type="hidden" name="UF_PROPERTY_CODE" value="' . htmlspecialcharsbx((string)$value['PROPERTY_CODE']) . '">' .
        '<input type="hidden" name="UF_VALUE_ID" value="' . (int)$value['ID'] . '">' .
        '<input type="hidden" name="UF_VALUE_XML_ID" value="' . htmlspecialcharsbx((string)$value['XML_ID']) . '">' .
        '<input type="hidden" name="UF_VALUE_NAME" value="' . htmlspecialcharsbx((string)$value['VALUE']) . '">';
}

/** @param array<string, mixed> $row */
function prospektweb_propvalmanager_render_content_fields(array $row, bool $readonly = false): void
{
    $disabled = $readonly ? ' disabled' : '';
    $fields = [
        'UF_TITLE' => 'Заголовок',
        'UF_SHORT_TEXT' => 'Краткое описание',
        'UF_DESCRIPTION' => 'Подробное описание',
        'UF_HINT' => 'Подсказка',
        'UF_LINK' => 'Ссылка',
        'UF_LINK_TEXT' => 'Текст ссылки',
        'UF_COLOR' => 'Цвет',
        'UF_TEXT_COLOR' => 'Цвет текста',
        'UF_ICON' => 'Файл иконки',
        'UF_IMAGE' => 'Файл изображения',
        'UF_DOCUMENT' => 'Файл документа',
        'UF_SORT' => 'Сортировка',
        'UF_EXTRA_JSON' => 'Дополнительные параметры JSON',
    ];
    echo '<table class="adm-detail-content-table edit-table">';
    echo '<tr><td>Активность</td><td><input type="checkbox" name="UF_ACTIVE" value="Y" ' . (((int)($row['UF_ACTIVE'] ?? 1) === 1) ? 'checked' : '') . $disabled . '></td></tr>';
    foreach ($fields as $name => $label) {
        $value = htmlspecialcharsbx((string)($row[$name] ?? ''));
        echo '<tr><td width="30%">' . htmlspecialcharsbx($label) . '</td><td>';
        if (in_array($name, ['UF_SHORT_TEXT', 'UF_DESCRIPTION', 'UF_HINT', 'UF_EXTRA_JSON'], true)) {
            echo '<textarea name="' . $name . '" rows="4" cols="80"' . $disabled . '>' . $value . '</textarea>';
        } elseif (in_array($name, ['UF_ICON', 'UF_IMAGE', 'UF_DOCUMENT'], true)) {
            if ((int)($row[$name] ?? 0) > 0) {
                echo '<div>Текущий файл: #' . (int)$row[$name] . '</div>';
            }
            $accept = in_array($name, ['UF_ICON', 'UF_IMAGE'], true) ? ' accept="image/*"' : '';
            echo '<input type="file" name="' . $name . '_FILE"' . $accept . $disabled . '>';
        } else {
            echo '<input type="text" name="' . $name . '" value="' . $value . '" size="80"' . $disabled . '>';
        }
        echo '</td></tr>';
    }
    echo '</table>';
}

function prospektweb_propvalmanager_render_description_card(int $id, PropertyValueDescriptionRepository $repository, bool $readonly): void
{
    $row = $repository->getById($id);

    if (!$row) {
        echo CAdminMessage::ShowMessage('Описание #' . $id . ' не найдено.');
        return;
    }

    echo '<div class="pwu-property-block">';
    echo '<h2>' . ($readonly ? 'Просмотр' : 'Редактирование') . ' описания #' . $id . '</h2>';
    echo '<div class="adm-info-message">Связано: инфоблок #' . (int)$row['UF_IBLOCK_ID'] . ', свойство ' . htmlspecialcharsbx((string)$row['UF_PROPERTY_CODE']) . ' (#' . (int)$row['UF_PROPERTY_ID'] . '), значение ' . htmlspecialcharsbx((string)$row['UF_VALUE_NAME']) . ' (#' . (int)$row['UF_VALUE_ID'] . ', XML_ID: ' . htmlspecialcharsbx((string)$row['UF_VALUE_XML_ID']) . '). Технические идентификаторы доступны только для просмотра; изменяются только контентные данные.</div>';
    prospektweb_propvalmanager_render_description_form('update', $id, $row, [], $readonly);
    echo '</div>';
}
