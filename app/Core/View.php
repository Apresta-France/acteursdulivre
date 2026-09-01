<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Data\AdminCatalog;
use Adl\Data\Prototype;

final class View
{
    public static function render(string $name, array $data = [], ?string $layout = 'layouts/site'): void
    {
        if ($layout === 'layouts/site') {
            try {
                \Adl\Models\Analytics::hit();
            } catch (\Throwable) {
            }
        }
        $content = self::fetch($name, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::fetch($layout, array_merge($data, ['content' => $content]));
    }

    public static function fetch(string $name, array $data = []): string
    {
        $php = ADL_ROOT . '/app/Views/' . $name . '.php';
        if (is_file($php)) {
            extract($data, EXTR_SKIP);
            ob_start();
            require $php;
            return (string) ob_get_clean();
        }

        $html = ADL_ROOT . '/app/Views/' . $name . '.html';
        if (is_file($html)) {
            return DcEngine::render((string) file_get_contents($html), $data);
        }

        throw new \RuntimeException('Vue introuvable : ' . $name);
    }

    public static function page(string $screen, array $extra = []): void
    {
        $data = Prototype::forScreen($screen, $extra);
        self::render('pages/' . $screen, $data);
    }

    public static function admin(string $screen, array $extra = []): void
    {
        $data = AdminCatalog::forScreen($screen, $extra);
        self::render('admin/screens/' . $screen, $data, 'layouts/admin');
    }
}
