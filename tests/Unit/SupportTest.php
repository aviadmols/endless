<?php

namespace Tests\Unit;

use App\Services\Html\HtmlSanitizer;
use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{
    /* ------------------------------------------------------------- phones */

    public function test_israeli_numbers_normalise_to_e164(): void
    {
        foreach (['050-1234567', '0501234567', '050 123 4567', '+972501234567', '00972501234567', '972501234567'] as $input) {
            $this->assertSame('+972501234567', PhoneNumber::normalize($input, '972'), "failed for {$input}");
        }
    }

    public function test_other_country_codes_are_respected(): void
    {
        $this->assertSame('+12125550100', PhoneNumber::normalize('212-555-0100', '1'));
        $this->assertSame('+447400000000', PhoneNumber::normalize('07400 000000', '44'));
    }

    public function test_rubbish_input_returns_null(): void
    {
        $this->assertNull(PhoneNumber::normalize('123', '972'));
        $this->assertNull(PhoneNumber::normalize('', '972'));
        $this->assertNull(PhoneNumber::normalize(null, '972'));
    }

    public function test_the_gateway_format_is_local_for_israel(): void
    {
        $this->assertSame('0501234567', PhoneNumber::toGatewayFormat('+972501234567'));
        $this->assertSame('12125550100', PhoneNumber::toGatewayFormat('+12125550100'));
    }

    public function test_display_format(): void
    {
        $this->assertSame('050-1234567', PhoneNumber::format('+972501234567'));
    }

    public function test_phone_versus_email_detection(): void
    {
        $this->assertTrue(PhoneNumber::looksLikePhone('050-1234567'));
        $this->assertTrue(PhoneNumber::looksLikePhone('+972 50 123 4567'));
        $this->assertFalse(PhoneNumber::looksLikePhone('someone@example.com'));
        $this->assertFalse(PhoneNumber::looksLikePhone(''));
    }

    /* ---------------------------------------------------------- sanitizer */

    protected function sanitizer(): HtmlSanitizer
    {
        return new HtmlSanitizer;
    }

    public function test_scripts_and_handlers_are_removed(): void
    {
        $clean = $this->sanitizer()->clean('<p>שלום</p><script>alert(1)</script><p onclick="x()">עולם</p>');

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringContainsString('<p>שלום</p>', $clean);
        $this->assertStringContainsString('עולם', $clean);
    }

    public function test_unknown_tags_are_unwrapped_but_keep_their_text(): void
    {
        $clean = $this->sanitizer()->clean('<div class="x">טקסט <span>פנימי</span></div>');

        $this->assertStringNotContainsString('<div', $clean);
        $this->assertStringContainsString('טקסט', $clean);
        $this->assertStringContainsString('פנימי', $clean);
    }

    public function test_links_keep_safe_hrefs_only(): void
    {
        $clean = $this->sanitizer()->clean('<a href="https://ok.example">טוב</a><a href="javascript:alert(1)">רע</a>');

        $this->assertStringContainsString('href="https://ok.example"', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('rel="noopener nofollow"', $clean);
    }

    public function test_empty_paragraphs_are_dropped(): void
    {
        $this->assertSame('', $this->sanitizer()->clean('<p><br></p><p>&nbsp;</p>'));
        $this->assertSame('', $this->sanitizer()->clean(''));
    }

    public function test_plain_text_extraction(): void
    {
        $plain = $this->sanitizer()->toPlainText('<p>שורה ראשונה</p><p>שורה שנייה</p>');

        $this->assertStringContainsString('שורה ראשונה', $plain);
        $this->assertStringContainsString('שורה שנייה', $plain);
        $this->assertStringNotContainsString('<', $plain);
    }

    public function test_hebrew_survives_sanitising(): void
    {
        $clean = $this->sanitizer()->clean('<p>אבא יקר, בתמונה הזאת אתה עומד לידי — בסיום הקורס.</p>');

        $this->assertStringContainsString('אבא יקר, בתמונה הזאת אתה עומד לידי — בסיום הקורס.', $clean);
    }
}
