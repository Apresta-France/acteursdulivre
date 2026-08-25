<?php

declare(strict_types=1);

namespace Adl\Core;

final class DcEngine
{
    public static function render(string $html, array $data): string
    {
        $html = self::normalize($html);
        $html = self::process($html, $data);
        return $html;
    }

    private static function normalize(string $html): string
    {
        $html = preg_replace('/\s+hint-placeholder-[a-z-]+="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace('/\s+style-hover="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace_callback(
            '/<image-slot([^>]*)><\/image-slot>/i',
            static function (array $m): string {
                $attrs = $m[1];
                $src = '';
                if (preg_match('/src="([^"]*)"/i', $attrs, $sm)) {
                    $src = $sm[1];
                }
                $alt = 'visuel';
                if (preg_match('/placeholder="([^"]*)"/i', $attrs, $am)) {
                    $alt = $am[1];
                }
                return '<img src="' . $src . '" alt="' . htmlspecialchars($alt, ENT_QUOTES) . '" style="width:100%;height:100%;object-fit:cover;display:block;">';
            },
            $html
        ) ?? $html;
        $html = str_replace(['<sc-raw-select', '</sc-raw-select>', 'sc-camel-view-box'], ['<select', '</select>', 'viewBox'], $html);

        $routes = self::routes();
        $html = preg_replace_callback(
            '/sc-camel-on-click="\{\{\s*([^}]+)\s*\}\}"/',
            static function (array $m) use ($routes): string {
                $expr = trim($m[1]);
                if (isset($routes[$expr])) {
                    return 'data-go="' . $routes[$expr] . '"';
                }
                if (preg_match('/^(\w+)\.onClick$/', $expr, $pm) || preg_match('/^(\w+)\.go$/', $expr)) {
                    return 'data-item-go';
                }
                return 'data-action="' . htmlspecialchars($expr, ENT_QUOTES) . '"';
            },
            $html
        ) ?? $html;

        return $html;
    }

    private static function process(string $html, array $data): string
    {
        $out = '';
        $offset = 0;
        $len = strlen($html);

        while ($offset < $len) {
            $ifPos = strpos($html, '<sc-if', $offset);
            $forPos = strpos($html, '<sc-for', $offset);
            $next = self::nearest($ifPos, $forPos);

            if ($next === null) {
                $out .= self::interpolate(substr($html, $offset), $data);
                break;
            }

            $out .= self::interpolate(substr($html, $offset, $next['pos'] - $offset), $data);
            $tagEnd = strpos($html, '>', $next['pos']);
            if ($tagEnd === false) {
                break;
            }
            $openTag = substr($html, $next['pos'], $tagEnd - $next['pos'] + 1);
            $innerStart = $tagEnd + 1;
            $closeTag = $next['type'] === 'if' ? '</sc-if>' : '</sc-for>';
            $innerEnd = self::findClose($html, $innerStart, $next['type'] === 'if' ? 'sc-if' : 'sc-for');
            $inner = substr($html, $innerStart, $innerEnd - $innerStart);

            if ($next['type'] === 'if') {
                $cond = self::attr($openTag, 'value') ?? '';
                $path = self::mustachePath($cond);
                if (self::truthy(self::value($data, $path))) {
                    $out .= self::process($inner, $data);
                }
            } else {
                $listPath = self::mustachePath(self::attr($openTag, 'list') ?? '');
                $as = self::attr($openTag, 'as') ?: 'item';
                $list = self::value($data, $listPath);
                if (is_array($list)) {
                    foreach ($list as $item) {
                        $ctx = $data;
                        $ctx[$as] = $item;
                        if (is_array($item) && isset($item['href'])) {
                            $innerItem = str_replace('data-item-go', 'data-go="' . htmlspecialchars((string) $item['href'], ENT_QUOTES) . '"', $inner);
                        } else {
                            $innerItem = $inner;
                        }
                        $out .= self::process($innerItem, $ctx);
                    }
                }
            }

            $offset = $innerEnd + strlen($closeTag);
        }

        return $out;
    }

    private static function nearest(int|false $ifPos, int|false $forPos): ?array
    {
        $candidates = [];
        if ($ifPos !== false) {
            $candidates[] = ['pos' => $ifPos, 'type' => 'if'];
        }
        if ($forPos !== false) {
            $candidates[] = ['pos' => $forPos, 'type' => 'for'];
        }
        if ($candidates === []) {
            return null;
        }
        usort($candidates, static fn (array $a, array $b): int => $a['pos'] <=> $b['pos']);
        return $candidates[0];
    }

