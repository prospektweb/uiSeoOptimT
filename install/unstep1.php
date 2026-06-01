<?php
if (!check_bitrix_sessid()) {
    return;
}
?>
<form action="<?php echo $APPLICATION->GetCurPage(); ?>" method="post">
    <?php echo bitrix_sessid_post(); ?>
    <input type="hidden" name="lang" value="<?php echo htmlspecialcharsbx(LANGUAGE_ID); ?>">
    <input type="hidden" name="id" value="prospektweb.propvalmanager">
    <input type="hidden" name="uninstall" value="Y">

    <p>Удалить данные модуля (включая свойства, созданные модулем, например TR_CASE)?</p>
    <label><input type="radio" name="remove_data" value="N" checked> Нет, оставить</label><br>
    <label><input type="radio" name="remove_data" value="Y"> Да, удалить</label><br><br>

    <input type="submit" name="inst" value="Удалить модуль" class="adm-btn-save">
</form>
