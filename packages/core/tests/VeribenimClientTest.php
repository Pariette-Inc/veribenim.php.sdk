<?php

declare(strict_types=1);

namespace Veribenim\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Veribenim\Tests\Support\RecordingVeribenimClient;
use Veribenim\VeribenimConfig;

class VeribenimClientTest extends TestCase
{
    private const TOKEN = 'testtoken1234567890abcdef1234567';

    private RecordingVeribenimClient $client;

    protected function setUp(): void
    {
        $this->client = new RecordingVeribenimClient(new VeribenimConfig(
            token: self::TOKEN,
            domain: 'claude.com',
        ));
    }

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function test_constructor_accepts_plain_token_string(): void
    {
        $client = new RecordingVeribenimClient(self::TOKEN);
        $client->queuedResponses[] = ['ok' => true];

        $client->getPreferences();

        $this->assertSame('/api/preferences/' . self::TOKEN, $client->lastRequest()['path']);
    }

    // -------------------------------------------------------------------------
    // logImpression
    // -------------------------------------------------------------------------

    public function test_log_impression_posts_server_context_and_returns_true_on_success(): void
    {
        $_SERVER['REQUEST_URI']     = '/blog/yazi';
        $_SERVER['HTTP_REFERER']    = 'https://google.com';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';

        $this->client->queuedResponses[] = ['status' => true];

        $this->assertTrue($this->client->logImpression());

        $request = $this->client->lastRequest();
        $this->assertSame('POST', $request['method']);
        $this->assertSame('/api/impressions/' . self::TOKEN, $request['path']);
        $this->assertSame('/blog/yazi', $request['body']['url']);
        $this->assertSame('https://google.com', $request['body']['referrer']);
        $this->assertSame('PHPUnit', $request['body']['user_agent']);

        unset($_SERVER['REQUEST_URI'], $_SERVER['HTTP_REFERER'], $_SERVER['HTTP_USER_AGENT']);
    }

    public function test_log_impression_payload_overrides_server_values(): void
    {
        $_SERVER['REQUEST_URI'] = '/orjinal';
        $this->client->queuedResponses[] = ['status' => true];

        $this->client->logImpression(['url' => '/ozel-url']);

        $this->assertSame('/ozel-url', $this->client->lastRequest()['body']['url']);

        unset($_SERVER['REQUEST_URI']);
    }

    public function test_log_impression_returns_false_on_failure(): void
    {
        // queuedResponses boş → request() null döner (4xx/5xx/timeout senaryosu)
        $this->assertFalse($this->client->logImpression());
    }

    // -------------------------------------------------------------------------
    // logConsent
    // -------------------------------------------------------------------------

    public function test_log_consent_sends_action_only_when_no_preferences(): void
    {
        $this->client->queuedResponses[] = ['ok' => true];

        $this->assertTrue($this->client->logConsent('accept_all'));

        $request = $this->client->lastRequest();
        $this->assertSame('/api/consents/' . self::TOKEN . '/log', $request['path']);
        $this->assertSame(['action' => 'accept_all'], $request['body']);
    }

    public function test_log_consent_includes_consents_and_session_id(): void
    {
        $this->client->queuedResponses[] = ['ok' => true];
        $preferences = [
            'strictly_necessary' => true,
            'functional'         => false,
            'analytics'          => true,
            'marketing'          => false,
        ];

        $this->client->logConsent('save_preferences', $preferences, 'sess-42');

        $body = $this->client->lastRequest()['body'];
        $this->assertSame('save_preferences', $body['action']);
        $this->assertSame($preferences, $body['consents']);
        $this->assertSame('sess-42', $body['session_id']);
    }

    // -------------------------------------------------------------------------
    // getPreferences / savePreferences
    // -------------------------------------------------------------------------

    public function test_get_preferences_without_session_id(): void
    {
        $this->client->queuedResponses[] = ['current_consents' => ['analytics' => true]];

        $result = $this->client->getPreferences();

        $request = $this->client->lastRequest();
        $this->assertSame('GET', $request['method']);
        $this->assertSame('/api/preferences/' . self::TOKEN, $request['path']);
        $this->assertSame(['current_consents' => ['analytics' => true]], $result);
    }

    public function test_get_preferences_urlencodes_session_id(): void
    {
        $this->client->getPreferences('sess id/özel');

        $this->assertSame(
            '/api/preferences/' . self::TOKEN . '?session_id=' . urlencode('sess id/özel'),
            $this->client->lastRequest()['path']
        );
    }

