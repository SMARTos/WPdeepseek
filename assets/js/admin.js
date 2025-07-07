jQuery(document).ready(function($) {
    
    // Загружаем сохраненные промты при загрузке страницы
    loadSavedPrompts();
    
    // Обработчик генерации контента
    $('#generate-content').on('click', function() {
        var prompt = $('#prompt-input').val().trim();
        
        if (!prompt) {
            showMessage('Пожалуйста, введите промт', 'error');
            return;
        }
        
        generateContent(prompt);
    });
    
    // Обработчик сохранения промта
    $('#save-prompt-btn').on('click', function() {
        var prompt = $('#prompt-input').val().trim();
        
        if (!prompt) {
            showMessage('Пожалуйста, введите промт для сохранения', 'error');
            return;
        }
        
        showSavePromptModal(prompt);
    });
    
    // Обработчик загрузки сохраненного промта
    $('#load-prompt').on('click', function() {
        var selectedPrompt = $('#saved-prompts option:selected');
        
        if (selectedPrompt.val()) {
            $('#prompt-input').val(selectedPrompt.data('prompt'));
        }
    });
    
    // Обработчик подтверждения сохранения промта
    $('#confirm-save-prompt').on('click', function() {
        var title = $('#prompt-title').val().trim();
        var prompt = $('#prompt-text').val();
        
        if (!title) {
            showMessage('Пожалуйста, введите название для промта', 'error');
            return;
        }
        
        savePrompt(title, prompt);
    });
    
    // Обработчик создания черновика
    $('#create-draft').on('click', function() {
        var content = $('#generated-content').text();
        
        if (content) {
            createDraft(content);
        } else {
            showMessage('Сначала сгенерируйте контент', 'error');
        }
    });
    
    // Обработчик копирования контента
    $('#copy-content').on('click', function() {
        var content = $('#generated-content').text();
        
        if (content) {
            copyToClipboard(content);
            showMessage('Контент скопирован в буфер обмена', 'success');
        }
    });
    
    // Обработчик очистки результата
    $('#clear-result').on('click', function() {
        $('.deepseek-result').hide();
        $('#generated-content').text('');
    });
    
    // Закрытие модального окна
    $('.close').on('click', function() {
        $('#save-prompt-modal').hide();
    });
    
    // Закрытие модального окна при клике вне его
    $(window).on('click', function(event) {
        if (event.target == document.getElementById('save-prompt-modal')) {
            $('#save-prompt-modal').hide();
        }
    });
    
    // Функция генерации контента
    function generateContent(prompt) {
        $('#loading').show();
        $('#generate-content').prop('disabled', true);
        
        $.ajax({
            url: deepseek_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'deepseek_generate_content',
                prompt: prompt,
                nonce: deepseek_ajax.nonce
            },
            success: function(response) {
                $('#loading').hide();
                $('#generate-content').prop('disabled', false);
                
                if (response.success) {
                    $('#generated-content').text(response.data.content);
                    $('.deepseek-result').show();
                    
                    showMessage('Контент успешно сгенерирован!', 'success');
                } else {
                    showMessage('Ошибка: ' + response.data, 'error');
                }
            },
            error: function() {
                $('#loading').hide();
                $('#generate-content').prop('disabled', false);
                showMessage('Произошла ошибка при генерации контента', 'error');
            }
        });
    }
    
    // Функция сохранения промта
    function savePrompt(title, prompt) {
        $.ajax({
            url: deepseek_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'deepseek_save_prompt',
                title: title,
                prompt: prompt,
                nonce: deepseek_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data, 'success');
                    $('#save-prompt-modal').hide();
                    $('#prompt-title').val('');
                    loadSavedPrompts();
                } else {
                    showMessage('Ошибка: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Произошла ошибка при сохранении промта', 'error');
            }
        });
    }
    
    // Функция загрузки сохраненных промтов
    function loadSavedPrompts() {
        $.ajax({
            url: deepseek_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'deepseek_get_prompts',
                nonce: deepseek_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updatePromptsList(response.data);
                    updatePromptsSelect(response.data);
                }
            },
            error: function() {
                console.log('Ошибка загрузки промтов');
            }
        });
    }
    
    // Функция обновления списка промтов
    function updatePromptsList(prompts) {
        var html = '';
        
        if (prompts.length === 0) {
            html = '<p>Сохраненных промтов пока нет.</p>';
        } else {
            prompts.forEach(function(prompt) {
                html += '<div class="prompt-item">';
                html += '<h4>' + escapeHtml(prompt.title) + '</h4>';
                html += '<p><strong>Промт:</strong> ' + escapeHtml(prompt.prompt.substring(0, 100)) + (prompt.prompt.length > 100 ? '...' : '') + '</p>';
                html += '<p><small>Создан: ' + prompt.created_at + '</small></p>';
                html += '<div class="prompt-actions">';
                html += '<button type="button" class="button" onclick="loadPromptToInput(\'' + escapeHtml(prompt.prompt) + '\')">Загрузить</button>';
                html += '<button type="button" class="button" onclick="deletePrompt(' + prompt.id + ')">Удалить</button>';
                html += '</div>';
                html += '</div>';
            });
        }
        
        $('#prompts-list').html(html);
    }
    
    // Функция обновления выпадающего списка промтов
    function updatePromptsSelect(prompts) {
        var $select = $('#saved-prompts');
        $select.find('option:not(:first)').remove();
        
        prompts.forEach(function(prompt) {
            $select.append($('<option>', {
                value: prompt.id,
                text: prompt.title,
                'data-prompt': prompt.prompt
            }));
        });
    }
    
    // Функция удаления промта
    window.deletePrompt = function(id) {
        if (confirm('Вы уверены, что хотите удалить этот промт?')) {
            $.ajax({
                url: deepseek_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'deepseek_delete_prompt',
                    id: id,
                    nonce: deepseek_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.data, 'success');
                        loadSavedPrompts();
                    } else {
                        showMessage('Ошибка: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showMessage('Произошла ошибка при удалении промта', 'error');
                }
            });
        }
    };
    
    // Функция загрузки промта в поле ввода
    window.loadPromptToInput = function(prompt) {
        $('#prompt-input').val(prompt);
    };
    
    // Функция создания черновика
    function createDraft(content) {
        $.ajax({
            url: deepseek_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'deepseek_create_draft',
                content: content,
                nonce: deepseek_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var message = 'Черновик успешно создан! ';
                    if (response.data.edit_url) {
                        message += '<a href="' + response.data.edit_url + '" target="_blank">Открыть для редактирования</a>';
                    }
                    showMessage(message, 'success');
                } else {
                    showMessage('Ошибка создания черновика: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Произошла ошибка при создании черновика', 'error');
            }
        });
    }
    
    // Функция копирования в буфер обмена
    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
        } else {
            // Fallback для старых браузеров
            var textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    }
    
    // Функция показа модального окна сохранения промта
    function showSavePromptModal(prompt) {
        $('#prompt-text').val(prompt);
        $('#prompt-title').val('');
        $('#save-prompt-modal').show();
        $('#prompt-title').focus();
    }
    
    // Функция показа сообщений
    function showMessage(message, type) {
        var className = type === 'success' ? 'success-message' : 'error-message';
        var $message = $('<div class="' + className + '">' + message + '</div>');
        
        $('.deepseek-container').prepend($message);
        
        // Увеличиваем время показа для сообщений с ссылками
        var timeout = message.includes('<a') ? 8000 : 5000;
        
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, timeout);
    }
    
    // Функция экранирования HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Обработка Enter в поле ввода промта
    $('#prompt-input').on('keydown', function(e) {
        if (e.ctrlKey && e.keyCode === 13) {
            e.preventDefault();
            $('#generate-content').click();
        }
    });
    
    // Обработка Enter в модальном окне
    $('#prompt-title').on('keydown', function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            $('#confirm-save-prompt').click();
        }
    });
}); 