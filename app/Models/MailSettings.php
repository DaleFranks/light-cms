<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * How the site sends email, edited on the Email screen. By default it uses the
 * server's own mail program, which needs no setting up; an SMTP server can be
 * used instead when email from the server isn't getting through.
 */
#[Fillable([
    'from_address',
    'from_name',
    'use_smtp',
    'smtp_host',
    'smtp_port',
    'smtp_username',
    'smtp_password',
    'smtp_encryption',
])]
class MailSettings extends Model
{
    /**
     * @var list<string>
     */
    protected $hidden = [
        'smtp_password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'use_smtp' => 'boolean',
            'smtp_port' => 'integer',
            'smtp_password' => 'encrypted',
        ];
    }

    /**
     * The site's email settings, created empty the first time they're needed.
     */
    public static function current(): self
    {
        return self::query()->oldest('id')->firstOr(fn (): self => self::query()->create());
    }

    /**
     * The address email is sent from: the one set here, or "noreply@" the site's own domain.
     */
    public function fromAddress(): string
    {
        return $this->from_address ?: 'noreply@'.preg_replace('/^www\./', '', request()->getHost() ?: 'localhost');
    }

    /**
     * The name email is sent from: the one set here, or the site name.
     */
    public function fromName(): string
    {
        return $this->from_name ?: Theme::current()->site_name;
    }

    /**
     * Use these settings for the email about to be sent.
     */
    public function apply(): void
    {
        Config::set('mail.from', ['address' => $this->fromAddress(), 'name' => $this->fromName()]);

        if ($this->usesSmtp()) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp', [
                ...config('mail.mailers.smtp'),
                'url' => null,
                'scheme' => $this->smtp_encryption === 'ssl' ? 'smtps' : 'smtp',
                'host' => $this->smtp_host,
                'port' => $this->smtp_port ?: ($this->smtp_encryption === 'ssl' ? 465 : 587),
                'username' => $this->smtp_username,
                'password' => $this->smtp_password,
                'timeout' => 15,
            ]);
        }

        // Mailers already made this request still have the old sender and server.
        app('mail.manager')->purge(config('mail.default'));
    }

    public function usesSmtp(): bool
    {
        return $this->use_smtp && filled($this->smtp_host);
    }
}
