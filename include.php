<?php

defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'prospektweb.aspremieruiseo',
    [
        'Prospektweb\\AsproPremierUiSeo\\Service\\ModuleConfig' => 'lib/Service/ModuleConfig.php',
        'Prospektweb\\AsproPremierUiSeo\\Service\\PropertyManager' => 'lib/Service/PropertyManager.php',
        'Prospektweb\\AsproPremierUiSeo\\Service\\AsproTemplatePatcher' => 'lib/Service/AsproTemplatePatcher.php',
    ]
);
