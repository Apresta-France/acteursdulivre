<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Env;
use Adl\Core\HourlyCron;
use Adl\Core\Request;

final class CronController
{
    /** @var array<string, array{label: string, runner: callable(bool): array<string, mixed>}> */
    private const JOBS = [
        'relances' => [
            'label' => 'Relances profil, mission, demandes et projets',
            'runner' => [HourlyCron::class, 'run'],
        ],
    ];

    public function index(Request $request): void
    {
        if (!$this->authorized($request)) {
            $this->json(403, ['ok' => false, 'error' => 'Jeton cron manquant ou invalide.']);
            return;
        }
        $this->json(200, [
            'ok' => true,
            'hint' => 'Appelez une tâche précise : /cron/{tache}?token=…',
            'jobs' => self::catalog(),
        ]);
    }

    public function run(Request $request, string $task): void
    {
        if (!$this->authorized($request)) {
            $this->json(403, ['ok' => false, 'error' => 'Jeton cron manquant ou invalide.']);
            return;
        }
        if (!isset(self::JOBS[$task])) {
            $this->json(404, [
                'ok' => false,
                'error' => 'Tâche cron inconnue : ' . $task,
                'jobs' => self::catalog(),
            ]);
            return;
        }

        $force = Env::bool('APP_DEBUG', false) && $request->string('force') === '1';
        $result = (self::JOBS[$task]['runner'])($force);
        $result['job'] = $task;

        $this->json(!empty($result['ok']) ? 200 : 500, $result);
    }

    private function authorized(Request $request): bool
    {
        $expected = trim((string) (Env::get('CRON_TOKEN', '') ?: Env::get('APP_KEY', '')));
        $given = $request->string('token');
        return $expected !== '' && $given !== '' && hash_equals($expected, $given);
    }

    /** @return list<array{id: string, label: string, url: string}> */
    private static function catalog(): array
    {
        $out = [];
        foreach (self::JOBS as $id => $job) {
            $out[] = [
                'id' => $id,
                'label' => $job['label'],
                'url' => url('/cron/' . $id),
            ];
        }
        return $out;
    }

    /** @param array<string, mixed> $payload */
    private function json(int $status, array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