    public function test_save_preferences_wraps_payload_in_consents_key(): void
    {
        $this->client->savePreferences(['analytics' => true], 'sess-1');

        $request = $this->client->lastRequest();
        $this->assertSame('POST', $request['method']);
        $this->assertSame('/api/preferences/' . self::TOKEN, $request['path']);
        $this->assertSame(['consents' => ['analytics' => true], 'session_id' => 'sess-1'], $request['body']);
    }

    // -------------------------------------------------------------------------
    // scriptTag
    // -------------------------------------------------------------------------

    public function test_script_tag_uses_domain_derived_bundle_url(): void
    {
        $this->assertSame(
            '<script src="https://bundles.veribenim.com/claudecom.js" async></script>',
            $this->client->scriptTag()
        );
    }

    public function test_script_tag_escapes_script_url(): void
    {
        $client = new RecordingVeribenimClient(new VeribenimConfig(
            token: self::TOKEN,
            scriptUrl: 'https://cdn.example.com/x.js?a=1&b="2"',
        ));

        $this->assertSame(
            '<script src="https://cdn.example.com/x.js?a=1&amp;b=&quot;2&quot;" async></script>',
            $client->scriptTag()
        );
    }

    public function test_script_tag_returns_comment_without_domain_or_script_url(): void
    {
        $client = new RecordingVeribenimClient(new VeribenimConfig(token: self::TOKEN));

        $this->assertSame('<!-- Veribenim: domain veya scriptUrl gerekli -->', $client->scriptTag());
    }

    // -------------------------------------------------------------------------
    // logFormConsent
    // -------------------------------------------------------------------------

    public function test_log_form_consent_omits_empty_optional_fields(): void
    {
        $this->client->logFormConsent('newsletter', true);

        $request = $this->client->lastRequest();
        $this->assertSame('/api/form-consents/' . self::TOKEN, $request['path']);
        $this->assertSame(['form_name' => 'newsletter', 'consented' => true], $request['body']);
    }

    public function test_log_form_consent_includes_consent_text_and_metadata(): void
    {
        $this->client->logFormConsent('contact', false, 'KVKK metnini okudum', ['ip' => '127.0.0.1']);

        $body = $this->client->lastRequest()['body'];
        $this->assertFalse($body['consented']);
        $this->assertSame('KVKK metnini okudum', $body['consent_text']);
        $this->assertSame(['ip' => '127.0.0.1'], $body['metadata']);
    }

    // -------------------------------------------------------------------------
    // Form Generator
    // -------------------------------------------------------------------------

    public function test_get_form_schema_rawurlencodes_slug_and_lang(): void
    {
        $this->client->getFormSchema('iletişim formu', 'en');

        $this->assertSame(
            '/api/public/forms/' . self::TOKEN . '/' . rawurlencode('iletişim formu') . '?lang=en',
            $this->client->lastRequest()['path']
        );
    }

    public function test_get_form_schema_without_lang_has_no_query_string(): void
    {
        $this->client->getFormSchema('iletisim-formu');

        $this->assertSame(
            '/api/public/forms/' . self::TOKEN . '/iletisim-formu',
            $this->client->lastRequest()['path']
        );
    }

    public function test_submit_form_posts_data_to_slug_endpoint(): void
    {
        $this->client->submitForm('iletisim-formu', ['uuid-1' => 'Ahmet']);

        $request = $this->client->lastRequest();
        $this->assertSame('POST', $request['method']);
        $this->assertSame('/api/public/forms/' . self::TOKEN . '/iletisim-formu', $request['path']);
        $this->assertSame(['uuid-1' => 'Ahmet'], $request['body']);
    }

    public function test_render_form_html_returns_comment_when_schema_missing(): void
    {
        $html = $this->client->renderFormHtml('yok-boyle-form');

        $this->assertSame('<!-- Veribenim: form "yok-boyle-form" bulunamadı -->', $html);
    }

