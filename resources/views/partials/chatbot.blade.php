<div class="ai-chat-widget" data-chatbot data-chat-url="{{ route('chatbot.reply') }}">
    <button class="ai-chat-toggle" type="button" data-chat-toggle aria-label="Mở trợ lý AI">
        <i class="fa fa-comments-o"></i>
        <span>AI</span>
    </button>

    <section class="ai-chat-panel" data-chat-panel hidden>
        <header class="ai-chat-header">
            <div>
                <strong>Trợ lý bệnh viện</strong>
            </div>
            <button type="button" data-chat-close aria-label="Đóng trợ lý AI">
                <i class="fa fa-times"></i>
            </button>
        </header>

        <div class="ai-chat-messages" data-chat-messages>
            <div class="ai-chat-message bot">
                Xin chào, tôi có thể hỗ trợ đặt lịch, chuyên khoa, bác sĩ, dịch vụ và phí khám.
            </div>
        </div>

        <form class="ai-chat-form" data-chat-form>
            <input type="text" name="message" data-chat-input placeholder="Nhập câu hỏi của bạn" autocomplete="off" maxlength="1000" required>
            <button type="submit" aria-label="Gửi câu hỏi">
                <i class="fa fa-paper-plane"></i>
            </button>
        </form>
    </section>
</div>
