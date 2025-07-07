<?php
if (!defined('ABSPATH')) {
    exit;
}

// Обработка сохранения настроек
if (isset($_POST['submit'])) {
    check_admin_referer('deepseek_settings');
    
    $deepseek_api_key = isset($_POST['deepseek_api_key']) ? sanitize_text_field(wp_unslash($_POST['deepseek_api_key'])) : '';
    $deepseek_model = isset($_POST['deepseek_model']) ? sanitize_text_field(wp_unslash($_POST['deepseek_model'])) : 'deepseek-chat';
    $max_tokens = isset($_POST['deepseek_max_tokens']) ? intval(wp_unslash($_POST['deepseek_max_tokens'])) : 8000;
    $temperature = isset($_POST['deepseek_temperature']) ? floatval(wp_unslash($_POST['deepseek_temperature'])) : 1.0;
    $remove_markdown = isset($_POST['deepseek_remove_markdown']) ? true : false;
    
    update_option('deepseek_api_key', $deepseek_api_key);
    update_option('deepseek_model', $deepseek_model);
    update_option('deepseek_max_tokens', $max_tokens);
    update_option('deepseek_temperature', $temperature);
    update_option('deepseek_remove_markdown', $remove_markdown);
    
    echo '<div class="notice notice-success"><p>Настройки успешно сохранены!</p></div>';
}

$deepseek_api_key = get_option('deepseek_api_key', '');
$deepseek_model = get_option('deepseek_model', 'deepseek-chat');
$max_tokens = get_option('deepseek_max_tokens', 8000);
$temperature = get_option('deepseek_temperature', 1.0);
$remove_markdown = get_option('deepseek_remove_markdown', true);
?>

<div class="wrap">
    <h1>Настройки DeepSeek Content Generator</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('deepseek_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="deepseek_api_key">API Ключ DeepSeek</label>
                </th>
                <td>
                    <input type="password" 
                           id="deepseek_api_key" 
                           name="deepseek_api_key" 
                           value="<?php echo esc_attr($deepseek_api_key); ?>" 
                           class="regular-text"
                           placeholder="sk-...">
                    <p class="description">
                        Введите ваш API ключ от DeepSeek. Получить ключ можно на 
                        <a href="https://platform.deepseek.com/" target="_blank">платформе DeepSeek</a>.
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="deepseek_model">Модель DeepSeek</label>
                </th>
                <td>
                    <select id="deepseek_model" name="deepseek_model">
                        <option value="deepseek-chat" <?php selected($deepseek_model, 'deepseek-chat'); ?>>deepseek-chat</option>
                    </select>
                    <p class="description">
                        Выберите модель DeepSeek для генерации контента.
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="deepseek_max_tokens">Максимальное количество токенов</label>
                </th>
                <td>
                    <input type="number" 
                           id="deepseek_max_tokens" 
                           name="deepseek_max_tokens" 
                           value="<?php echo esc_attr($max_tokens); ?>" 
                           min="100" 
                           max="8000" 
                           class="small-text">
                    <p class="description">
                        Максимальное количество токенов в ответе (100-8000).
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="deepseek_temperature">Температура</label>
                </th>
                <td>
                    <input type="number" 
                           id="deepseek_temperature" 
                           name="deepseek_temperature" 
                           value="<?php echo esc_attr($temperature); ?>" 
                           min="0.0" 
                           max="1.5" 
                           step="0.1"
                           class="small-text">
                    <p class="description">
                        Контролирует креативность ответов. 0.0 = детерминированный, 1.5 = очень креативный.
                        <br><strong>Рекомендации:</strong> Код/Математика: 0.0, Анализ данных: 1.0, Общение/Перевод: 1.3, Творчество: 1.5
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="deepseek_remove_markdown">Удалять Markdown разметку</label>
                </th>
                <td>
                    <input type="checkbox" 
                           id="deepseek_remove_markdown" 
                           name="deepseek_remove_markdown" 
                           value="1" 
                           <?php checked($remove_markdown, true); ?>>
                    <label for="deepseek_remove_markdown">Удалять символы разметки (#, **, *, и т.д.) из результата</label>
                    <p class="description">
                        Если включено, из сгенерированного текста будут удалены символы Markdown разметки.
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="Сохранить настройки">
        </p>
    </form>
    <div style="margin-top: 20px; font-size: 15px;">
        По всем вопросам поддержки: <a href="mailto:admin@keytracker.ru">admin@keytracker.ru</a>
    </div>
    

    
   <!--  <div class="deepseek-info">
        <h2>Информация о плагине</h2>
        <div class="info-section">
            <h3>Как использовать:</h3>
            <ol>
                <li>Получите API ключ на <a href="https://platform.deepseek.com/" target="_blank">платформе DeepSeek</a></li>
                <li>Введите API ключ в настройках выше</li>
                <li>Настройте параметры генерации (токены, температура)</li>
                <li>Перейдите в раздел "DeepSeek Generator" в меню администратора</li>
                <li>Введите промт и нажмите "Сгенерировать контент"</li>
                <li>Нажмите "Создать черновик" для сохранения результата</li>
            </ol>
        </div>
        
        <div class="info-section">
            <h3>Возможности:</h3>
            <ul>
                <li>Генерация контента с помощью DeepSeek API</li>
                <li>Сохранение промтов в черновики для повторного использования</li>
                <li>Создание черновиков записей с умными заголовками</li>
                <li>Управление сохраненными промтами</li>
                <li>Настройка параметров генерации (температура, токены)</li>
                <li>Удаление Markdown разметки из результатов</li>
            </ul>
        </div>
        
        <div class="info-section">
            <h3>Поддерживаемые модели:</h3>
            <ul>
                <li><strong>DeepSeek:</strong> deepseek-chat</li>
            </ul>
        </div>
        
        <div class="info-section">
            <h3>Настройка температуры:</h3>
            <p>Температура контролирует креативность и предсказуемость ответов согласно <a href="https://api-docs.deepseek.com/quick_start/parameter_settings" target="_blank">документации DeepSeek</a>:</p>
            <ul>
                <li><strong>0.0</strong> - Coding / Math (детерминированный)</li>
                <li><strong>1.0</strong> - Data Cleaning / Data Analysis (сбалансированный)</li>
                <li><strong>1.3</strong> - General Conversation / Translation (креативный)</li>
                <li><strong>1.5</strong> - Creative Writing / Poetry (очень креативный)</li>
            </ul>
        </div>
    </div>
</div> -->

<style>
.deepseek-info {
    margin-top: 30px;
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.info-section {
    margin-bottom: 25px;
}

.info-section h3 {
    color: #0073aa;
    margin-bottom: 10px;
}

.info-section ol,
.info-section ul {
    margin-left: 20px;
}

.info-section li {
    margin-bottom: 5px;
}

.form-table th {
    width: 200px;
}

.form-table input[type="password"],
.form-table input[type="number"],
.form-table select {
    min-width: 300px;
}
</style> 