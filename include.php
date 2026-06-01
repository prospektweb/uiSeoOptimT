<?php

defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'prospektweb.propvalmanager',
    [
        'Prospektweb\\PropValManager\\Service\\ModuleConfig' => 'lib/Service/ModuleConfig.php',
        'Prospektweb\\PropValManager\\Service\\PropertyManager' => 'lib/Service/PropertyManager.php',
        'Prospektweb\\PropValManager\\Service\\AsproTemplatePatcher' => 'lib/Service/AsproTemplatePatcher.php',
        'Prospektweb\\PropValManager\\Service\\PropertyValueDescriptionInstaller' => 'lib/Service/PropertyValueDescriptionInstaller.php',
        'Prospektweb\\PropValManager\\Service\\PropertyValueDescriptionRepository' => 'lib/Service/PropertyValueDescriptionRepository.php',
        'Prospektweb\\PropValManager\\Service\\PropertyDescriptionService' => 'lib/Service/PropertyDescriptionService.php',
    ]
);
