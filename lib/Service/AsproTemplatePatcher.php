<?php

namespace Prospektweb\PropValManager\Service;

use Exception;

final class AsproTemplatePatcher
{
    private const TRACKED_FILES_OPTION = 'ASPRO_PATCHED_FILES';

    /** @param array<int, string> $targetFiles */
    public function apply(array $targetFiles): void
    {
        $tracked = [];

        foreach ($targetFiles as $filePath) {
            if (!is_file($filePath) || !is_readable($filePath) || !is_writable($filePath)) {
                throw new Exception('Файл Aspro недоступен: ' . $filePath);
            }

            $content = (string)file_get_contents($filePath);
            $hash = hash('sha256', $content);
            $backupPath = $this->getBackupPath($filePath);

            if (!is_file($backupPath)) {
                if (!is_dir(dirname($backupPath)) && !mkdir(dirname($backupPath), 0775, true) && !is_dir(dirname($backupPath))) {
                    throw new Exception('Не удалось создать директорию backup: ' . dirname($backupPath));
                }
                if (file_put_contents($backupPath, $content) === false) {
                    throw new Exception('Не удалось создать backup для: ' . $filePath);
                }
            }

            // Безопасный маркерный patch: не меняет шаблон, но подтверждает контрольный контур.
            if (mb_strpos($content, 'prospektweb.propvalmanager marker') === false) {
                $content .= PHP_EOL . '<!-- prospektweb.propvalmanager marker -->' . PHP_EOL;
                if (file_put_contents($filePath, $content) === false) {
                    throw new Exception('Не удалось записать patch в: ' . $filePath);
                }
            }

            $tracked[] = ['path' => $filePath, 'hash' => $hash, 'backup' => $backupPath];
        }

        \Bitrix\Main\Config\Option::set(ModuleConfig::MODULE_ID, self::TRACKED_FILES_OPTION, json_encode($tracked, JSON_THROW_ON_ERROR));
    }

    public function restore(): void
    {
        $raw = \Bitrix\Main\Config\Option::get(ModuleConfig::MODULE_ID, self::TRACKED_FILES_OPTION, '[]');
        $tracked = json_decode($raw, true);
        if (!is_array($tracked)) {
            $tracked = [];
        }

        foreach ($tracked as $item) {
            $path = (string)($item['path'] ?? '');
            $backup = (string)($item['backup'] ?? '');
            if ($path === '' || $backup === '' || !is_file($backup)) {
                continue;
            }

            $backupContent = (string)file_get_contents($backup);
            if (file_put_contents($path, $backupContent) === false) {
                throw new Exception('Не удалось восстановить файл Aspro: ' . $path);
            }
        }

        \Bitrix\Main\Config\Option::delete(ModuleConfig::MODULE_ID, ['name' => self::TRACKED_FILES_OPTION]);
    }

    private function getBackupPath(string $filePath): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/upload/' . ModuleConfig::MODULE_ID . '/backup/' . md5($filePath) . '.bak';
    }
}
