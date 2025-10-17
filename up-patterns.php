<?php
/*
Plugin Name: Up Patterns
Description: Importer et centraliser les patterns, templates et parts d'un thème actif avec leurs assets associés.
Version: 0.1.0
Author: GEHIN Nicolas
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('UP_Patterns_Plugin')) {
    class UP_Patterns_Plugin {
        private const OPTION_KEY = 'up_patterns_settings';
        private const MANIFEST_FILE = 'manifest.json';

        private static $instance = null;
        private $notices = [];
        private $settings_cache = null;

        public static function instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public static function activate() {
            $instance = self::instance();
            $instance->ensure_base_directories();
            $manifest = $instance->load_manifest();
            $instance->save_manifest($manifest);
        }

        private function __construct() {
            add_action('admin_menu', [$this, 'register_admin_pages']);
            add_action('admin_init', [$this, 'handle_actions']);
            add_action('admin_notices', [$this, 'render_admin_notices']);
        }

        public function register_admin_pages() {
            add_menu_page(
                __('UP Patterns', 'up-patterns'),
                __('UP Patterns', 'up-patterns'),
                'manage_options',
                'up-patterns',
                [$this, 'render_import_page'],
                'dashicons-layout',
                58
            );

            add_submenu_page(
                'up-patterns',
                __('Configuration', 'up-patterns'),
                __('Configuration', 'up-patterns'),
                'manage_options',
                'up-patterns-settings',
                [$this, 'render_settings_page']
            );
        }

        public function handle_actions() {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (isset($_POST['up_patterns_import'])) {
                $this->handle_import_action();
            }

            if (isset($_POST['up_patterns_save_settings'])) {
                $this->handle_settings_save();
            }
        }

        private function handle_import_action() {
            check_admin_referer('up_patterns_import');

            $type = isset($_POST['up_patterns_type']) ? sanitize_text_field($_POST['up_patterns_type']) : '';
            $relative_path = isset($_POST['up_patterns_rel_path']) ? sanitize_text_field(wp_unslash($_POST['up_patterns_rel_path'])) : '';

            if (empty($type) || empty($relative_path)) {
                $this->add_notice(__('Requête invalide.', 'up-patterns'), 'error');
                return;
            }

            $result = $this->import_item($type, $relative_path);
            if (is_wp_error($result)) {
                $this->add_notice($result->get_error_message(), 'error');
            } else {
                $this->add_notice(sprintf(__('Le fichier "%1$s" a été importé avec succès.', 'up-patterns'), esc_html($relative_path)), 'success');
            }
        }

        private function handle_settings_save() {
            check_admin_referer('up_patterns_save_settings');

            $settings = $this->get_settings();

            $settings['patterns_dir'] = isset($_POST['patterns_dir']) ? sanitize_text_field(wp_unslash($_POST['patterns_dir'])) : $settings['patterns_dir'];
            $settings['templates_dir'] = isset($_POST['templates_dir']) ? sanitize_text_field(wp_unslash($_POST['templates_dir'])) : $settings['templates_dir'];
            $settings['parts_dir'] = isset($_POST['parts_dir']) ? sanitize_text_field(wp_unslash($_POST['parts_dir'])) : $settings['parts_dir'];

            $settings['assets']['css'] = $this->sanitize_multiline_input('assets_css');
            $settings['assets']['js'] = $this->sanitize_multiline_input('assets_js');
            $settings['assets']['gsap'] = $this->sanitize_multiline_input('assets_gsap');
            $settings['assets']['preview'] = $this->sanitize_multiline_input('assets_preview');

            update_option(self::OPTION_KEY, $settings);
            $this->settings_cache = $settings;

            $this->add_notice(__('Configuration enregistrée.', 'up-patterns'), 'success');
        }

        private function sanitize_multiline_input($field) {
            $raw = isset($_POST[$field]) ? wp_unslash($_POST[$field]) : '';
            $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)));
            return array_values($lines);
        }

        public function render_import_page() {
            if (!current_user_can('manage_options')) {
                return;
            }

            $settings = $this->get_settings();
            $items = [
                'patterns' => $this->scan_theme_items('patterns', $settings['patterns_dir']),
                'templates' => $this->scan_theme_items('templates', $settings['templates_dir']),
                'parts' => $this->scan_theme_items('parts', $settings['parts_dir']),
            ];

            echo '<div class="wrap up-patterns-admin">';
            echo '<h1>' . esc_html__('Importer les patterns du thème', 'up-patterns') . '</h1>';
            echo '<p>' . esc_html__('Sélectionnez un élément pour l’importer dans le plugin. Chaque import crée un dossier dédié dans "ressources" et met à jour le manifest.', 'up-patterns') . '</p>';

            foreach ($items as $type => $entries) {
                $this->render_items_section($type, $entries);
            }

            echo '</div>';
        }

        public function render_settings_page() {
            if (!current_user_can('manage_options')) {
                return;
            }

            $settings = $this->get_settings();

            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Configuration UP Patterns', 'up-patterns') . '</h1>';
            echo '<p>' . esc_html__('Définissez les répertoires sources dans le thème pour les patterns, templates, parts et assets associés. Les valeurs sont relatives au répertoire du thème actif.', 'up-patterns') . '</p>';

            echo '<form method="post">';
            wp_nonce_field('up_patterns_save_settings');
            echo '<input type="hidden" name="up_patterns_save_settings" value="1" />';

            echo '<table class="form-table" role="presentation">';
            $this->render_text_input_row('patterns_dir', __('Répertoire des patterns', 'up-patterns'), $settings['patterns_dir'], 'patterns/');
            $this->render_text_input_row('templates_dir', __('Répertoire des templates', 'up-patterns'), $settings['templates_dir'], 'templates/');
            $this->render_text_input_row('parts_dir', __('Répertoire des parts', 'up-patterns'), $settings['parts_dir'], 'parts/');

            $this->render_textarea_row('assets_css', __('Répertoires CSS / SCSS', 'up-patterns'), $settings['assets']['css'], "assets/css/block\nassets/scss/block");
            $this->render_textarea_row('assets_js', __('Répertoires JS', 'up-patterns'), $settings['assets']['js'], 'assets/js/block');
            $this->render_textarea_row('assets_gsap', __('Répertoires GSAP', 'up-patterns'), $settings['assets']['gsap'], 'assets/js/gsap');
            $this->render_textarea_row('assets_preview', __('Répertoires des aperçus (images)', 'up-patterns'), $settings['assets']['preview'], "assets/images/block\nassets/images\nimages");
            echo '</table>';

            submit_button(__('Enregistrer la configuration', 'up-patterns'));
            echo '</form>';

            echo '<hr />';
            echo '<h2>' . esc_html__('Structure des imports', 'up-patterns') . '</h2>';
            echo '<p>' . esc_html__('Chaque import crée un dossier dans "ressources/{type}/{slug}" avec le fichier original et, si présents, les assets classés dans "assets/css", "assets/js" et "assets/gsap". Le fichier manifest.json à la racine du plugin liste l’ensemble des éléments importés.', 'up-patterns') . '</p>';

            echo '</div>';
        }

        private function render_text_input_row($name, $label, $value, $placeholder = '') {
            echo '<tr>'; echo '<th scope="row"><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
            echo '<td><input type="text" class="regular-text" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" /></td>';
            echo '</tr>';
        }

        private function render_textarea_row($name, $label, $values, $placeholder = '') {
            $value = implode("\n", $values);
            echo '<tr>'; echo '<th scope="row"><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
            echo '<td><textarea class="large-text code" rows="4" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '">' . esc_textarea($value) . '</textarea></td>';
            echo '</tr>';
        }

        private function render_items_section($type, $items) {
            $titles = [
                'patterns' => __('Patterns du thème', 'up-patterns'),
                'templates' => __('Templates du thème', 'up-patterns'),
                'parts' => __('Template parts du thème', 'up-patterns'),
            ];

            $title = isset($titles[$type]) ? $titles[$type] : ucfirst($type);
            echo '<h2>' . esc_html($title) . '</h2>';

            if (empty($items)) {
                echo '<p>' . esc_html__('Aucun fichier détecté.', 'up-patterns') . '</p>';
                return;
            }

            echo '<table class="widefat fixed striped">';
            echo '<thead><tr><th>' . esc_html__('Nom', 'up-patterns') . '</th><th>' . esc_html__('Chemin', 'up-patterns') . '</th><th>' . esc_html__('État', 'up-patterns') . '</th><th>' . esc_html__('Action', 'up-patterns') . '</th></tr></thead>';
            echo '<tbody>';

            foreach ($items as $item) {
                $is_imported = $this->is_item_imported($type, $item['slug']);
                echo '<tr>';
                echo '<td><strong>' . esc_html($item['name']) . '</strong></td>';
                echo '<td><code>' . esc_html($item['relative_path']) . '</code></td>';
                echo '<td>' . ($is_imported ? esc_html__('Déjà importé', 'up-patterns') : esc_html__('Disponible', 'up-patterns')) . '</td>';
                echo '<td>';
                echo '<form method="post" style="display:inline;">';
                wp_nonce_field('up_patterns_import');
                echo '<input type="hidden" name="up_patterns_import" value="1" />';
                echo '<input type="hidden" name="up_patterns_type" value="' . esc_attr($type) . '" />';
                echo '<input type="hidden" name="up_patterns_rel_path" value="' . esc_attr($item['relative_path']) . '" />';
                $disabled = $is_imported ? 'disabled' : '';
                echo '<button type="submit" class="button button-secondary" ' . $disabled . '>' . esc_html__('Importer dans le plugin', 'up-patterns') . '</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        private function scan_theme_items($type, $relative_directory) {
            $theme_dir = trailingslashit(get_stylesheet_directory());
            $relative_directory = trim($relative_directory, '/');

            if (empty($relative_directory)) {
                return [];
            }

            $base_dir = trailingslashit($theme_dir . $relative_directory);
            if (!is_dir($base_dir)) {
                return [];
            }

            $items = [];
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file_info) {
                if (!$file_info->isFile()) {
                    continue;
                }

                $extension = strtolower($file_info->getExtension());
                if (!in_array($extension, ['php', 'html'], true)) {
                    continue;
                }

                $absolute_path = wp_normalize_path($file_info->getPathname());
                $relative_path = str_replace(wp_normalize_path($theme_dir), '', $absolute_path);
                $slug = $this->determine_slug($relative_path);

                $items[] = [
                    'name' => $file_info->getBasename(),
                    'relative_path' => ltrim($relative_path, '/'),
                    'slug' => $slug,
                ];
            }

            usort($items, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            return $items;
        }

        private function determine_slug($relative_path) {
            $filename = pathinfo($relative_path, PATHINFO_FILENAME);
            $slug = sanitize_title($filename);
            return $slug ?: md5($relative_path);
        }

        private function import_item($type, $relative_path) {
            $allowed_types = ['patterns', 'templates', 'parts'];
            if (!in_array($type, $allowed_types, true)) {
                return new WP_Error('up_patterns_invalid_type', __('Type non supporté.', 'up-patterns'));
            }

            $theme_dir = trailingslashit(get_stylesheet_directory());
            $source = wp_normalize_path($theme_dir . ltrim($relative_path, '/'));

            if (!file_exists($source)) {
                return new WP_Error('up_patterns_missing_source', __('Le fichier source est introuvable.', 'up-patterns'));
            }

            $slug = $this->determine_slug($relative_path);
            $destination_dir = trailingslashit($this->get_resources_dir()) . $type . '/' . $slug;

            $this->ensure_directory($destination_dir);

            $destination_file = trailingslashit($destination_dir) . basename($source);
            if (!copy($source, $destination_file)) {
                return new WP_Error('up_patterns_copy_failed', __('Impossible de copier le fichier principal.', 'up-patterns'));
            }

            $assets = $this->copy_related_assets($slug, $type);

            $this->update_manifest($type, $slug, [
                'name' => $this->generate_display_name($slug, basename($source)),
                'source' => ltrim(str_replace(wp_normalize_path($theme_dir), '', $source), '/'),
                'destination' => ltrim(str_replace(wp_normalize_path(plugin_dir_path(__FILE__)), '', $destination_file), '/'),
                'assets' => $assets,
            ]);

            return true;
        }

        private function copy_related_assets($slug, $type) {
            $settings = $this->get_settings();
            $theme_dir = trailingslashit(get_stylesheet_directory());
            $plugin_dir = trailingslashit(plugin_dir_path(__FILE__));
            $base_destination = trailingslashit($this->get_resources_dir()) . $type . '/' . $slug . '/assets/';

            $map = [
                'css' => ['paths' => $settings['assets']['css'], 'extensions' => ['css', 'scss']],
                'js' => ['paths' => $settings['assets']['js'], 'extensions' => ['js']],
                'gsap' => ['paths' => $settings['assets']['gsap'], 'extensions' => ['js']],
            ];

            $copied = [
                'css' => [],
                'js' => [],
                'gsap' => [],
                'preview' => null,
            ];

            foreach ($map as $asset_type => $config) {
                $destination_subdir = $base_destination . $asset_type . '/';
                $this->ensure_directory($destination_subdir);

                foreach ($config['paths'] as $relative_dir) {
                    $relative_dir = trim($relative_dir, '/');
                    if ('' === $relative_dir) {
                        continue;
                    }

                    $source_dir = trailingslashit($theme_dir . $relative_dir);
                    if (!is_dir($source_dir)) {
                        continue;
                    }

                    foreach ($config['extensions'] as $extension) {
                        $candidate = $source_dir . $slug . '.' . $extension;
                        if (!file_exists($candidate)) {
                            continue;
                        }

                        $destination_path = $destination_subdir . basename($candidate);
                        if (!copy($candidate, $destination_path)) {
                            continue;
                        }

                        $relative_destination = ltrim(str_replace($plugin_dir, '', $destination_path), '/');
                        $copied[$asset_type][] = $relative_destination;
                    }
                }
            }

            $preview_paths = $settings['assets']['preview'] ?? [];
            $preview_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            foreach ($preview_paths as $relative_dir) {
                $relative_dir = trim($relative_dir, '/');
                if ('' === $relative_dir) {
                    continue;
                }

                $source_dir = trailingslashit($theme_dir . $relative_dir);
                if (!is_dir($source_dir)) {
                    continue;
                }

                foreach ($preview_extensions as $extension) {
                    $candidate = $source_dir . $slug . '.' . $extension;
                    if (!file_exists($candidate)) {
                        continue;
                    }

                    $destination_subdir = $base_destination . 'preview/';
                    $this->ensure_directory($destination_subdir);
                    $destination_path = $destination_subdir . basename($candidate);
                    if (!copy($candidate, $destination_path)) {
                        continue;
                    }

                    $copied['preview'] = ltrim(str_replace($plugin_dir, '', $destination_path), '/');
                    break 2;
                }
            }

            return $copied;
        }

        private function update_manifest($type, $slug, $data) {
            $manifest = $this->load_manifest();

            $entry = $this->build_manifest_entry($type, $slug, $data);

            if (!isset($manifest[$type]) || !is_array($manifest[$type])) {
                $manifest[$type] = [];
            }

            // Replace if existing
            $manifest[$type] = array_values(array_filter($manifest[$type], function ($item) use ($slug) {
                return isset($item['slug']) && $item['slug'] !== $slug;
            }));
            $manifest[$type][] = $entry;

            $manifest['generated_at'] = current_time('c');
            $this->save_manifest($manifest);
        }

        private function load_manifest() {
            $path = $this->get_manifest_path();
            if (!file_exists($path)) {
                return $this->default_manifest_structure();
            }

            $contents = file_get_contents($path);
            $data = json_decode($contents, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                return $this->default_manifest_structure();
            }

            return $data;
        }

        private function save_manifest($manifest) {
            $path = $this->get_manifest_path();
            $this->ensure_base_directories();
            $encoded = wp_json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($path, $encoded);
        }

        private function get_manifest_path() {
            return trailingslashit(plugin_dir_path(__FILE__)) . self::MANIFEST_FILE;
        }

        private function get_resources_dir() {
            return trailingslashit(plugin_dir_path(__FILE__)) . 'ressources';
        }

        private function ensure_base_directories() {
            $this->ensure_directory($this->get_resources_dir());
            $this->ensure_directory(trailingslashit($this->get_resources_dir()) . 'patterns');
            $this->ensure_directory(trailingslashit($this->get_resources_dir()) . 'templates');
            $this->ensure_directory(trailingslashit($this->get_resources_dir()) . 'parts');
        }

        private function ensure_directory($path) {
            if (empty($path)) {
                return;
            }

            if (!is_dir($path)) {
                wp_mkdir_p($path);
            }
        }

        private function is_item_imported($type, $slug) {
            $path = trailingslashit($this->get_resources_dir()) . $type . '/' . $slug;
            return is_dir($path) && count(glob($path . '/*')) > 0;
        }

        private function get_settings() {
            if (null !== $this->settings_cache) {
                return $this->settings_cache;
            }

            $defaults = [
                'patterns_dir' => 'patterns',
                'templates_dir' => 'templates',
                'parts_dir' => 'parts',
                'assets' => [
                    'css' => ['assets/css/block', 'assets/scss/block'],
                    'js' => ['assets/js/block'],
                    'gsap' => ['assets/js/gsap'],
                    'preview' => ['assets/images/block', 'assets/images', 'images'],
                ],
            ];

            $saved = get_option(self::OPTION_KEY, []);
            if (!is_array($saved)) {
                $saved = [];
            }

            $settings = wp_parse_args($saved, $defaults);

            foreach (['css', 'js', 'gsap', 'preview'] as $key) {
                if (!isset($settings['assets'][$key]) || !is_array($settings['assets'][$key])) {
                    $settings['assets'][$key] = $defaults['assets'][$key];
                }
            }

            $this->settings_cache = $settings;
            return $settings;
        }

        private function build_manifest_entry($type, $slug, $data) {
            $settings = $this->get_settings();

            $files = [];
            if ('patterns' === $type) {
                $files['pattern'] = $data['destination'];
            } elseif ('templates' === $type) {
                $files['template'] = $data['destination'];
            } elseif ('parts' === $type) {
                $files['part'] = $data['destination'];
            }

            if (!empty($data['assets']['css'])) {
                $files['style'] = $data['assets']['css'][0];
            }
            if (!empty($data['assets']['js'])) {
                $files['script'] = $data['assets']['js'][0];
            }
            if (!empty($data['assets']['gsap'])) {
                $files['gsap'] = $data['assets']['gsap'][0];
            }

            $install = [];
            if ('patterns' === $type) {
                $install['pattern'] = $this->normalize_install_path($settings['patterns_dir']);
            } elseif ('templates' === $type) {
                $install['template'] = $this->normalize_install_path($settings['templates_dir']);
            } elseif ('parts' === $type) {
                $install['part'] = $this->normalize_install_path($settings['parts_dir']);
            }

            if (!empty($settings['assets']['css'])) {
                $install['style'] = $this->normalize_install_path($settings['assets']['css'][0]);
            }
            if (!empty($settings['assets']['js'])) {
                $install['script'] = $this->normalize_install_path($settings['assets']['js'][0]);
            }
            if (!empty($settings['assets']['gsap'])) {
                $install['gsap'] = $this->normalize_install_path($settings['assets']['gsap'][0]);
            }

            $entry = [
                'slug' => $slug,
                'name' => $data['name'],
                'type' => $type,
                'files' => $files,
                'install' => $install,
            ];

            if (!empty($data['assets']['preview'])) {
                $entry['preview'] = $data['assets']['preview'];
            }

            return $entry;
        }

        private function normalize_install_path($path) {
            $path = trim((string) $path);
            return trim($path, '/');
        }

        private function default_manifest_structure() {
            return [
                'generated_at' => current_time('c'),
                'patterns' => [],
                'templates' => [],
                'parts' => [],
            ];
        }

        private function generate_display_name($slug, $filename) {
            $base = pathinfo($filename, PATHINFO_FILENAME);
            $candidate = sanitize_text_field(str_replace(['-', '_'], ' ', $slug));
            if ('' === trim($candidate)) {
                $candidate = $base;
            }
            return ucwords(trim(str_replace(['-', '_'], ' ', $candidate)));
        }

        private function add_notice($message, $type = 'success') {
            $this->notices[] = [
                'message' => $message,
                'type' => $type,
            ];
        }

        public function render_admin_notices() {
            if (empty($this->notices)) {
                return;
            }

            foreach ($this->notices as $notice) {
                $class = 'success' === $notice['type'] ? 'notice notice-success is-dismissible' : 'notice notice-error';
                echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($notice['message']) . '</p></div>';
            }
        }
    }
}

if (!function_exists('wp_mkdir_p')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}

UP_Patterns_Plugin::instance();
register_activation_hook(__FILE__, ['UP_Patterns_Plugin', 'activate']);