    private static function findClose(string $html, int $start, string $tag): int
    {
        $depth = 1;
        $i = $start;
        $open = '<' . $tag;
        $close = '</' . $tag . '>';
        while ($i < strlen($html) && $depth > 0) {
            $nextOpen = strpos($html, $open, $i);
            $nextClose = strpos($html, $close, $i);
            if ($nextClose === false) {
                return strlen($html);
            }
            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $i = $nextOpen + strlen($open);
            } else {
                $depth--;
                if ($depth === 0) {
                    return $nextClose;
                }
                $i = $nextClose + strlen($close);
            }
        }
        return strlen($html);
    }

    private static function interpolate(string $html, array $data): string
    {
        return (string) preg_replace_callback('/\{\{\s*([^}]+?)\s*\}\}/', static function (array $m) use ($data): string {
            $path = trim($m[1]);
            $value = self::value($data, $path);
            if (is_bool($value)) {
                return $value ? '1' : '';
            }
            if (is_array($value) || is_object($value)) {
                return '';
            }
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }, $html);
    }

    private static function value(array $data, string $path): mixed
    {
        if ($path === '') {
            return null;
        }
        $parts = explode('.', $path);
        $cur = $data;
        foreach ($parts as $part) {
            if (is_array($cur) && array_key_exists($part, $cur)) {
                $cur = $cur[$part];
                continue;
            }
            if (is_object($cur) && isset($cur->{$part})) {
                $cur = $cur->{$part};
                continue;
            }
            return null;
        }
        return $cur;
    }

    private static function truthy(mixed $value): bool
    {
        return !empty($value);
    }

    private static function attr(string $tag, string $name): ?string
    {
        if (preg_match('/' . preg_quote($name, '/') . '="([^"]*)"/', $tag, $m)) {
            return $m[1];
        }
        return null;
    }

    private static function mustachePath(string $expr): string
    {
        if (preg_match('/\{\{\s*([^}]+?)\s*\}\}/', $expr, $m)) {
            return trim($m[1]);
        }
        return trim($expr);
    }

    public static function routes(): array
    {
        return [
            'goAccueil' => '/',
            'goResultats' => '/recherche',
            'goFiche' => '/prestations/correction-complete-roman',
            'goCommande' => '/espace/commande',
            'goProfil' => '/prestataires/marion-vasseur',
            'goPublier' => '/espace/publier',
            'goMessagerie' => '/espace/messages',
            'goDashboard' => '/espace',
            'goMissions' => '/missions',
            'goMission' => '/missions/correcteur-essai-historique',
            'goSuivi' => '/espace/suivi',
            'goCreer' => '/espace/prestations/creer',
            'goTarifs' => '/tarifs',
            'goAide' => '/aide',
            'goComment' => '/comment-ca-marche',
            'goConfiance' => '/confiance',
            'goInscription' => '/inscription',
            'goCommandes' => '/espace/commandes',
            'goMetier' => '/metiers/correction',
            'goApropos' => '/a-propos',
            'goJournal' => '/journal',
            'goArticle' => '/journal/cout-fabrication-roman-autoedition',
            'goContact' => '/contact',
            'goLegal' => '/mentions-legales',
            'goConnexion' => '/connexion',
            'goNotifications' => '/espace/notifications',
            'goParametres' => '/espace/parametres',
            'goFacturation' => '/espace/facturation',
            'goFavoris' => '/espace/favoris',
            'goMesPrestations' => '/espace/prestations',
            'goMesMissions' => '/espace/missions',
            'goCandidatures' => '/espace/candidatures',
            'goAvis' => '/espace/avis',
            'goVitrine' => '/espace/vitrine',
            'login' => '/espace',
            'toggleMega' => '#mega',
            'goAdmin' => '/admin',
            'goModeration' => '/admin/moderation',
            'goVerif' => '/admin/verifications',
            'goLitiges' => '/admin/litiges',
            'goAvisSignales' => '/admin/avis',
            'goUsers' => '/admin/utilisateurs',
            'goCatalogue' => '/admin/prestations',
            'goAdminMissions' => '/admin/missions',
            'goFinances' => '/admin/finances',
            'goPreouverture' => '/admin/pre-ouverture',
            'goCms' => '/admin/journal',
            'goReglages' => '/admin/reglages',
        ];
    }
}
