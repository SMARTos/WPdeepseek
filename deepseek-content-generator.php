<?php
/**
 * Plugin Name: DeepSeek Content Generator
 * Plugin URI: https://github.com/your-username/deepseek-content-generator
 * Description: Генератор контента с использованием DeepSeek API. Создавайте контент с помощью ИИ и сохраняйте промты в черновики.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: deepseek-content-generator
 */

// Предотвращаем прямой доступ к файлу
if (!defined('ABSPATH')) {
    exit;
}

// Определяем константы плагина
define('DEEPSEEK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DEEPSEEK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DEEPSEEK_PLUGIN_VERSION', '1.0.0');

// Основной класс плагина
class DeepSeekContentGenerator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_deepseek_generate_content', array($this, 'generate_content'));
        add_action('wp_ajax_deepseek_create_draft', array($this, 'create_draft'));
        add_action('wp_ajax_deepseek_save_prompt', array($this, 'save_prompt'));
        add_action('wp_ajax_deepseek_get_prompts', array($this, 'get_prompts'));
        add_action('wp_ajax_deepseek_delete_prompt', array($this, 'delete_prompt'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Инициализация плагина
    }
    
    public function activate() {
        // Создаем таблицу для сохранения промтов
        $this->create_prompts_table();
        
        // Добавляем опции по умолчанию
        add_option('deepseek_api_key', '');
        add_option('deepseek_model', 'deepseek-chat');
        add_option('deepseek_max_tokens', 8000);
        add_option('deepseek_temperature', 1.0);
        add_option('deepseek_remove_markdown', true);
    }
    
    public function deactivate() {
        // Очистка при деактивации (опционально)
        // Раскомментируйте следующие строки, если хотите удалить данные при деактивации
        // global $wpdb;
        // $table_name = $wpdb->prefix . 'deepseek_prompts';
        // $wpdb->query("DROP TABLE IF EXISTS $table_name");
        // delete_option('deepseek_api_key');
        // delete_option('deepseek_model');
        // delete_option('deepseek_max_tokens');
        
        // Удаляем API ключ при деактивации для безопасности
        delete_option('deepseek_api_key');
    }
    
    private function create_prompts_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'deepseek_prompts';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            prompt text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'DeepSeek Generator',
            'DeepSeek Generator',
            'manage_options',
            'deepseek-generator',
            array($this, 'admin_page'),
            'dashicons-edit',
            30
        );
        
        add_submenu_page(
            'deepseek-generator',
            'Настройки',
            'Настройки',
            'manage_options',
            'deepseek-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'deepseek-generator') !== false || strpos($hook, 'deepseek-settings') !== false) {
            wp_enqueue_script('deepseek-admin', DEEPSEEK_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), DEEPSEEK_PLUGIN_VERSION, true);
            wp_enqueue_style('deepseek-admin', DEEPSEEK_PLUGIN_URL . 'assets/css/admin.css', array(), DEEPSEEK_PLUGIN_VERSION);
            
            wp_localize_script('deepseek-admin', 'deepseek_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('deepseek_nonce')
            ));
        }
    }
    
    public function admin_page() {
        include DEEPSEEK_PLUGIN_PATH . 'templates/admin-page.php';
    }
    
    public function settings_page() {
        include DEEPSEEK_PLUGIN_PATH . 'templates/settings-page.php';
    }
    
    public function generate_content() {
        check_ajax_referer('deepseek_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Недостаточно прав');
        }
        
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field(wp_unslash($_POST['prompt'])) : '';
        $api_key = get_option('deepseek_api_key');
        $model = get_option('deepseek_model', 'deepseek-chat');
        $max_tokens = get_option('deepseek_max_tokens', 8000);
        $temperature = get_option('deepseek_temperature', 1.0);
        $remove_markdown = get_option('deepseek_remove_markdown', true);
        
        if (empty($api_key)) {
            wp_send_json_error('API ключ не настроен');
        }
        
        if (empty($prompt)) {
            wp_send_json_error('Промт не может быть пустым');
        }
        
        $response = $this->call_deepseek_api($prompt, $api_key, $model, $max_tokens, $temperature, $remove_markdown);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        wp_send_json_success(array(
            'content' => $response
        ));
    }
    
    private function call_deepseek_api($prompt, $api_key, $model, $max_tokens, $temperature, $remove_markdown = true) {
        $url = 'https://api.deepseek.com/v1/chat/completions';
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => intval($max_tokens),
            'temperature' => floatval($temperature)
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($body),
            'timeout' => 60
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return new WP_Error('api_error', $data['error']['message']);
        }
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
            
            // Удаляем Markdown разметку, если включена опция
            if ($remove_markdown) {
                $content = $this->remove_markdown($content);
            }
            
            return $content;
        }
        
        return new WP_Error('api_error', 'Неожиданный ответ от API');
    }
    
    public function create_draft() {
        check_ajax_referer('deepseek_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Недостаточно прав');
        }
        
        $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
        
        if (empty($content)) {
            wp_send_json_error('Контент не может быть пустым');
        }
        
        // Извлекаем заголовок из первой строки контента
        $lines = explode("\n", $content);
        $title = '';
        
        // Ищем первую непустую строку для заголовка
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && strlen($line) > 10) { // Минимум 10 символов для заголовка
                $title = $line;
                break;
            }
        }
        
        // Если не нашли подходящую строку, используем первые 50 символов
        if (empty($title)) {
            $title = substr(trim($content), 0, 50);
            if (strlen($title) < 10) {
                $title = 'Новая статья';
            }
        }
        
        // Ограничиваем длину заголовка
        if (strlen($title) > 100) {
            $title = substr($title, 0, 97) . '...';
        }
        
        // Создаем черновик записи
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'draft',
            'post_type' => 'post'
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error('Ошибка создания записи: ' . $post_id->get_error_message());
        }
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, '')
        ));
    }
    
    /**
     * Удаляет Markdown разметку из текста
     */
    private function remove_markdown($text) {
        // Удаляем заголовки (# ## ### #### ##### ######)
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);
        
        // Удаляем жирный текст (**текст** или __текст__)
        $text = preg_replace('/\*\*(.*?)\*\*/s', '$1', $text);
        $text = preg_replace('/__(.*?)__/s', '$1', $text);
        
        // Удаляем курсив (*текст* или _текст_)
        $text = preg_replace('/\*(.*?)\*/s', '$1', $text);
        $text = preg_replace('/_(.*?)_/s', '$1', $text);
        
        // Удаляем зачеркнутый текст (~~текст~~)
        $text = preg_replace('/~~(.*?)~~/s', '$1', $text);
        
        // Удаляем код в обратных кавычках (`код`)
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        
        // Удаляем блоки кода (```код```)
        $text = preg_replace('/```.*?\n(.*?)```/s', '$1', $text);
        
        // Удаляем ссылки [текст](url)
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        
        // Удаляем изображения ![alt](url)
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '', $text);
        
        // Удаляем списки (- * +)
        $text = preg_replace('/^[\s]*[-*+]\s+/m', '', $text);
        
        // Удаляем нумерованные списки (1. 2. 3.)
        $text = preg_replace('/^[\s]*\d+\.\s+/m', '', $text);
        
        // Удаляем цитаты (> текст)
        $text = preg_replace('/^>\s+/m', '', $text);
        
        // Удаляем горизонтальные линии (--- или ***)
        $text = preg_replace('/^[\s]*[-*_]{3,}[\s]*$/m', '', $text);
        
        // Удаляем лишние пустые строки
        $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text);
        
        // Удаляем одиночные символы # в тексте (не в начале строки)
        $text = preg_replace('/\s#\s/', ' ', $text);
        
        // Удаляем одиночные символы * в тексте
        $text = preg_replace('/\s\*\s/', ' ', $text);
        
        // Очищаем начало и конец текста
        $text = trim($text);
        
        return $text;
    }
    
    public function save_prompt() {
        check_ajax_referer('deepseek_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }
        
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field(wp_unslash($_POST['prompt'])) : '';
        
        if (empty($title) || empty($prompt)) {
            wp_send_json_error('Название и промт не могут быть пустыми');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'deepseek_prompts';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'title' => $title,
                'prompt' => $prompt
            ),
            array('%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Ошибка сохранения промта');
        }
        
        wp_send_json_success('Промт успешно сохранен');
    }
    
    public function get_prompts() {
        check_ajax_referer('deepseek_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'deepseek_prompts';
        
        $prompts = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY created_at DESC"),
            ARRAY_A
        );
        
        wp_send_json_success($prompts);
    }
    
    public function delete_prompt() {
        check_ajax_referer('deepseek_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }
        
        $id = isset($_POST['id']) ? intval(wp_unslash($_POST['id'])) : 0;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'deepseek_prompts';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Ошибка удаления промта');
        }
        
        wp_send_json_success('Промт успешно удален');
    }
}

// Инициализируем плагин
new DeepSeekContentGenerator(); 