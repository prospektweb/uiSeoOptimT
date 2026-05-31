<?php

defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'prospektweb.uiseooptimt',
    [
        'Prospektweb\\UiSeoOptimT\\Service\\ModuleConfig' => 'lib/Service/ModuleConfig.php',
        'Prospektweb\\UiSeoOptimT\\Service\\PropertyManager' => 'lib/Service/PropertyManager.php',
        'Prospektweb\\UiSeoOptimT\\Service\\AsproTemplatePatcher' => 'lib/Service/AsproTemplatePatcher.php',
        'Prospektweb\\UiSeoOptimT\\Service\\PropertyValueDescriptionInstaller' => 'lib/Service/PropertyValueDescriptionInstaller.php',
        'Prospektweb\\UiSeoOptimT\\Service\\PropertyValueDescriptionRepository' => 'lib/Service/PropertyValueDescriptionRepository.php',
        'Prospektweb\\UiSeoOptimT\\Service\\PropertyDescriptionService' => 'lib/Service/PropertyDescriptionService.php',
    ]
);
