(function () {
    var doctorSelect = document.querySelector('[data-doctor-select]');
    var scheduleSelect = document.querySelector('[data-schedule-select]');

    function syncScheduleOptions() {
        if (!doctorSelect || !scheduleSelect) {
            return;
        }

        var selectedDoctor = doctorSelect.value;

        Array.prototype.forEach.call(scheduleSelect.options, function (option) {
            if (!option.dataset.doctor) {
                option.hidden = false;
                return;
            }

            var shouldShow = !selectedDoctor || option.dataset.doctor === selectedDoctor;
            option.hidden = !shouldShow;

            if (!shouldShow && option.selected) {
                scheduleSelect.value = '';
            }
        });
    }

    if (doctorSelect) {
        doctorSelect.addEventListener('change', syncScheduleOptions);
    }

    syncScheduleOptions();
})();

(function () {
    var panel = document.querySelector('[data-medical-ai]');

    if (!panel) {
        return;
    }

    var form = panel.querySelector('[data-medical-ai-form]');
    var result = panel.querySelector('[data-medical-ai-result]');
    var csrf = document.querySelector('meta[name="csrf-token"]');

    function showResult(text, state) {
        result.hidden = false;
        result.className = 'doctor-ai-result ' + (state || '');
        result.textContent = text;
        result.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var submit = form.querySelector('button[type="submit"]');
        var payload = {};
        var formData = new FormData(form);

        formData.forEach(function (value, key) {
            payload[key] = typeof value === 'string' ? value.trim() : value;
        });

        submit.disabled = true;
        showResult('AI đang phân tích dữ liệu lâm sàng...', 'pending');

        fetch(panel.dataset.aiUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf ? csrf.content : ''
            },
            body: JSON.stringify(payload)
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Không nhận được phản hồi AI.');
                }

                return response.json();
            })
            .then(function (payload) {
                showResult(
                    payload.data && payload.data.answer
                        ? payload.data.answer
                        : 'AI chưa tạo được gợi ý phù hợp. Bác sĩ vui lòng bổ sung dữ liệu lâm sàng.',
                    'ready'
                );
            })
            .catch(function () {
                showResult('Hệ thống AI đang bận hoặc dữ liệu chưa hợp lệ. Bác sĩ vui lòng thử lại sau.', 'error');
            })
            .finally(function () {
                submit.disabled = false;
            });
    });
})();

(function () {
    var widget = document.querySelector('[data-chatbot]');

    if (!widget) {
        return;
    }

    var toggle = widget.querySelector('[data-chat-toggle]');
    var closeButton = widget.querySelector('[data-chat-close]');
    var panel = widget.querySelector('[data-chat-panel]');
    var form = widget.querySelector('[data-chat-form]');
    var input = widget.querySelector('[data-chat-input]');
    var messages = widget.querySelector('[data-chat-messages]');
    var chatUrl = widget.dataset.chatUrl;
    var csrf = document.querySelector('meta[name="csrf-token"]');

    function appendMessage(text, type) {
        var bubble = document.createElement('div');
        bubble.className = 'ai-chat-message ' + type;
        bubble.textContent = text;
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
    }

    function setOpen(open) {
        panel.hidden = !open;
        panel.style.display = open ? 'grid' : 'none';
        widget.classList.toggle('is-open', open);

        if (open) {
            input.focus();
        }
    }

    toggle.addEventListener('click', function (event) {
        event.preventDefault();
        setOpen(panel.hidden || panel.style.display === 'none');
    });

    closeButton.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        setOpen(false);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var message = input.value.trim();

        if (!message) {
            return;
        }

        appendMessage(message, 'user');
        input.value = '';
        input.disabled = true;

        var pending = appendMessage('Đang trả lời...', 'bot pending');

        fetch(chatUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf ? csrf.content : ''
            },
            body: JSON.stringify({ message: message })
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Không gửi được câu hỏi.');
                }

                return response.json();
            })
            .then(function (payload) {
                pending.textContent = payload.data && payload.data.answer
                    ? payload.data.answer
                    : 'Tôi chưa có câu trả lời phù hợp. Bạn vui lòng hỏi lại rõ hơn.';
                pending.classList.remove('pending');
            })
            .catch(function () {
                pending.textContent = 'Hệ thống đang bận. Bạn vui lòng thử lại sau hoặc liên hệ hotline 1900 1000.';
                pending.classList.remove('pending');
            })
            .finally(function () {
                input.disabled = false;
                input.focus();
            });
    });
})();