    public function test_render_form_html_builds_fields_sorted_by_order(): void
    {
        $this->client->queuedResponses[] = [
            'fields' => [
                ['uuid' => 'u-msg', 'type' => 'textarea', 'label' => 'Mesaj', 'order' => 2],
                ['uuid' => 'u-name', 'type' => 'input', 'label' => 'Ad Soyad', 'order' => 1, 'required' => true],
            ],
            'settings' => ['submit_button_text' => 'Yolla'],
        ];

        $html = $this->client->renderFormHtml('iletisim', ['id' => 'my-form', 'class' => 'custom']);

        $this->assertStringContainsString('<form id="my-form" class="custom" data-vb-form="iletisim"', $html);
        $this->assertStringContainsString(
            'data-vb-action="https://live.veribenim.com/api/public/forms/' . self::TOKEN . '/iletisim"',
            $html
        );
        // order'a göre sıralama: name (1) mesajdan (2) önce gelmeli
        $this->assertLessThan(strpos($html, 'u-msg'), strpos($html, 'u-name'));
        $this->assertStringContainsString('<span class="vb-required"', $html);
        $this->assertStringContainsString('<textarea id="vb-u-msg"', $html);
        $this->assertStringContainsString('<button type="submit" class="vb-submit">Yolla</button>', $html);
    }

    public function test_render_form_html_escapes_field_labels(): void
    {
        $this->client->queuedResponses[] = [
            'fields' => [
                ['uuid' => 'u-1', 'type' => 'input', 'label' => '<script>alert(1)</script>'],
            ],
        ];

        $html = $this->client->renderFormHtml('xss-form');

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_render_form_html_renders_dropdown_options_and_special_types(): void
    {
        $this->client->queuedResponses[] = [
            'fields' => [
                ['type' => 'heading', 'label' => 'Bölüm 1'],
                ['type' => 'divider'],
                [
                    'uuid'    => 'u-city',
                    'type'    => 'dropdown',
                    'label'   => 'Şehir',
                    'options' => [['value' => '34', 'label' => 'İstanbul'], ['value' => '06', 'label' => 'Ankara']],
                ],
                ['uuid' => 'u-mail', 'type' => 'email', 'label' => 'E-posta'],
                [
                    'uuid'       => 'u-age',
                    'type'       => 'number',
                    'label'      => 'Yaş',
                    'validation' => ['min' => 18, 'max' => 99],
                ],
            ],
        ];

        $html = $this->client->renderFormHtml('detayli');

        $this->assertStringContainsString('<div class="vb-heading">Bölüm 1</div>', $html);
        $this->assertStringContainsString('<hr class="vb-divider">', $html);
        $this->assertStringContainsString('<option value="34">İstanbul</option>', $html);
        $this->assertStringContainsString('<input type="email" id="vb-u-mail"', $html);
        $this->assertStringContainsString('min="18"', $html);
        $this->assertStringContainsString('max="99"', $html);
    }

    // -------------------------------------------------------------------------
    // submitDsar
    // -------------------------------------------------------------------------

    public function test_submit_dsar_rejects_invalid_request_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Geçersiz DSAR tipi: silinme');

        $this->client->submitDsar('silinme', 'Ahmet Yılmaz', 'ahmet@example.com');
    }

    public function test_submit_dsar_posts_valid_request(): void
    {
        $this->client->submitDsar('erasure', 'Ahmet Yılmaz', 'ahmet@example.com', 'Verilerim silinsin');

        $request = $this->client->lastRequest();
        $this->assertSame('/api/dsar/' . self::TOKEN, $request['path']);
        $this->assertSame([
            'request_type' => 'erasure',
            'full_name'    => 'Ahmet Yılmaz',
            'email'        => 'ahmet@example.com',
            'description'  => 'Verilerim silinsin',
        ], $request['body']);
    }

    public function test_submit_dsar_omits_empty_description(): void
    {
        $this->client->submitDsar('access', 'Ahmet', 'a@b.com');

        $this->assertArrayNotHasKey('description', $this->client->lastRequest()['body']);
    }

    // -------------------------------------------------------------------------
    // collectAnalytics
    // -------------------------------------------------------------------------

