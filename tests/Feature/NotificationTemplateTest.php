<?php

namespace Asimnet\Notify\Tests\Feature;

use Asimnet\Notify\Models\NotificationTemplate;
use Asimnet\Notify\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_can_be_created_with_factory(): void
    {
        $template = NotificationTemplate::factory()->create();

        $this->assertDatabaseHas('notify_templates', [
            'id' => $template->id,
            'is_active' => true,
        ]);
    }

    public function test_template_auto_generates_slug_from_name(): void
    {
        $template = NotificationTemplate::factory()->create([
            'name' => 'Welcome Message',
            'slug' => null,
        ]);

        $this->assertEquals('welcome-message', $template->slug);
    }

    public function test_template_renders_simple_variables(): void
    {
        $template = NotificationTemplate::factory()->create([
            'title' => 'Hello {user_name}!',
            'body' => 'Your email is {user_email}.',
        ]);

        $rendered = $template->render([
            'user_name' => 'John',
            'user_email' => 'john@example.com',
        ]);

        $this->assertEquals('Hello John!', $rendered['title']);
        $this->assertEquals('Your email is john@example.com.', $rendered['body']);
    }

    public function test_template_renders_dot_notation_variables(): void
    {
        $template = NotificationTemplate::factory()->create([
            'title' => 'Hello {user.name}!',
            'body' => 'Welcome to {tenant_name}.',
        ]);

        $rendered = $template->render([
            'user' => ['name' => 'Jane', 'email' => 'jane@example.com'],
            'tenant_name' => 'My Family',
        ]);

        $this->assertEquals('Hello Jane!', $rendered['title']);
        $this->assertEquals('Welcome to My Family.', $rendered['body']);
    }

    public function test_template_handles_missing_variables_gracefully(): void
    {
        $template = NotificationTemplate::factory()->create([
            'title' => 'Hello {user_name}!',
            'body' => 'Your code is {missing_var}.',
        ]);

        $rendered = $template->render([
            'user_name' => 'John',
            // 'missing_var' intentionally not provided
        ]);

        $this->assertEquals('Hello John!', $rendered['title']);
        // Unreplaced variables remain as-is
        $this->assertStringContainsString('{missing_var}', $rendered['body']);
    }

    public function test_template_handles_null_values(): void
    {
        $template = NotificationTemplate::factory()->create([
            'title' => 'Hello {user_name}!',
            'body' => 'Value: {nullable}',
        ]);

        $rendered = $template->render([
            'user_name' => 'John',
            'nullable' => null,
        ]);

        $this->assertEquals('Hello John!', $rendered['title']);
        $this->assertEquals('Value: ', $rendered['body']);
    }

    public function test_template_converts_to_notification_message(): void
    {
        $template = NotificationTemplate::factory()->create([
            'title' => 'Hello {name}!',
            'body' => 'Welcome!',
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $message = $template->toNotificationMessage(['name' => 'John']);

        $this->assertEquals('Hello John!', $message->title);
        $this->assertEquals('Welcome!', $message->body);
        $this->assertEquals('https://example.com/image.jpg', $message->imageUrl);
    }

    public function test_template_extracts_variable_names(): void
    {
        $template = NotificationTemplate::factory()->create([
            'title' => 'Hello {user.name}!',
            'body' => 'Your email is {user.email}. Welcome to {tenant_name}.',
        ]);

        $variableNames = $template->getVariableNames();

        $this->assertContains('user.name', $variableNames);
        $this->assertContains('user.email', $variableNames);
        $this->assertContains('tenant_name', $variableNames);
    }

    public function test_active_scope_filters_inactive_templates(): void
    {
        NotificationTemplate::factory()->create(['is_active' => true, 'slug' => 'active']);
        NotificationTemplate::factory()->create(['is_active' => false, 'slug' => 'inactive']);

        $activeTemplates = NotificationTemplate::active()->get();

        $this->assertCount(1, $activeTemplates);
        $this->assertEquals('active', $activeTemplates->first()->slug);
    }

    public function test_by_slug_scope_finds_template(): void
    {
        NotificationTemplate::factory()->create(['slug' => 'welcome']);
        NotificationTemplate::factory()->create(['slug' => 'goodbye']);

        $template = NotificationTemplate::bySlug('welcome')->first();

        $this->assertNotNull($template);
        $this->assertEquals('welcome', $template->slug);
    }

    public function test_inactive_factory_state(): void
    {
        $template = NotificationTemplate::factory()->inactive()->create();

        $this->assertFalse($template->is_active);
    }

    public function test_welcome_factory_state(): void
    {
        $template = NotificationTemplate::factory()->welcome()->create();

        $this->assertEquals('welcome', $template->slug);
        $this->assertStringContainsString('{user.name}', $template->title);
    }

    public function test_template_preserves_image_url_in_render(): void
    {
        $template = NotificationTemplate::factory()->withImage('https://example.com/img.png')->create();

        $rendered = $template->render([]);

        $this->assertEquals('https://example.com/img.png', $rendered['image_url']);
    }
}
