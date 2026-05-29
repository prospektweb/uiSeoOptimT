<?php
if (!check_bitrix_sessid()) {
    return;
}

echo CAdminMessage::ShowNote('Модуль prospektweb.uiseooptimt успешно удалён.');
?>
<p><a href="/bitrix/admin/settings.php?lang=<?php echo LANGUAGE_ID; ?>&mid=prospektweb.uiseooptimt">Перейти в настройки</a></p>
<p><a href="/bitrix/admin/partner_modules.php?lang=<?php echo LANGUAGE_ID; ?>">К списку модулей</a></p>
