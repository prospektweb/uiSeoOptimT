<?php

define('ADMIN_MODULE_NAME', 'prospektweb.uiseooptimt');

use Bitrix\Main\Loader;
use Prospektweb\UiSeoOptimT\Service\ModuleConfig;

if (!$USER->IsAdmin()) {
    return;
}

if (!Loader::includeModule(ADMIN_MODULE_NAME)) {
    echo CAdminMessage::ShowMessage('Не удалось подключить модуль.');
    return;
}

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$errors = [];
$warnings = [];

if ($request->isPost() && $request->getPost('Update') && check_bitrix_sessid()) {
    $enabled = $request->getPost('ENABLED') === 'Y';
    $productsIblockId = (int)$request->getPost('PRODUCTS_IBLOCK_ID');
    $offersIblockId = (int)$request->getPost('OFFERS_IBLOCK_ID');

    if ($productsIblockId < 0 || $offersIblockId < 0) {
        $errors[] = 'ID инфоблоков не могут быть отрицательными.';
    }

    $asproFile = $_SERVER['DOCUMENT_ROOT'] . '/local/templates/aspro_premier/header.php';
    if (is_file($asproFile) && !is_readable($asproFile)) {
        $warnings[] = 'Файл Aspro найден, но не читается: ' . $asproFile;
    }
    if (!is_file($asproFile)) {
        $warnings[] = 'Файл Aspro не найден: ' . $asproFile;
    }

    if (empty($errors)) {
        ModuleConfig::setEnabled($enabled);
        ModuleConfig::setProductsIblockId($productsIblockId);
        ModuleConfig::setOffersIblockId($offersIblockId);
        echo CAdminMessage::ShowNote('Сохранено');
    }
}

foreach ($errors as $error) {
    echo CAdminMessage::ShowMessage($error);
}
foreach ($warnings as $warning) {
    echo CAdminMessage::ShowMessage(['TYPE' => 'ERROR', 'MESSAGE' => $warning]);
}

$aTabs = [[
    'DIV' => 'edit1',
    'TAB' => 'Настройки',
    'ICON' => 'main_settings',
    'TITLE' => 'Настройки модуля',
]];
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<form method="post" action="<?php echo $APPLICATION->GetCurPage(); ?>?mid=<?php echo urlencode(ADMIN_MODULE_NAME); ?>&lang=<?php echo LANGUAGE_ID; ?>">
    <?php
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
    <tr>
        <td width="40%">Включено:</td>
        <td><input type="checkbox" name="ENABLED" value="Y" <?php echo ModuleConfig::isEnabled() ? 'checked' : ''; ?>></td>
    </tr>
    <tr>
        <td>ID инфоблока товаров:</td>
        <td><input type="number" min="0" name="PRODUCTS_IBLOCK_ID" value="<?php echo ModuleConfig::getProductsIblockId(); ?>"></td>
    </tr>
    <tr>
        <td>ID инфоблока ТП:</td>
        <td><input type="number" min="0" name="OFFERS_IBLOCK_ID" value="<?php echo ModuleConfig::getOffersIblockId(); ?>"></td>
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
