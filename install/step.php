<?php
if (!check_bitrix_sessid()) {
    return;
}

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
if ($request->getPost('install_step') === '2') {
    echo CAdminMessage::ShowNote('Модуль prospektweb.propvalmanager успешно установлен.');
    ?>
    <p><a href="/bitrix/admin/settings.php?lang=<?php echo LANGUAGE_ID; ?>&mid=prospektweb.propvalmanager">Перейти в настройки</a></p>
    <p><a href="/bitrix/admin/partner_modules.php?lang=<?php echo LANGUAGE_ID; ?>">К списку модулей</a></p>
    <?php
    return;
}

$productsIblocks = is_array($GLOBALS['PROSPEKTWEB_PROPVALMANAGER_PRODUCTS_IBLOCKS'] ?? null)
    ? $GLOBALS['PROSPEKTWEB_PROPVALMANAGER_PRODUCTS_IBLOCKS']
    : [];
$offersIblocks = is_array($GLOBALS['PROSPEKTWEB_PROPVALMANAGER_OFFERS_IBLOCKS'] ?? null)
    ? $GLOBALS['PROSPEKTWEB_PROPVALMANAGER_OFFERS_IBLOCKS']
    : [];
$detectedProductsIblockId = (int)($GLOBALS['PROSPEKTWEB_PROPVALMANAGER_PRODUCTS_IBLOCK_ID'] ?? 0);
$detectedOffersIblockId = (int)($GLOBALS['PROSPEKTWEB_PROPVALMANAGER_OFFERS_IBLOCK_ID'] ?? 0);
?>
<form action="<?php echo $APPLICATION->GetCurPage(); ?>" method="post">
    <?php echo bitrix_sessid_post(); ?>
    <input type="hidden" name="lang" value="<?php echo htmlspecialcharsbx(LANGUAGE_ID); ?>">
    <input type="hidden" name="id" value="prospektweb.propvalmanager">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="install_step" value="2">

    <?php if (empty($productsIblocks)): ?>
        <?php echo CAdminMessage::ShowMessage('Не найдено ни одного инфоблока. Проверьте, что модуль iblock подключен и инфоблоки созданы.'); ?>
    <?php endif; ?>

    <p><strong>PRODUCTS_IBLOCK_ID (товары)</strong></p>
    <select name="PRODUCTS_IBLOCK_ID" style="min-width: 600px" required>
        <option value="">Выберите инфоблок товаров</option>
        <?php foreach ($productsIblocks as $iblock): ?>
            <option value="<?php echo (int)$iblock['id']; ?>" <?php echo !empty($iblock['selected']) ? 'selected' : ''; ?>>[<?php echo (int)$iblock['id']; ?>] <?php echo htmlspecialcharsbx((string)$iblock['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($detectedProductsIblockId > 0): ?>
        <p style="color: #666;">Автоопределён инфоблок товаров: <?php echo $detectedProductsIblockId; ?></p>
    <?php endif; ?>

    <p><strong>OFFERS_IBLOCK_ID (SKU)</strong></p>
    <select name="OFFERS_IBLOCK_ID" style="min-width: 600px">
        <option value="0">Не выбрано</option>
        <?php foreach ($offersIblocks as $iblock): ?>
            <option value="<?php echo (int)$iblock['id']; ?>" <?php echo !empty($iblock['selected']) ? 'selected' : ''; ?>>[<?php echo (int)$iblock['id']; ?>] <?php echo htmlspecialcharsbx((string)$iblock['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($detectedOffersIblockId > 0): ?>
        <p style="color: #666;">Автоопределён инфоблок SKU: <?php echo $detectedOffersIblockId; ?></p>
    <?php endif; ?>

    <p>Основные инфоблоки предзаполнены по данным каталога. При необходимости выберите вручную. Свойство TR_CASE будет создано для товаров выбранного инфоблока товаров.</p>
    <input type="submit" value="Установить" class="adm-btn-save">
</form>
