<?php

use Bitrix\Iblock\IblockTable;
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
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
    }

    public function DoInstall(): void
    {
        global $APPLICATION;
        $request = Application::getInstance()->getContext()->getRequest();

        if ($request->getPost('install_step') !== '2') {
            $APPLICATION->IncludeAdminFile('Установка модуля', __DIR__ . '/step.php');
            return;
        }

        try {
            $this->checkDependencies();
            $productsId = (int)$request->getPost('PRODUCTS_IBLOCK_ID');
            $offersId = (int)$request->getPost('OFFERS_IBLOCK_ID');
            ModuleConfig::setProductsIblockId($productsId);
            ModuleConfig::setOffersIblockId($offersId);
            ModuleConfig::setEnabled(true);

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

    public function DoUninstall(): void { /* unchanged */
        global $APPLICATION;
        $request = Application::getInstance()->getContext()->getRequest();
        if ($request->getPost('inst') === null) {
            $APPLICATION->IncludeAdminFile('Удаление модуля', __DIR__ . '/unstep1.php');
            return;
        }
        $removeData = $request->getPost('remove_data') === 'Y';
        try {
            Loader::includeModule($this->MODULE_ID);
            (new AsproTemplatePatcher())->restore();
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

    public function InstallDB(): bool { (new PropertyManager())->ensureProperties(ModuleConfig::getProductsIblockId(), ModuleConfig::getOffersIblockId()); return true; }
    public function UnInstallDB(bool $removeData = false): bool { if ($removeData) {(new PropertyManager())->removeManagedProperties(ModuleConfig::getProductsIblockId(), ModuleConfig::getOffersIblockId());} return true; }
    public function InstallFiles(): bool { return true; }
    public function UnInstallFiles(): bool { return true; }
    private function registerEvents(): void {}
    private function unRegisterEvents(): void {}

    private function checkDependencies(): void
    {
        foreach (['iblock', 'catalog', 'sale'] as $module) {
            if (!Loader::includeModule($module)) { throw new RuntimeException('Не найден обязательный модуль: ' . $module); }
        }
    }

    private function rollbackInstall(string $reason): void
    {
        try { Loader::includeModule($this->MODULE_ID); (new AsproTemplatePatcher())->restore(); } catch (\Throwable) {}
        UnRegisterModule($this->MODULE_ID);
        AddMessage2Log('Rollback установки ' . $this->MODULE_ID . ': ' . $reason);
    }

    /** @return array<int, array{id:int,name:string}> */
    public function getIblockList(): array
    {
        $items = [];
        if (!Loader::includeModule('iblock')) { return $items; }
        $rows = IblockTable::getList(['select' => ['ID', 'NAME'], 'order' => ['ID' => 'ASC']]);
        while ($row = $rows->fetch()) {
            $items[] = ['id' => (int)$row['ID'], 'name' => (string)$row['NAME']];
        }
        return $items;
    }
}
