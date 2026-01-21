<?php

namespace Asimnet\Notify\Models;

use Asimnet\Notify\DTOs\NotificationMessage;
use Asimnet\Notify\Models\Concerns\BelongsToTenantOptionally;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationTemplate extends Model
{
    use BelongsToTenantOptionally;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'title',
        'body',
        'image_url',
        'variables',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getTable(): string
    {
        return config('notify.tables.templates', 'notify_templates');
    }

    protected static function booted(): void
    {
        static::creating(function (NotificationTemplate $template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    /**
     * Render the template with the given variables.
     *
     * تنفيذ القالب مع المتغيرات المحددة.
     *
     * @param  array<string, mixed>  $variables
     * @return array{title: string, body: string, image_url: ?string}
     */
    public function render(array $variables = []): array
    {
        return [
            'title' => $this->replaceVariables($this->title, $variables),
            'body' => $this->replaceVariables($this->body, $variables),
            'image_url' => $this->image_url,
        ];
    }

    /**
     * Replace {variable} placeholders with actual values.
     * Supports dot notation: {user.name} from ['user' => ['name' => 'John']]
     *
     * استبدال العناصر النائبة {متغير} بالقيم الفعلية.
     * يدعم التدوين النقطي: {user.name} من ['user' => ['name' => 'John']]
     */
    protected function replaceVariables(string $text, array $variables): string
    {
        // Flatten nested arrays for dot notation access
        $flattened = $this->flattenArray($variables);

        foreach ($flattened as $key => $value) {
            if ($value === null) {
                $value = '';
            }
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $text = str_replace('{'.$key.'}', (string) $value, $text);
        }

        return $text;
    }

    /**
     * Flatten nested array with dot notation keys.
     *
     * تسطيح المصفوفة المتداخلة باستخدام مفاتيح التدوين النقطي.
     *
     * @param  array<string, mixed>  $array
     * @return array<string, mixed>
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            // Also keep the flat key for convenience (user_name alongside user.name)
            if ($prefix === '') {
                $result[$key] = $value;
            }

            if (is_array($value) && ! empty($value) && ! array_is_list($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Get variable names used in the template.
     *
     * الحصول على أسماء المتغيرات المستخدمة في القالب.
     *
     * @return array<string>
     */
    public function getVariableNames(): array
    {
        preg_match_all('/\{([a-zA-Z0-9_.]+)\}/', $this->title.' '.$this->body, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Convert template to NotificationMessage with variables replaced.
     *
     * تحويل القالب إلى NotificationMessage مع استبدال المتغيرات.
     *
     * @param  array<string, mixed>  $variables
     */
    public function toNotificationMessage(array $variables = []): NotificationMessage
    {
        $rendered = $this->render($variables);

        $message = NotificationMessage::create(
            $rendered['title'],
            $rendered['body']
        );

        if ($rendered['image_url']) {
            $message = $message->withImage($rendered['image_url']);
        }

        return $message;
    }

    /**
     * Scope to only active templates.
     *
     * نطاق للقوالب النشطة فقط.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find by slug.
     *
     * نطاق للبحث بواسطة المعرف.
     */
    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    protected static function newFactory()
    {
        return \Asimnet\Notify\Database\Factories\NotificationTemplateFactory::new();
    }
}
