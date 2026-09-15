<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_it_removes_script_tags_and_event_handler_attributes(): void
    {
        $sanitizer = new HtmlSanitizer;

        $result = $sanitizer->sanitize('<p onclick="alert(1)">Hello <script>alert(1)</script>world</p><img src=x onerror=alert(1)>');

        $this->assertSame('<p>Hello world</p>', $result);
    }

    public function test_it_keeps_safe_formatting_tags_and_adds_rel_to_blank_target_links(): void
    {
        $sanitizer = new HtmlSanitizer;

        $result = $sanitizer->sanitize('<p><strong>Bold</strong> and <em>italic</em> with a <a href="https://example.com" target="_blank">link</a>.</p>');

        $this->assertSame(
            '<p><strong>Bold</strong> and <em>italic</em> with a <a href="https://example.com" target="_blank" rel="noopener noreferrer">link</a>.</p>',
            $result,
        );
    }

    public function test_it_strips_javascript_urls_from_links(): void
    {
        $sanitizer = new HtmlSanitizer;

        $this->assertSame('<a>click</a>', $sanitizer->sanitize('<a href="javascript:alert(1)">click</a>'));
    }

    public function test_it_allows_relative_and_mail_links(): void
    {
        $sanitizer = new HtmlSanitizer;

        $this->assertSame('<a href="/dashboard">home</a>', $sanitizer->sanitize('<a href="/dashboard">home</a>'));
        $this->assertSame('<a href="mailto:a@b.com">email</a>', $sanitizer->sanitize('<a href="mailto:a@b.com">email</a>'));
    }

    public function test_it_unwraps_disallowed_wrapper_tags_but_keeps_their_safe_children(): void
    {
        $sanitizer = new HtmlSanitizer;

        $result = $sanitizer->sanitize('<div class="x"><p onclick="x">Kept text</p></div>');

        $this->assertSame('<p>Kept text</p>', $result);
    }

    public function test_it_unwraps_nested_disallowed_tags_down_to_plain_text(): void
    {
        $sanitizer = new HtmlSanitizer;

        $result = $sanitizer->sanitize('<div class="x"><span onclick="x">Kept text</span></div>');

        $this->assertSame('Kept text', $result);
    }

    public function test_it_removes_iframes_entirely(): void
    {
        $sanitizer = new HtmlSanitizer;

        $this->assertSame('', $sanitizer->sanitize('<iframe src="https://evil.example"></iframe>'));
    }

    public function test_it_returns_an_empty_string_for_empty_input(): void
    {
        $sanitizer = new HtmlSanitizer;

        $this->assertSame('', $sanitizer->sanitize(null));
        $this->assertSame('', $sanitizer->sanitize('   '));
    }
}
