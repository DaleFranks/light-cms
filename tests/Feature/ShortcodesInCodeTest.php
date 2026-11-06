<?php

namespace Tests\Feature;

use App\Markdown;
use App\Models\Form;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortcodesInCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Image::factory()->create(['filename' => 'team.jpg']);
        Form::factory()->create(['slug' => 'contact', 'fields' => [['label' => 'Email', 'type' => 'email']]]);
    }

    public function test_shortcodes_in_code_are_shown_as_written(): void
    {
        $html = Markdown::toHtml('Write `[form:contact]` or `![Team](team.jpg)` where it should go.', withBlocks: true);

        $this->assertStringContainsString('<code>[form:contact]</code>', $html);
        $this->assertStringContainsString('<code>![Team](team.jpg)</code>', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_shortcodes_in_a_fenced_code_block_are_shown_as_written(): void
    {
        $html = Markdown::toHtml("```\n[image:team.jpg|400|right]\n[youtube:https://youtu.be/abcdefghijk]\n```\n\n~~~markdown\n[form:contact]\n~~~", withBlocks: true);

        $this->assertStringContainsString("<pre><code>[image:team.jpg|400|right]\n[youtube:https://youtu.be/abcdefghijk]\n</code></pre>", $html);
        $this->assertStringContainsString("<code class=\"language-markdown\">[form:contact]\n</code>", $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('<form', $html);
    }

    public function test_shortcodes_beside_code_still_work(): void
    {
        $html = Markdown::toHtml("Use `[image:file]`, like this: [image:team.jpg]\n\n```\ncode\n```\n\n[form:contact]", withBlocks: true);

        $this->assertStringContainsString('<code>[image:file]</code>', $html);
        $this->assertStringContainsString('<img src="/uploads/images/team.jpg"', $html);
        $this->assertStringContainsString('<form class="site-form', $html);
    }

    public function test_a_lone_backtick_doesnt_hide_the_shortcodes_after_it(): void
    {
        $html = Markdown::toHtml("It's 5` long.\n\n[image:team.jpg] and a closing ` here", withBlocks: true);

        $this->assertStringContainsString('<img src="/uploads/images/team.jpg"', $html);
    }
}