    public function test_collect_analytics_requires_sid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client->collectAnalytics(['url' => 'https://claude.com']);
    }

    public function test_collect_analytics_requires_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client->collectAnalytics(['sid' => 'uuid-1']);
    }

    public function test_collect_analytics_posts_beacon(): void
    {
        $payload = ['sid' => 'uuid-1', 'url' => 'https://claude.com/blog'];

        $this->assertTrue($this->client->collectAnalytics($payload));

        $request = $this->client->lastRequest();
        $this->assertSame('POST(beacon)', $request['method']);
        $this->assertSame('/api/v/' . self::TOKEN . '/e', $request['path']);
        $this->assertSame($payload, $request['body']);
    }

    public function test_collect_analytics_returns_false_when_beacon_fails(): void
    {
        $this->client->beaconResult = false;

        $this->assertFalse($this->client->collectAnalytics(['sid' => 'uuid-1', 'url' => '/x']));
    }

    // -------------------------------------------------------------------------
    // withdrawConsent
    // -------------------------------------------------------------------------

    public function test_withdraw_consent_logs_withdraw_action(): void
    {
        $this->client->queuedResponses[] = ['status' => true];

        $this->assertTrue($this->client->withdrawConsent('sess-1'));

        $request = $this->client->lastRequest();
        $this->assertSame('/api/consents/' . self::TOKEN . '/log', $request['path']);
        $this->assertSame('withdraw', $request['body']['action']);
        $this->assertSame('sess-1', $request['body']['session_id']);
        $this->assertArrayNotHasKey('consents', $request['body']);
    }

    // -------------------------------------------------------------------------
    // trackEvent
    // -------------------------------------------------------------------------

    public function test_track_event_requires_positive_event_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client->trackEvent(0, 'sess-1');
    }

    public function test_track_event_requires_session_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client->trackEvent(7, '');
    }

    public function test_track_event_posts_beacon_with_optional_fields(): void
    {
        $this->assertTrue($this->client->trackEvent(7, 'sess-1', [
            'txt' => 'Satın Al',
            'url' => 'https://claude.com/urun',
        ]));

        $request = $this->client->lastRequest();
        $this->assertSame('POST(beacon)', $request['method']);
        $this->assertSame('/api/v/' . self::TOKEN . '/ce', $request['path']);
        $this->assertSame(7, $request['body']['eid']);
        $this->assertSame('sess-1', $request['body']['sid']);
        $this->assertSame('Satın Al', $request['body']['txt']);
        $this->assertSame('https://claude.com/urun', $request['body']['url']);
        $this->assertArrayNotHasKey('vid', $request['body']);
    }

    // -------------------------------------------------------------------------
    // getVendors
    // -------------------------------------------------------------------------

    public function test_get_vendors_calls_public_endpoint(): void
    {
        $this->client->queuedResponses[] = ['status' => true, 'data' => ['gvl_version' => 42, 'vendors' => []]];

        $result = $this->client->getVendors();

        $request = $this->client->lastRequest();
        $this->assertSame('GET', $request['method']);
        $this->assertSame('/api/public/vendors/' . self::TOKEN, $request['path']);
        $this->assertSame(42, $result['data']['gvl_version']);
    }

    // -------------------------------------------------------------------------
    // scanCookies / verifyDomain
    // -------------------------------------------------------------------------

    public function test_scan_cookies_posts_url(): void
    {
        $this->client->scanCookies('https://claude.com');

        $request = $this->client->lastRequest();
        $this->assertSame('/api/public/cookie-scan', $request['path']);
        $this->assertSame(['url' => 'https://claude.com'], $request['body']);
    }

    public function test_verify_domain_cleans_protocol_www_and_path(): void
    {
        $this->client->verifyDomain('https://WWW.Example.com/some/path');

        $this->assertSame('/api/public/verify/example.com', $this->client->lastRequest()['path']);
    }

    public function test_verify_domain_falls_back_to_config_domain(): void
    {
        $this->client->verifyDomain();

        $this->assertSame('/api/public/verify/claude.com', $this->client->lastRequest()['path']);
    }

    public function test_verify_domain_throws_without_domain(): void
    {
        $client = new RecordingVeribenimClient(new VeribenimConfig(token: self::TOKEN));

        $this->expectException(InvalidArgumentException::class);

        $client->verifyDomain();
    }

    // -------------------------------------------------------------------------
    // impressionPixelUrl
    // -------------------------------------------------------------------------

    public function test_impression_pixel_url_without_options(): void
    {
        $this->assertSame(
            'https://live.veribenim.com/api/impressions/' . self::TOKEN . '/pixel',
            $this->client->impressionPixelUrl()
        );
    }

    public function test_impression_pixel_url_builds_query_string(): void
    {
        $url = $this->client->impressionPixelUrl([
            'url'        => '/sayfa?a=1',
            'domain'     => 'claude.com',
            'session_id' => 'sess-1',
            'referrer'   => 'https://google.com',
        ]);

        $this->assertStringStartsWith(
            'https://live.veribenim.com/api/impressions/' . self::TOKEN . '/pixel?',
            $url
        );

        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $this->assertSame([
            'u' => '/sayfa?a=1',
            'd' => 'claude.com',
            's' => 'sess-1',
            'r' => 'https://google.com',
        ], $params);
    }
}
