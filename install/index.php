<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Prospektweb\UiSeoOptimT\Service\AsproTemplatePatcher;
use Prospektweb\UiSeoOptimT\Service\ModuleConfig;
use Prospektweb\UiSeoOptimT\Service\PropertyManager;

Loc::loadMessages(__FILE__);

class prospektweb_uiseooptimt extends CModule
{
    public $MODULE_ID = 'prospektweb.uiseooptimt';
    public $MODULE_NAME = 'PROSPEKT-WEB: UI/SEO Optim Transformer';
    public $MODULE_DESCRIPTION = 'UI/SEO улучшения и управление свойствами для Aspro Premier';
    public $PARTNER_NAME = 'PROSPEKT-WEB';
    public $PARTNER_URI = 'https://prospektweb.ru';

    public function __construct()
    {
        $versionInfo = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $versionInfo['VERSION'] ?? $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $versionInfo['VERSION_DATE'] ?? $arModuleVersion['VERSION_DATE'];
    }

    public function DoInstall(): void
    {
        global $APPLICATION;

        try {
            $this->checkDependencies();
            RegisterModule($this->MODULE_ID);
            Loader::includeModule($this->MODULE_ID);

            $this->InstallDB();
            $this->InstallFiles();
            $this->registerEvents();

            $APPLICATION->IncludeAdminFile('Установка модуля', __DIR__ . '/step.php');
        } catch (\Throwable $e) {
            $this->rollbackInstall($e->getMessage());
            $APPLICATION->ThrowException('Ошибка установки: ' . $e->getMessage());
            $APPLICATION->IncludeAdminFile('Ошибка установки', __DIR__ . '/unstep2.php');
        }
    }

    public function DoUninstall(): void
    {
        global $APPLICATION;
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();

        if ($request->getPost('inst') === null) {
            $APPLICATION->IncludeAdminFile('Удаление модуля', __DIR__ . '/unstep1.php');
            return;
        }

        $removeData = $request->getPost('remove_data') === 'Y';

        try {
            Loader::includeModule($this->MODULE_ID);
            $patcher = new AsproTemplatePatcher();
            $patcher->restore();

            $this->UnInstallDB($removeData);
            $this->UnInstallFiles();
            $this->unRegisterEvents();
            UnRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile('Удаление модуля', __DIR__ . '/unstep2.php');
        } catch (\Throwable $e) {
            $APPLICATION->ThrowException('Ошибка удаления: ' . $e->getMessage());
            $APPLICATION->IncludeAdminFile('Ошибка удаления', __DIR__ . '/unstep2.php');
        }
    }

    public function InstallDB(): bool
    {
        $propertyManager = new PropertyManager();
        $propertyManager->ensureProperties(ModuleConfig::getProductsIblockId(), ModuleConfig::getOffersIblockId());

        $patcher = new AsproTemplatePatcher();
        $patchTargets = $this->getAsproPatchTargets();
        if (!empty($patchTargets)) {
            $patcher->apply($patchTargets);
        }

        return true;
    }

    public function UnInstallDB(bool $removeData = false): bool
    {
        if ($removeData) {
            $propertyManager = new PropertyManager();
            $propertyManager->removeManagedProperties(ModuleConfig::getProductsIblockId(), ModuleConfig::getOffersIblockId());
        }

        return true;
    }

    public function InstallFiles(): bool
    {
        return true;
    }

    public function UnInstallFiles(): bool
    {
        return true;
    }

    private function checkDependencies(): void
    {
        foreach (['iblock', 'catalog', 'sale'] as $module) {
            if (!Loader::includeModule($module)) {
                throw new RuntimeException('Не найден обязательный модуль: ' . $module);
            }
        }
    }

    /** @return array<int, string> */
    private function getAsproPatchTargets(): array
    {
        $target = $_SERVER['DOCUMENT_ROOT'] . '/local/templates/aspro_premier/header.php';
        return is_file($target) ? [$target] : [];
    }

    private function rollbackInstall(string $reason): void
    {
        try {
            Loader::includeModule($this->MODULE_ID);
            (new AsproTemplatePatcher())->restore();
            $this->UnInstallDB(false);
            $this->UnInstallFiles();
            $this->unRegisterEvents();
        } catch (\Throwable) {
        }
        UnRegisterModule($this->MODULE_ID);
        AddMessage2Log('Rollback установки ' . $this->MODULE_ID . ': ' . $reason);
    }

    private function registerEvents(): void
    {
    }

    private function unRegisterEvents(): void
    {
    }
}
