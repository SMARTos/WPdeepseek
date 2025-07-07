<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>DeepSeek Content Generator</h1>
    
    <div class="deepseek-container">
        <!-- Основная форма генерации контента -->
        <div class="deepseek-main-form">
            <h2>Генерация контента</h2>
            
            <div class="form-group">
                <label for="prompt-input">Введите промт:</label>
                <textarea id="prompt-input" rows="6" placeholder="Опишите, какой контент вы хотите создать..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="saved-prompts">Сохраненные промты:</label>
                <select id="saved-prompts">
                    <option value="">Выберите сохраненный промт...</option>
                </select>
                <button type="button" id="load-prompt" class="button">Загрузить</button>
            </div>
            
            <div class="form-actions">
                <button type="button" id="generate-content" class="button button-primary">Сгенерировать контент</button>
                <button type="button" id="save-prompt-btn" class="button">Сохранить промт</button>
                <span id="loading" style="display: none;">Генерация контента...</span>
            </div>
        </div>
        
        <!-- Результат генерации -->
        <div class="deepseek-result" style="display: none;">
            <h3>Сгенерированный контент:</h3>
            <div id="generated-content"></div>
            <div class="result-actions">
                <button type="button" id="create-draft" class="button button-primary">Создать черновик</button>
                <button type="button" id="copy-content" class="button">Копировать</button>
                <button type="button" id="clear-result" class="button">Очистить</button>
            </div>
        </div>
        
        <!-- Модальное окно для сохранения промта -->
        <div id="save-prompt-modal" class="deepseek-modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>Сохранить промт</h3>
                <div class="form-group">
                    <label for="prompt-title">Название промта:</label>
                    <input type="text" id="prompt-title" placeholder="Введите название для промта">
                </div>
                <div class="form-group">
                    <label for="prompt-text">Текст промта:</label>
                    <textarea id="prompt-text" rows="4" readonly></textarea>
                </div>
                <button type="button" id="confirm-save-prompt" class="button button-primary">Сохранить</button>
            </div>
        </div>
        
        <!-- Управление сохраненными промтами -->
        <div class="deepseek-saved-prompts">
            <h3>Управление промтами</h3>
            <div id="prompts-list">
                <!-- Список промтов будет загружен через AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
.deepseek-container {
    max-width: 1200px;
    margin: 20px 0;
}

.deepseek-main-form {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group textarea,
.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 14px;
}

.form-group select {
    width: calc(100% - 100px);
    margin-right: 10px;
}

.form-actions {
    margin-top: 20px;
}

.form-actions button {
    margin-right: 10px;
}

#loading {
    color: #0073aa;
    font-style: italic;
}

.deepseek-result {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 20px;
}

#generated-content {
    background: #f9f9f9;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 3px;
    margin: 10px 0;
    white-space: pre-wrap;
    max-height: 400px;
    overflow-y: auto;
}

.result-actions {
    margin-top: 15px;
}

.result-actions button {
    margin-right: 10px;
}

.deepseek-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 5px;
    width: 500px;
    position: relative;
}

.close {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 24px;
    cursor: pointer;
}

.close:hover {
    color: #0073aa;
}

.deepseek-saved-prompts {
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.prompt-item {
    background: #f9f9f9;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 3px;
    border-left: 4px solid #0073aa;
}

.prompt-item h4 {
    margin: 0 0 10px 0;
    color: #0073aa;
}

.prompt-item p {
    margin: 0 0 10px 0;
    color: #666;
}

.prompt-item .prompt-actions {
    margin-top: 10px;
}

.prompt-item .prompt-actions button {
    margin-right: 5px;
    font-size: 12px;
}

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 3px;
    margin: 10px 0;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 3px;
    margin: 10px 0;
}
</style> 