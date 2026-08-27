<!-- AI Chatbot Widget -->
<div id="ai-chatbot-widget" class="fixed bottom-6 right-6 z-[100]">
    <!-- Chat Window -->
    <div id="ai-chat-window" class="hidden mb-4 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden w-[360px] max-w-[calc(100vw-2rem)]">
        <div class="bg-[#0B132B] text-white px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-[20px]">smart_toy</span>
                </div>
                <div>
                    <div class="text-sm font-bold">AI Assistant</div>
                    <div class="text-[10px] text-white/50">Velex AI Chatbot</div>
                </div>
            </div>
            <button onclick="document.getElementById('ai-chat-window').classList.add('hidden')" class="text-white/60 hover:text-white">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div id="ai-chat-messages" class="h-[320px] overflow-y-auto p-4 bg-slate-50 space-y-3">
            <div class="flex items-start gap-2">
                <div class="w-7 h-7 rounded-full bg-emerald-500 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-white text-[14px]">smart_toy</span>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-sm px-3 py-2 text-xs text-slate-800 shadow-sm">
                    👋 Hi! I'm your AI assistant. Ask me about bikes, pricing, maintenance, agreements, or demand forecasts!
                </div>
            </div>
        </div>
        <div class="p-3 border-t border-slate-100 bg-white">
            <div class="flex gap-2">
                <input id="ai-chat-input" type="text" placeholder="Ask about bikes, prices, maintenance..."
                    class="flex-1 text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <button onclick="sendAIMessage()" class="bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl px-3 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px]">send</span>
                </button>
            </div>
            <div class="flex gap-1.5 mt-2 flex-wrap">
                <button onclick="quickAIPrompt('Recommend a bike in Karachi under budget')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full px-2.5 py-1">Recommend a bike</button>
                <button onclick="quickAIPrompt('What is the maintenance schedule?')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full px-2.5 py-1">Maintenance</button>
                <button onclick="quickAIPrompt('Tell me about the agreement terms')" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full px-2.5 py-1">Agreements</button>
            </div>
        </div>
    </div>

    <!-- Toggle Button -->
    <button onclick="toggleAIChat()" id="ai-chat-toggle" class="ml-auto bg-emerald-700 hover:bg-emerald-800 text-white w-14 h-14 rounded-full shadow-xl flex items-center justify-center transition-colors">
        <span class="material-symbols-outlined text-[26px]">chat</span>
    </button>
</div>

<script>
    let aiSession = @json([
        'csrf_token' => csrf_token(),
        'chat_url' => route('ai.chat'),
    ]);

    function toggleAIChat() {
        document.getElementById('ai-chat-window').classList.toggle('hidden');
    }

    function quickAIPrompt(text) {
        document.getElementById('ai-chat-input').value = text;
        sendAIMessage();
    }

    function sendAIMessage() {
        const input = document.getElementById('ai-chat-input');
        const message = input.value.trim();
        if (!message) return;

        const messages = document.getElementById('ai-chat-messages');

        // User bubble
        const userDiv = document.createElement('div');
        userDiv.className = 'flex items-start gap-2 justify-end';
        userDiv.innerHTML = `
            <div class="bg-emerald-700 text-white rounded-2xl rounded-tr-sm px-3 py-2 text-xs shadow-sm">${escapeHtml(message)}</div>
            <div class="w-7 h-7 rounded-full bg-slate-400 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-white text-[14px]">person</span>
            </div>
        `;
        messages.appendChild(userDiv);
        input.value = '';
        messages.scrollTop = messages.scrollHeight;

        // Loading indicator
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'flex items-start gap-2';
        loadingDiv.innerHTML = `
            <div class="w-7 h-7 rounded-full bg-emerald-500 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-white text-[14px]">smart_toy</span>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-sm px-3 py-2 text-xs text-slate-400">Thinking...</div>
        `;
        messages.appendChild(loadingDiv);
        messages.scrollTop = messages.scrollHeight;

        // API call
        fetch(aiSession.chat_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': aiSession.csrf_token,
            },
            body: JSON.stringify({ message: message }),
        })
        .then(res => res.json())
        .then(data => {
            loadingDiv.remove();
            const botDiv = document.createElement('div');
            botDiv.className = 'flex items-start gap-2';
            botDiv.innerHTML = `
                <div class="w-7 h-7 rounded-full bg-emerald-500 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-white text-[14px]">smart_toy</span>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-sm px-3 py-2 text-xs text-slate-800 shadow-sm whitespace-pre-wrap">${escapeHtml(data.reply || JSON.stringify(data))}</div>
            `;
            messages.appendChild(botDiv);
            messages.scrollTop = messages.scrollHeight;
        })
        .catch(err => {
            loadingDiv.remove();
            const errDiv = document.createElement('div');
            errDiv.className = 'flex items-start gap-2';
            errDiv.innerHTML = `
                <div class="w-7 h-7 rounded-full bg-emerald-500 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-white text-[14px]">smart_toy</span>
                </div>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl rounded-tl-sm px-3 py-2 text-xs">Sorry, I couldn't reach the AI service. Please try again.</div>
            `;
            messages.appendChild(errDiv);
            messages.scrollTop = messages.scrollHeight;
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Enter key support
    document.getElementById('ai-chat-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            sendAIMessage();
        }
    });
</script>
