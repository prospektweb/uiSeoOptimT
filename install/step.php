<?php
if (!check_bitrix_sessid()) {
    return;
}

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
if ($request->getPost('install_step') === '2') {
    echo CAdminMessage::ShowNote('Модуль prospektweb.uiseooptimt успешно установлен.');
    ?>
    <p><a href="/bitrix/admin/settings.php?lang=<?php echo LANGUAGE_ID; ?>&mid=prospektweb.uiseooptimt">Перейти в настройки</a></p>
    <p><a href="/bitrix/admin/partner_modules.php?lang=<?php echo LANGUAGE_ID; ?>">К списку модулей</a></p>
    <?php
    return;
}

$productsIblocks = method_exists($this, 'getProductsIblockList') ? $this->getProductsIblockList() : [];
$offersIblocks = method_exists($this, 'getOffersIblockList') ? $this->getOffersIblockList() : [];
?>
<form action="<?php echo $APPLICATION->GetCurPage(); ?>" method="post">
    <?php echo bitrix_sessid_post(); ?>
    <input type="hidden" name="lang" value="<?php echo htmlspecialcharsbx(LANGUAGE_ID); ?>">
    <input type="hidden" name="id" value="prospektweb.uiseooptimt">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="install_step" value="2">

    <p><strong>PRODUCTS_IBLOCK_ID (товары)</strong></p>
    <select name="PRODUCTS_IBLOCK_ID" style="min-width: 600px" required>
        <option value="">Выберите инфоблок товаров</option>
        <?php foreach ($productsIblocks as $iblock): ?>
            <option value="<?php echo $iblock['id']; ?>" <?php echo $iblock['selected'] ? 'selected' : ''; ?>>[<?php echo $iblock['id']; ?>] <?php echo htmlspecialcharsbx($iblock['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <p><strong>OFFERS_IBLOCK_ID (SKU)</strong></p>
    <select name="OFFERS_IBLOCK_ID" style="min-width: 600px">
        <option value="0">Не выбрано</option>
        <?php foreach ($offersIblocks as $iblock): ?>
            <option value="<?php echo $iblock['id']; ?>" <?php echo $iblock['selected'] ? 'selected' : ''; ?>>[<?php echo $iblock['id']; ?>] <?php echo htmlspecialcharsbx($iblock['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <p>Основные инфоблоки предзаполнены по данным каталога. При необходимости выберите вручную. Свойство TR_CASE будет создано для товаров и разделов выбранного инфоблока товаров.</p>
    <input type="submit" value="Установить" class="adm-btn-save">
</form>
