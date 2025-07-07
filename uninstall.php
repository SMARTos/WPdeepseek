<?php
/**
 * Файл удаления плагина DeepSeek Content Generator
 * Выполняется при удалении плагина из WordPress
 */

// Если WordPress не загружен, выходим
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Удаляем таблицу с промтами
global $wpdb;
$table_name = $wpdb->prefix . 'deepseek_prompts';
$wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS {$table_name}"));

// Удаляем опции плагина
delete_option('deepseek_api_key');
delete_option('deepseek_model');
delete_option('deepseek_max_tokens');
delete_option('deepseek_temperature');
delete_option('deepseek_remove_markdown');

// Очищаем кэш (если используется)
wp_cache_flush(); 