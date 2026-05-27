<?php
if (!check_bitrix_sessid()) {
    return;
}

echo CAdminMessage::ShowNote('Модуль prospektweb.aspremieruiseo успешно установлен.');
