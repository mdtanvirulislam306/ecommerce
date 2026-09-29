<?php

namespace Modules\Platform\Services;

use App\Core\Support\Service;
use App\Models\PlatformSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Billing\Models\Plan;
use Modules\Billing\Services\PlanService;
use Throwable;

class PlatformSettingService extends Service
{
    public const CACHE_KEY = 'platform_settings.all';

    public const MAILER_SERVER = 'server';

    public const MAILER_SMTP = 'smtp';

    /** @var list<string> */
    public const SECRET_KEYS = ['mail_password', 'sslcommerz_store_password'];

    public function __construct(private readonly PlanService $plans) {}

    /**
     * @return array<string, string>
     */
    public function defaults(): array
    {
        return [
            'platform_name' => (string) config('app.name'),
            'support_email' => '',
            'mail_mailer' => self::MAILER_SERVER,
            'mail_host' => '',
            'mail_port' => '587',
            'mail_encryption' => 'tls',
            'mail_username' => '',
            'mail_password' => '',
            'mail_from_address' => '',
            'mail_from_name' => '',
            'sslcommerz_store_id' => '',
            'sslcommerz_store_password' => '',
            'sslcommerz_sandbox' => '1',
            'maintenance_enabled' => '0',
            'maintenance_message' => '',
        ];
    }

    /**
     * Stored values over defaults. Secrets stay encrypted here.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => PlatformSetting::query()->pluck('value', 'key')->all());

        return array_merge($this->defaults(), array_map(fn ($value) => (string) $value, $stored));
    }

    public function get(string $key): string
    {
        return $this->all()[$key] ?? '';
    }

    /**
     * @param  array<string, string>  $values
     */
    public function putMany(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                PlatformSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Form values. Saved secrets are never sent back to the browser.
     *
     * @return array<string, mixed>
     */
    public function forAdmin(): array
    {
        $settings = $this->all();

        return [
            'platform_name' => $settings['platform_name'],
            'support_email' => $settings['support_email'],
            'mail_mailer' => $settings['mail_mailer'],
            'mail_host' => $settings['mail_host'],
            'mail_port' => $settings['mail_port'],
            'mail_encryption' => $settings['mail_encryption'],
            'mail_username' => $settings['mail_username'],
            'has_mail_password' => filled($this->decrypt($settings['mail_password'])),
            'mail_from_address' => $settings['mail_from_address'],
            'mail_from_name' => $settings['mail_from_name'],
            'sslcommerz_store_id' => $settings['sslcommerz_store_id'],
            'has_sslcommerz_store_password' => filled($this->decrypt($settings['sslcommerz_store_password'])),
            'sslcommerz_sandbox' => $settings['sslcommerz_sandbox'] === '1',
            'maintenance_enabled' => $settings['maintenance_enabled'] === '1',
            'maintenance_message' => $settings['maintenance_message'],
            'default_plan_id' => $this->plans->defaultPlan()?->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $values = [
            'platform_name' => trim((string) $data['platform_name']),
            'support_email' => trim((string) ($data['support_email'] ?? '')),
            'mail_mailer' => (string) $data['mail_mailer'],
            'mail_host' => trim((string) ($data['mail_host'] ?? '')),
            'mail_port' => (string) ($data['mail_port'] ?? ''),
            'mail_encryption' => (string) ($data['mail_encryption'] ?? 'tls'),
            'mail_username' => trim((string) ($data['mail_username'] ?? '')),
            'mail_from_address' => trim((string) ($data['mail_from_address'] ?? '')),
            'mail_from_name' => trim((string) ($data['mail_from_name'] ?? '')),
            'sslcommerz_store_id' => trim((string) ($data['sslcommerz_store_id'] ?? '')),
            'sslcommerz_sandbox' => ! empty($data['sslcommerz_sandbox']) ? '1' : '0',
            'maintenance_enabled' => ! empty($data['maintenance_enabled']) ? '1' : '0',
            'maintenance_message' => trim((string) ($data['maintenance_message'] ?? '')),
        ];

        foreach (self::SECRET_KEYS as $key) {
            if (filled($data[$key] ?? null)) {
                $values[$key] = Crypt::encryptString((string) $data[$key]);
            }
        }

        DB::transaction(function () use ($values, $data) {
            $this->putMany($values);

            if (! empty($data['default_plan_id'])) {
                $plan = Plan::query()->findOrFail($data['default_plan_id']);

                if (! $plan->is_default) {
                    $this->plans->makeDefault($plan);
                }
            }
        });

        $this->applyMailConfig();
    }

    /**
     * Points Laravel's mailer at the platform's SMTP account when one is configured; otherwise the
     * server's own mail settings stay in charge.
     */
    public function applyMailConfig(): void
    {
        try {
            $settings = $this->all();
        } catch (Throwable) {
            return;
        }

        if (filled($settings['mail_from_address'])) {
            config(['mail.from.address' => $settings['mail_from_address']]);
        }

        if (filled($settings['mail_from_name'])) {
            config(['mail.from.name' => $settings['mail_from_name']]);
        }

        if ($settings['mail_mailer'] !== self::MAILER_SMTP || blank($settings['mail_host'])) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.scheme' => $settings['mail_encryption'] === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => $settings['mail_host'],
            'mail.mailers.smtp.port' => (int) $settings['mail_port'],
            'mail.mailers.smtp.username' => $settings['mail_username'] ?: null,
            'mail.mailers.smtp.password' => $this->decrypt($settings['mail_password']),
        ]);

        if (app()->resolved('mail.manager')) {
            Mail::purge('smtp');
        }
    }

    /**
     * @return array{enabled: bool, message: string}
     */
    public function maintenance(): array
    {
        try {
            $settings = $this->all();
        } catch (Throwable) {
            return ['enabled' => false, 'message' => ''];
        }

        return [
            'enabled' => $settings['maintenance_enabled'] === '1',
            'message' => $settings['maintenance_message'],
        ];
    }

    private function decrypt(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }
}
