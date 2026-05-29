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

$iblocks = method_exists($this, 'getIblockList') ? $this->getIblockList() : [];
$defaultProducts = (int)\Prospektweb\UiSeoOptimT\Service\ModuleConfig::getProductsIblockId();
$defaultOffers = (int)\Prospektweb\UiSeoOptimT\Service\ModuleConfig::getOffersIblockId();
?>
<form action="<?php echo $APPLICATION->GetCurPage(); ?>" method="post">
    <?php echo bitrix_sessid_post(); ?>
    <input type="hidden" name="lang" value="<?php echo htmlspecialcharsbx(LANGUAGE_ID); ?>">
    <input type="hidden" name="id" value="prospektweb.uiseooptimt">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="install_step" value="2">

    <p><strong>PRODUCTS_IBLOCK_ID (товары)</strong></p>
    <select name="PRODUCTS_IBLOCK_ID" style="min-width: 600px">
        <?php foreach ($iblocks as $iblock): ?>
            <option value="<?php echo $iblock['id']; ?>" <?php echo $defaultProducts === $iblock['id'] ? 'selected' : ''; ?>>[<?php echo $iblock['id']; ?>] <?php echo htmlspecialcharsbx($iblock['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <p><strong>OFFERS_IBLOCK_ID (SKU)</strong></p>
    <select name="OFFERS_IBLOCK_ID" style="min-width: 600px">
        <option value="0">Не выбрано</option>
        <?php foreach ($iblocks as $iblock): ?>
            <option value="<?php echo $iblock['id']; ?>" <?php echo $defaultOffers === $iblock['id'] ? 'selected' : ''; ?>>[<?php echo $iblock['id']; ?>] <?php echo htmlspecialcharsbx($iblock['name']); ?></option>
        <?php endforeach; ?>
    </select>

    <p>Значения предзаполнены по автоопределению. При необходимости можно выбрать вручную.</p>
    <input type="submit" value="Установить" class="adm-btn-save">
</form>
