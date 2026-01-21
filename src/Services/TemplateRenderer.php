<?php

namespace Asimnet\Notify\Services;

use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Models\NotificationTemplate;
use InvalidArgumentException;

/**
 * Service for rendering notification templates with variables.
 *
 * خدمة لعرض قوالب الإشعارات مع المتغيرات.
 */
class TemplateRenderer
{
    /**
     * Build variables array from a user model.
     *
     * بناء مصفوفة المتغيرات من نموذج المستخدم.
     *
     * @param  mixed  $user  User model or null
     * @param  array<string, mixed>  $customVariables  Additional variables to merge
     * @return array<string, mixed>
     */
    public function buildVariablesForUser(mixed $user = null, array $customVariables = []): array
    {
        $variables = $this->getCommonVariables();

        if ($user !== null) {
            $variables['user'] = [
                'id' => $user->id ?? '',
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
            ];

            // Keep flat versions for backward compatibility
            $variables['user_id'] = $user->id ?? '';
            $variables['user_name'] = $user->name ?? '';
            $variables['user_email'] = $user->email ?? '';
        }

        return array_merge($variables, $customVariables);
    }

    /**
     * Get common variables available to all templates.
     *
     * الحصول على المتغيرات المشتركة المتاحة لجميع القوالب.
     *
     * @return array<string, mixed>
     */
    public function getCommonVariables(): array
    {
        $tenantName = config('app.name', 'App');
        $appUrl = config('app.url', 'http://localhost');

        // Try to get tenant name if Stancl/Tenancy is active
        if (function_exists('tenant') && tenant()) {
            $tenantName = tenant('name') ?? tenant('id') ?? $tenantName;
        }

        return [
            'tenant_name' => $tenantName,
            'family_name' => $tenantName, // Alias for family-based applications
            'app_name' => config('app.name', 'App'),
            'app_url' => $appUrl,
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i'),
            'datetime' => now()->format('Y-m-d H:i'),
            'year' => now()->format('Y'),
        ];
    }

    /**
     * Render a template with variables.
     *
     * عرض قالب مع المتغيرات.
     *
     * @param  NotificationTemplate  $template  The template to render
     * @param  array<string, mixed>  $variables  Variables for replacement
     */
    public function render(NotificationTemplate $template, array $variables = []): NotificationMessage
    {
        // Merge common variables with provided variables
        // Custom variables take precedence
        $allVariables = array_merge($this->getCommonVariables(), $variables);

        return $template->toNotificationMessage($allVariables);
    }

    /**
     * Render a template by slug.
     *
     * عرض قالب بواسطة المعرف.
     *
     * @param  string  $slug  Template slug
     * @param  array<string, mixed>  $variables  Variables for replacement
     * @return NotificationMessage|null Returns null if template not found
     */
    public function renderBySlug(string $slug, array $variables = []): ?NotificationMessage
    {
        $template = NotificationTemplate::active()->bySlug($slug)->first();

        if (! $template) {
            return null;
        }

        return $this->render($template, $variables);
    }

    /**
     * Render a template by slug or throw exception if not found.
     *
     * @param  string  $slug  Template slug
     * @param  array<string, mixed>  $variables  Variables for replacement
     *
     * @throws InvalidArgumentException If template not found
     */
    public function renderBySlugOrFail(string $slug, array $variables = []): NotificationMessage
    {
        $message = $this->renderBySlug($slug, $variables);

        if ($message === null) {
            throw new InvalidArgumentException(__('notify::notify.template_not_found').": {$slug}");
        }

        return $message;
    }

    /**
     * Render a template for a specific user.
     *
     * Convenience method that builds user variables automatically.
     *
     * @param  NotificationTemplate  $template  The template to render
     * @param  mixed  $user  User model
     * @param  array<string, mixed>  $extraVariables  Additional variables
     */
    public function renderForUser(NotificationTemplate $template, mixed $user, array $extraVariables = []): NotificationMessage
    {
        $variables = $this->buildVariablesForUser($user, $extraVariables);

        return $this->render($template, $variables);
    }

    /**
     * Get a template by slug.
     *
     * @param  string  $slug  Template slug
     * @param  bool  $activeOnly  Only return if active (default: true)
     */
    public function getTemplate(string $slug, bool $activeOnly = true): ?NotificationTemplate
    {
        $query = NotificationTemplate::bySlug($slug);

        if ($activeOnly) {
            $query->active();
        }

        return $query->first();
    }
}
