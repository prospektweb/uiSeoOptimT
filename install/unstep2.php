<?php
if (!check_bitrix_sessid()) {
    return;
}

echo CAdminMessage::ShowNote('Модуль prospektweb.propvalmanager успешно удалён.');
?>
<p><a href="/bitrix/admin/settings.php?lang=<?php echo LANGUAGE_ID; ?>&mid=prospektweb.propvalmanager">Перейти в настройки</a></p>
<p><a href="/bitrix/admin/partner_modules.php?lang=<?php echo LANGUAGE_ID; ?>">К списку модулей</a></p>
