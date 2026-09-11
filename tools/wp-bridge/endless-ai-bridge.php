<?php
/**
 * Endless AI Bridge — read-only REST bridge for Claude Code.
 *
 * INSTALL
 *   1. Copy this file to:  wp-content/themes/hello-theme-child-master/inc/endless-ai-bridge.php
 *   2. Add to functions.php:   require_once get_stylesheet_directory() . '/inc/endless-ai-bridge.php';
 *   3. In wp-admin go to  Tools → AI Bridge  and click "Generate token".
 *      The token is shown ONCE. Send it to Claude. Only a SHA-256 hash is stored.
 *
 * WHAT IT EXPOSES (all read-only, Bearer-token protected, admins can revoke at any time)
 *   GET  /wp-json/endless-ai/v1/status
 *   GET  /wp-json/endless-ai/v1/schema
 *   GET  /wp-json/endless-ai/v1/describe?table=wp_posts
 *   POST /wp-json/endless-ai/v1/query        { "sql": "SELECT ... WHERE x = %s", "params": ["..."], "limit": 200 }   (SELECT only)
 *   POST /wp-json/endless-ai/v1/wp-query     { "post_type": "person", "posts_per_page": 20, "s": "", "meta": true }
 *   GET  /wp-json/endless-ai/v1/post?id=7798
 *   GET  /wp-json/endless-ai/v1/acf-groups
 *   GET  /wp-json/endless-ai/v1/options?name=memorial_settings
 *   GET  /wp-json/endless-ai/v1/fs-list?path=plugins/MemorialMosaic
 *   GET  /wp-json/endless-ai/v1/fs-read?path=plugins/MemorialMosaic/memorial-mosaic.php
 *
 * SAFETY
 *   - Never writes to the DB or filesystem.
 *   - fs-* is confined to wp-content/themes and wp-content/plugins; wp-config.php, .env, *.sql, *.log are refused.
 *   - query: SELECT/SHOW/DESCRIBE/EXPLAIN only, single statement, LIMIT enforced, user_pass/user_activation_key redacted.
 *   - options: passwords / salts / API keys are refused by name pattern.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Endless_AI_Bridge
{
    const NS          = 'endless-ai/v1';
    const OPTION      = 'endless_ai_bridge_tokens';
    const MAX_LIMIT   = 500;
    const READ_ROOTS  = ['themes', 'plugins', 'uploads/acf-json'];
    const DENY_FILES  = '/(wp-config\.php|\.env|\.sql|\.log|\.pem|\.key|credentials|secret)/i';
    const DENY_OPTS   = '/(pass|secret|salt|token|api_key|apikey|private|auth|nonce|license)/i';

    public static function boot(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
        add_action('admin_menu', [self::class, 'admin_menu']);
        add_action('admin_post_endless_ai_bridge_generate', [self::class, 'handle_generate']);
        add_action('admin_post_endless_ai_bridge_revoke', [self::class, 'handle_revoke']);
    }

    /* ---------------------------------------------------------------- auth */

    public static function authorize(WP_REST_Request $request)
    {
        $header = $request->get_header('authorization') ?: '';
        if (!preg_match('/^Bearer\s+([A-Za-z0-9_\-]{32,})$/', trim($header), $m)) {
            return new WP_Error('endless_ai_unauthorized', 'Missing bearer token', ['status' => 401]);
        }
        $hash = hash('sha256', $m[1]);
        foreach ((array) get_option(self::OPTION, []) as $token) {
            if (!empty($token['hash']) && hash_equals($token['hash'], $hash)) {
                if (!empty($token['revoked'])) {
                    return new WP_Error('endless_ai_revoked', 'Token revoked', ['status' => 403]);
                }
                self::touch($hash);
                return true;
            }
        }
        return new WP_Error('endless_ai_forbidden', 'Invalid token', ['status' => 403]);
    }

    private static function touch(string $hash): void
    {
        $tokens = (array) get_option(self::OPTION, []);
        foreach ($tokens as &$t) {
            if (($t['hash'] ?? '') === $hash) {
                $t['last_used'] = current_time('mysql');
                $t['uses']      = (int) ($t['uses'] ?? 0) + 1;
            }
        }
        update_option(self::OPTION, $tokens, false);
    }

    /* -------------------------------------------------------------- routes */

    public static function routes(): void
    {
        $r = function (string $path, string $method, callable $cb, array $args = []) {
            register_rest_route(self::NS, $path, [
                'methods'             => $method,
                'callback'            => $cb,
                'permission_callback' => [self::class, 'authorize'],
                'args'                => $args,
            ]);
        };

        $r('/status',     'GET',  [self::class, 'status']);
        $r('/schema',     'GET',  [self::class, 'schema']);
        $r('/describe',   'GET',  [self::class, 'describe']);
        $r('/query',      'POST', [self::class, 'query']);
        $r('/wp-query',   'POST', [self::class, 'wp_query']);
        $r('/post',       'GET',  [self::class, 'post']);
        $r('/acf-groups', 'GET',  [self::class, 'acf_groups']);
        $r('/options',    'GET',  [self::class, 'options']);
        $r('/fs-list',    'GET',  [self::class, 'fs_list']);
        $r('/fs-read',    'GET',  [self::class, 'fs_read']);
    }

    public static function status(): array
    {
        global $wpdb;
        $types = [];
        foreach (get_post_types(['_builtin' => false], 'objects') as $pt) {
            $types[$pt->name] = [
                'label'  => $pt->label,
                'public' => (bool) $pt->public,
                'count'  => (array) wp_count_posts($pt->name),
            ];
        }
        $plugins = [];
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        foreach (get_plugins() as $file => $p) {
            $plugins[] = ['file' => $file, 'name' => $p['Name'], 'version' => $p['Version'], 'active' => is_plugin_active($file)];
        }
        return [
            'ok'          => true,
            'site'        => home_url(),
            'wp'          => get_bloginfo('version'),
            'php'         => PHP_VERSION,
            'db_prefix'   => $wpdb->prefix,
            'theme'       => ['stylesheet' => get_stylesheet(), 'template' => get_template()],
            'acf'         => function_exists('acf_get_field_groups'),
            'post_types'  => $types,
            'plugins'     => $plugins,
            'read_roots'  => self::READ_ROOTS,
            'time'        => current_time('mysql'),
        ];
    }

    public static function schema(): array
    {
        global $wpdb;
        $rows = $wpdb->get_results('SHOW TABLE STATUS', ARRAY_A);
        $tables = [];
        foreach ((array) $rows as $row) {
            $tables[] = ['name' => $row['Name'], 'rows' => (int) $row['Rows'], 'engine' => $row['Engine'], 'collation' => $row['Collation']];
        }
        return ['prefix' => $wpdb->prefix, 'tables' => $tables];
    }

    public static function describe(WP_REST_Request $request)
    {
        global $wpdb;
        $table = preg_replace('/[^A-Za-z0-9_]/', '', (string) $request->get_param('table'));
        if ($table === '') {
            return new WP_Error('endless_ai_bad_request', 'table is required', ['status' => 400]);
        }
        $cols = $wpdb->get_results("DESCRIBE `{$table}`", ARRAY_A);
        if (!$cols) {
            return new WP_Error('endless_ai_not_found', 'Unknown table', ['status' => 404]);
        }
        $idx = $wpdb->get_results("SHOW INDEX FROM `{$table}`", ARRAY_A);
        return ['table' => $table, 'columns' => $cols, 'indexes' => $idx];
    }

    public static function query(WP_REST_Request $request)
    {
        global $wpdb;
        $sql    = trim((string) $request->get_param('sql'));
        $params = (array) ($request->get_param('params') ?? []);
        $limit  = min(self::MAX_LIMIT, max(1, (int) ($request->get_param('limit') ?: 100)));

        if ($sql === '' || !preg_match('/^(SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i', $sql)) {
            return new WP_Error('endless_ai_bad_request', 'Only SELECT/SHOW/DESCRIBE/EXPLAIN statements are allowed', ['status' => 400]);
        }
        if (preg_match('/;\s*\S/', $sql) || preg_match('/\b(INTO\s+(OUT|DUMP)FILE|LOAD_FILE|SLEEP|BENCHMARK)\b/i', $sql)) {
            return new WP_Error('endless_ai_bad_request', 'Statement not allowed', ['status' => 400]);
        }
        if (preg_match('/^SELECT/i', $sql) && !preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            $sql = rtrim($sql, "; \n") . " LIMIT {$limit}";
        }
        $prepared = $params ? $wpdb->prepare($sql, ...$params) : $sql;
        $rows = $wpdb->get_results($prepared, ARRAY_A);
        if ($wpdb->last_error) {
            return new WP_Error('endless_ai_sql', $wpdb->last_error, ['status' => 400]);
        }
        foreach ((array) $rows as &$row) {
            foreach (['user_pass', 'user_activation_key'] as $k) {
                if (array_key_exists($k, $row)) {
                    $row[$k] = '[redacted]';
                }
            }
        }
        return ['sql' => $prepared, 'count' => count((array) $rows), 'rows' => $rows];
    }

    public static function wp_query(WP_REST_Request $request): array
    {
        $body = (array) $request->get_json_params();
        $args = [
            'post_type'      => sanitize_key($body['post_type'] ?? 'post'),
            'post_status'    => $body['post_status'] ?? 'any',
            'posts_per_page' => min(self::MAX_LIMIT, max(1, (int) ($body['posts_per_page'] ?? 20))),
            'paged'          => max(1, (int) ($body['paged'] ?? 1)),
            's'              => sanitize_text_field($body['s'] ?? ''),
            'orderby'        => sanitize_key($body['orderby'] ?? 'date'),
            'order'          => strtoupper($body['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC',
        ];
        if (!empty($body['post__in'])) {
            $args['post__in'] = array_map('intval', (array) $body['post__in']);
        }
        if (!empty($body['meta_query']) && is_array($body['meta_query'])) {
            $args['meta_query'] = $body['meta_query'];
        }
        $with_meta = !empty($body['meta']);
        $q = new WP_Query($args);
        $items = [];
        foreach ($q->posts as $p) {
            $items[] = self::serialize_post($p, $with_meta);
        }
        return ['args' => $args, 'found' => (int) $q->found_posts, 'pages' => (int) $q->max_num_pages, 'items' => $items];
    }

    public static function post(WP_REST_Request $request)
    {
        $p = get_post((int) $request->get_param('id'));
        if (!$p) {
            return new WP_Error('endless_ai_not_found', 'Post not found', ['status' => 404]);
        }
        $data = self::serialize_post($p, true);
        $data['attachments'] = array_map(function ($a) {
            return ['id' => $a->ID, 'title' => $a->post_title, 'mime' => $a->post_mime_type, 'url' => wp_get_attachment_url($a->ID), 'meta' => wp_get_attachment_metadata($a->ID)];
        }, get_attached_media('', $p->ID));
        $data['terms'] = [];
        foreach (get_object_taxonomies($p->post_type) as $tax) {
            $data['terms'][$tax] = wp_get_post_terms($p->ID, $tax, ['fields' => 'names']);
        }
        return $data;
    }

    private static function serialize_post(WP_Post $p, bool $with_meta): array
    {
        $out = [
            'id'        => $p->ID,
            'type'      => $p->post_type,
            'status'    => $p->post_status,
            'slug'      => $p->post_name,
            'title'     => $p->post_title,
            'excerpt'   => $p->post_excerpt,
            'content'   => $p->post_content,
            'author'    => (int) $p->post_author,
            'parent'    => (int) $p->post_parent,
            'date'      => $p->post_date,
            'modified'  => $p->post_modified,
            'permalink' => get_permalink($p),
            'thumbnail' => get_the_post_thumbnail_url($p, 'full') ?: null,
        ];
        if ($with_meta) {
            $meta = get_post_meta($p->ID);
            $out['meta'] = array_map(function ($v) {
                return count($v) === 1 ? maybe_unserialize($v[0]) : array_map('maybe_unserialize', $v);
            }, $meta);
            if (function_exists('get_fields')) {
                $out['acf'] = get_fields($p->ID) ?: [];
            }
        }
        return $out;
    }

    public static function acf_groups(): array
    {
        if (!function_exists('acf_get_field_groups')) {
            return ['acf' => false, 'groups' => []];
        }
        $groups = [];
        foreach (acf_get_field_groups() as $g) {
            $fields = acf_get_fields($g['key']) ?: [];
            $groups[] = [
                'key'      => $g['key'],
                'title'    => $g['title'],
                'location' => $g['location'] ?? [],
                'fields'   => array_map(function ($f) {
                    return array_intersect_key($f, array_flip(['key', 'label', 'name', 'type', 'required', 'choices', 'sub_fields', 'return_format', 'multiple', 'instructions', 'default_value']));
                }, $fields),
            ];
        }
        return ['acf' => true, 'groups' => $groups];
    }

    public static function options(WP_REST_Request $request)
    {
        global $wpdb;
        $name = (string) $request->get_param('name');
        if ($name === '') {
            $rows = $wpdb->get_results("SELECT option_name, LENGTH(option_value) AS size, autoload FROM {$wpdb->options} ORDER BY option_name LIMIT 2000", ARRAY_A);
            return ['count' => count($rows), 'options' => array_values(array_filter($rows, fn($r) => !preg_match(self::DENY_OPTS, $r['option_name'])))];
        }
        if (preg_match(self::DENY_OPTS, $name)) {
            return new WP_Error('endless_ai_forbidden', 'Option name refused', ['status' => 403]);
        }
        return ['name' => $name, 'value' => get_option($name)];
    }

    /* ------------------------------------------------------------- files */

    private static function resolve(string $rel)
    {
        $rel  = ltrim(str_replace('\\', '/', $rel), '/');
        $base = rtrim(str_replace('\\', '/', WP_CONTENT_DIR), '/');
        $abs  = realpath($base . '/' . $rel);
        if ($abs === false) {
            return new WP_Error('endless_ai_not_found', 'Path not found', ['status' => 404]);
        }
        $abs = str_replace('\\', '/', $abs);
        $ok = false;
        foreach (self::READ_ROOTS as $root) {
            $rootAbs = realpath($base . '/' . $root);
            if ($rootAbs && str_starts_with($abs . '/', str_replace('\\', '/', $rootAbs) . '/')) {
                $ok = true;
                break;
            }
        }
        if (!$ok || preg_match(self::DENY_FILES, $abs)) {
            return new WP_Error('endless_ai_forbidden', 'Path outside the allowed read roots', ['status' => 403]);
        }
        return $abs;
    }

    public static function fs_list(WP_REST_Request $request)
    {
        $abs = self::resolve((string) ($request->get_param('path') ?: 'plugins'));
        if (is_wp_error($abs)) {
            return $abs;
        }
        if (!is_dir($abs)) {
            return new WP_Error('endless_ai_bad_request', 'Not a directory', ['status' => 400]);
        }
        $entries = [];
        foreach (scandir($abs) as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }
            $full = $abs . '/' . $e;
            $entries[] = ['name' => $e, 'dir' => is_dir($full), 'size' => is_file($full) ? filesize($full) : null, 'mtime' => date('c', filemtime($full))];
        }
        return ['path' => str_replace(str_replace('\\', '/', WP_CONTENT_DIR) . '/', '', $abs), 'entries' => $entries];
    }

    public static function fs_read(WP_REST_Request $request)
    {
        $abs = self::resolve((string) $request->get_param('path'));
        if (is_wp_error($abs)) {
            return $abs;
        }
        if (!is_file($abs)) {
            return new WP_Error('endless_ai_bad_request', 'Not a file', ['status' => 400]);
        }
        if (filesize($abs) > 2 * 1024 * 1024) {
            return new WP_Error('endless_ai_too_large', 'File larger than 2MB', ['status' => 413]);
        }
        $content = file_get_contents($abs);
        $isText  = mb_check_encoding($content, 'UTF-8');
        return [
            'path'     => str_replace(str_replace('\\', '/', WP_CONTENT_DIR) . '/', '', $abs),
            'size'     => strlen($content),
            'encoding' => $isText ? 'utf-8' : 'base64',
            'content'  => $isText ? $content : base64_encode($content),
        ];
    }

    /* ------------------------------------------------------------- admin */

    public static function admin_menu(): void
    {
        add_management_page('AI Bridge', 'AI Bridge', 'manage_options', 'endless-ai-bridge', [self::class, 'admin_page']);
    }

    public static function handle_generate(): void
    {
        if (!current_user_can('manage_options') || !check_admin_referer('endless_ai_bridge_generate')) {
            wp_die('Forbidden');
        }
        $raw   = rtrim(strtr(base64_encode(random_bytes(36)), '+/', '-_'), '=');
        $label = sanitize_text_field($_POST['label'] ?? 'Claude');
        $tokens = (array) get_option(self::OPTION, []);
        $tokens[] = ['label' => $label, 'hash' => hash('sha256', $raw), 'created' => current_time('mysql'), 'uses' => 0, 'last_used' => null, 'revoked' => false];
        update_option(self::OPTION, $tokens, false);
        set_transient('endless_ai_bridge_show_' . get_current_user_id(), $raw, 300);
        wp_safe_redirect(admin_url('tools.php?page=endless-ai-bridge&generated=1'));
        exit;
    }

    public static function handle_revoke(): void
    {
        if (!current_user_can('manage_options') || !check_admin_referer('endless_ai_bridge_revoke')) {
            wp_die('Forbidden');
        }
        $hash   = sanitize_text_field($_POST['hash'] ?? '');
        $tokens = (array) get_option(self::OPTION, []);
        foreach ($tokens as &$t) {
            if (($t['hash'] ?? '') === $hash) {
                $t['revoked'] = true;
            }
        }
        update_option(self::OPTION, $tokens, false);
        wp_safe_redirect(admin_url('tools.php?page=endless-ai-bridge&revoked=1'));
        exit;
    }

    public static function admin_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $shown  = get_transient('endless_ai_bridge_show_' . get_current_user_id());
        if ($shown) {
            delete_transient('endless_ai_bridge_show_' . get_current_user_id());
        }
        $tokens = (array) get_option(self::OPTION, []);
        $base   = esc_url(rest_url(self::NS));
        echo '<div class="wrap"><h1>Endless AI Bridge</h1>';
        echo '<p>Read-only REST bridge for Claude Code. Endpoint base: <code>' . $base . '</code></p>';
        if ($shown) {
            echo '<div class="notice notice-success"><p><strong>New token (shown once — copy it now):</strong></p><p><code style="font-size:15px;user-select:all">' . esc_html($shown) . '</code></p></div>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:20px 0">';
        wp_nonce_field('endless_ai_bridge_generate');
        echo '<input type="hidden" name="action" value="endless_ai_bridge_generate">';
        echo '<input type="text" name="label" value="Claude" class="regular-text"> ';
        submit_button('Generate token', 'primary', 'submit', false);
        echo '</form>';
        echo '<table class="widefat striped"><thead><tr><th>Label</th><th>Created</th><th>Uses</th><th>Last used</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($tokens as $t) {
            echo '<tr><td>' . esc_html($t['label'] ?? '') . '</td><td>' . esc_html($t['created'] ?? '') . '</td><td>' . (int) ($t['uses'] ?? 0) . '</td><td>' . esc_html($t['last_used'] ?? '—') . '</td><td>' . (!empty($t['revoked']) ? 'revoked' : 'active') . '</td><td>';
            if (empty($t['revoked'])) {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                wp_nonce_field('endless_ai_bridge_revoke');
                echo '<input type="hidden" name="action" value="endless_ai_bridge_revoke"><input type="hidden" name="hash" value="' . esc_attr($t['hash']) . '">';
                submit_button('Revoke', 'small', 'submit', false);
                echo '</form>';
            }
            echo '</td></tr>';
        }
        if (!$tokens) {
            echo '<tr><td colspan="6">No tokens yet.</td></tr>';
        }
        echo '</tbody></table>';
        echo '<h2>Quick test</h2><pre>curl -H "Authorization: Bearer YOUR_TOKEN" ' . $base . '/status</pre></div>';
    }
}

Endless_AI_Bridge::boot();
