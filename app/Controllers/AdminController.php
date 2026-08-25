<?php

declare(strict_types=1);

namespace Adl\Controllers;

use Adl\Core\Auth;
use Adl\Core\Mailer;
use Adl\Core\Request;
use Adl\Core\View;
use Adl\Data\AdminCatalog;
use Adl\Models\EmailTemplate;
use Adl\Models\Setting;
use Throwable;

final class AdminController
{
    public function dashboard(Request $request): void
    {
        $this->screen('dash');
    }

    public function verifications(Request $request): void
    {
        $this->screen('verif');
    }

    public function moderation(Request $request): void
    {
        $this->screen('moderation');
    }

    public function litiges(Request $request): void
    {
        $this->screen('litiges');
    }

    public function avis(Request $request): void
    {
        $this->screen('avis');
    }

    public function utilisateurs(Request $request): void
    {
        $this->screen('users', ['query' => $request->string('q', '')]);
    }

    public function prestations(Request $request): void
    {
        $this->screen('catalogue');
    }

    public function missions(Request $request): void
    {
        $this->screen('missions');
    }

    public function finances(Request $request): void
    {
        $this->screen('finances');
    }

    public function preOuverture(Request $request): void
    {
        $this->screen('preouverture');
    }

    public function journal(Request $request): void
    {
        $this->screen('cms');
    }

    public function reglages(Request $request): void
    {
        $this->screen('reglages');
    }

    public function smtp(Request $request): void
    {
        Auth::requireAdmin();
        View::render('admin/smtp', AdminCatalog::forScreen('smtp', [
            'settings' => Setting::all(),
            'saved' => flash('saved') ? true : false,
            'error' => flash('error'),
            'tested' => flash('tested'),
        ]), 'layouts/admin');
    }

    public function smtpSave(Request $request): void
    {
        Auth::requireAdmin();
        foreach (['mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name'] as $key) {
            Setting::set($key, $request->string($key, ''));
        }
        flash('saved', true);
        redirect('/admin/smtp');
    }

    public function smtpTest(Request $request): void
    {
        Auth::requireAdmin();
        $to = $request->string('test_email', Auth::user()['email'] ?? '');
        try {
            Mailer::send($to, 'Test SMTP — Acteurs du Livre', '<p>Ceci est un e-mail de test envoyé depuis l\'administration.</p>');
            flash('tested', 'E-mail de test envoyé vers ' . $to . '.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/admin/smtp');
    }

    public function emails(Request $request): void
    {
        Auth::requireAdmin();
        View::render('admin/emails', AdminCatalog::forScreen('emails', [
            'templates' => EmailTemplate::all(),
            'saved' => flash('saved') ? true : false,
        ]), 'layouts/admin');
    }

    public function emailEdit(Request $request, string $id): void
    {
        Auth::requireAdmin();
        $template = EmailTemplate::find((int) $id);
        if (!$template) {
            redirect('/admin/emails');
        }
        View::render('admin/email-edit', AdminCatalog::forScreen('emails', [
            'title' => $template['name'],
            'template' => $template,
        ]), 'layouts/admin');
    }

    public function emailSave(Request $request, string $id): void
    {
        Auth::requireAdmin();
        EmailTemplate::update((int) $id, $request->string('subject'), $request->input('body_html', ''));
        flash('saved', true);
        redirect('/admin/emails');
    }

    private function screen(string $id, array $extra = []): void
    {
        Auth::requireAdmin();
        View::admin($id, $extra);
    }
}
