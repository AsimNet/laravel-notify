<?php

namespace Asimnet\Notify\DTOs;

use JsonSerializable;

/**
 * Immutable value object representing a notification message.
 *
 * Use the fluent builder pattern to configure the message:
 *
 * ```php
 * $message = NotificationMessage::create('Title', 'Body')
 *     ->withImage('https://example.com/image.jpg')
 *     ->withActionUrl('app://open/screen')
 *     ->withData(['key' => 'value'])
 *     ->withAnalyticsLabel('campaign_2024');
 * ```
 *
 * كائن قيمة ثابت يمثل رسالة الإشعار.
 * استخدم نمط البناء المتسلسل لتكوين الرسالة.
 */
class NotificationMessage implements JsonSerializable
{
    /**
     * Create a new notification message instance.
     *
     * @param  string  $title  Notification title (required) / عنوان الإشعار (مطلوب)
     * @param  string  $body  Notification body (required) / نص الإشعار (مطلوب)
     * @param  string|null  $imageUrl  Optional image URL / رابط الصورة (اختياري)
     * @param  string|null  $actionUrl  Optional deep link URL / رابط العمل (اختياري)
     * @param  array<string, mixed>  $data  Optional custom JSON payload / البيانات المخصصة (اختياري)
     * @param  string|null  $analyticsLabel  Optional analytics tracking label / تسمية التحليلات (اختياري)
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $imageUrl = null,
        public readonly ?string $actionUrl = null,
        public readonly array $data = [],
        public readonly ?string $analyticsLabel = null,
    ) {}

    /**
     * Create a new notification message with title and body.
     *
     * إنشاء رسالة إشعار جديدة بالعنوان والنص.
     */
    public static function create(string $title, string $body): self
    {
        return new self($title, $body);
    }

    /**
     * Create a new instance with an image URL.
     *
     * إنشاء نسخة جديدة مع رابط صورة.
     */
    public function withImage(string $url): self
    {
        return new self(
            title: $this->title,
            body: $this->body,
            imageUrl: $url,
            actionUrl: $this->actionUrl,
            data: $this->data,
            analyticsLabel: $this->analyticsLabel,
        );
    }

    /**
     * Create a new instance with an action URL (deep link).
     *
     * إنشاء نسخة جديدة مع رابط عمل (رابط عميق).
     */
    public function withActionUrl(string $url): self
    {
        return new self(
            title: $this->title,
            body: $this->body,
            imageUrl: $this->imageUrl,
            actionUrl: $url,
            data: $this->data,
            analyticsLabel: $this->analyticsLabel,
        );
    }

    /**
     * Create a new instance with additional data (merged with existing).
     *
     * إنشاء نسخة جديدة مع بيانات إضافية (مدمجة مع البيانات الحالية).
     *
     * @param  array<string, mixed>  $data
     */
    public function withData(array $data): self
    {
        return new self(
            title: $this->title,
            body: $this->body,
            imageUrl: $this->imageUrl,
            actionUrl: $this->actionUrl,
            data: array_merge($this->data, $data),
            analyticsLabel: $this->analyticsLabel,
        );
    }

    /**
     * Create a new instance with an analytics tracking label.
     *
     * إنشاء نسخة جديدة مع تسمية تتبع التحليلات.
     */
    public function withAnalyticsLabel(string $label): self
    {
        return new self(
            title: $this->title,
            body: $this->body,
            imageUrl: $this->imageUrl,
            actionUrl: $this->actionUrl,
            data: $this->data,
            analyticsLabel: $label,
        );
    }

    /**
     * Convert the message to an array.
     *
     * Only includes non-null values (except data which is included if non-empty).
     *
     * تحويل الرسالة إلى مصفوفة.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [
            'title' => $this->title,
            'body' => $this->body,
        ];

        if ($this->imageUrl !== null) {
            $array['image_url'] = $this->imageUrl;
        }

        if ($this->actionUrl !== null) {
            $array['action_url'] = $this->actionUrl;
        }

        if (! empty($this->data)) {
            $array['data'] = $this->data;
        }

        if ($this->analyticsLabel !== null) {
            $array['analytics_label'] = $this->analyticsLabel;
        }

        return $array;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * تحديد البيانات التي يجب تحويلها إلى JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
