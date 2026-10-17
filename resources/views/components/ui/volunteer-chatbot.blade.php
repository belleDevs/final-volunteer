<!-- Volunteer AI Chatbot Floating Component -->
<div id="volunteer-chatbot-root">
    <!-- Floating Launcher Button -->
    <button type="button" 
            onclick="toggleVolunteerChatbot()" 
            id="chatbot-launcher-btn"
            class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 bg-jci-blue hover:bg-jci-dark text-white rounded-full p-3.5 shadow-2xl shadow-sky-500/40 flex items-center gap-2.5 transition-all duration-300 hover:scale-105 group border-2 border-white/20">
        <div class="relative">
            <i class="fa-solid fa-robot text-xl group-hover:rotate-12 transition-transform duration-300"></i>
            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border border-white"></span>
            </span>
        </div>
        <span class="text-xs font-extrabold pr-1 hidden sm:inline-block tracking-wide">Messages</span>
    </button>

    <!-- Chat Drawer Modal -->
    <div id="volunteer-chatbot-drawer" 
         class="fixed z-50 bg-white/95 backdrop-blur-md shadow-2xl border border-slate-200 flex flex-col overflow-hidden transition-all duration-300 transform scale-90 opacity-0 pointer-events-none inset-x-0 bottom-0 h-[min(88dvh,640px)] max-h-[92dvh] rounded-t-3xl sm:inset-x-auto sm:bottom-24 sm:right-6 sm:w-[400px] sm:max-w-[92vw] sm:h-[520px] sm:max-h-[80vh] sm:rounded-3xl">
        
        <!-- Drawer Header -->
        <div class="bg-gradient-to-r from-jci-blue via-jci-dark to-jci-blue p-4 text-white flex items-center justify-between shadow-md shrink-0">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center text-jci-accent">
                    <i class="fa-solid fa-robot text-lg"></i>
                </div>
                <div>
                    <h4 id="chatbot-title" class="font-black text-xs tracking-wide">Messages</h4>
                    <p id="chatbot-status" class="text-[10px] text-sky-200 flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Assistant + organization
                    </p>
                </div>
            </div>
            <button type="button" onclick="toggleVolunteerChatbot()" class="h-7 w-7 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white text-xs transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Chat Messages Container -->
        <div id="chatbot-messages" class="flex-grow p-4 overflow-y-auto space-y-3.5 custom-scrollbar text-xs">
            <!-- Initial Greeting -->
            <div class="flex items-start gap-2.5" data-chat-greeting>
                <div class="h-7 w-7 rounded-full bg-jci-blue text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5 shadow-sm">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="bg-slate-100 text-slate-800 p-3 rounded-2xl rounded-tl-xs max-w-[85%] shadow-xs leading-relaxed">
                    Hi {{ Auth::user()->name }}! Ask Who / What / When / Where / Why / How about your account, your organization, or VolunteerHub. If I don’t know — or you say you want to talk to a person — your organization can reply in this same Messages chat.
                    <span class="block text-[9px] text-slate-400 mt-1 font-semibold">Just now</span>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div id="chatbot-typing" class="px-4 py-1.5 hidden">
            <div class="flex items-center gap-2 text-slate-400 text-[11px] italic">
                <div class="flex gap-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-jci-blue animate-bounce"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-jci-blue animate-bounce [animation-delay:0.2s]"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-jci-blue animate-bounce [animation-delay:0.4s]"></span>
                </div>
                <span>Typing…</span>
            </div>
        </div>

        <!-- Quick Suggestion Chips -->
        <div class="px-3 py-2 bg-slate-50 border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0">
            <button type="button" onclick="sendQuickChip('Who is the president?')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">Who</button>
            <button type="button" onclick="sendQuickChip('What are my tasks?')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">What</button>
            <button type="button" onclick="sendQuickChip('When is my next event?')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">When</button>
            <button type="button" onclick="sendQuickChip('How do I submit evidence?')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">How</button>
            <button type="button" onclick="sendQuickChip('What is VolunteerHub?')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">System</button>
            <button type="button" onclick="sendQuickChip('I want to talk to my organization')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">Talk to org</button>
            <button type="button" onclick="sendQuickChip('Talk to the assistant')" class="bg-white hover:bg-sky-50 border border-slate-200 hover:border-jci-blue text-slate-600 hover:text-jci-blue text-[10px] font-bold px-2.5 py-1 rounded-full whitespace-nowrap transition">Back to AI</button>
        </div>

        <!-- Footer Input Area -->
        <div class="p-3 bg-white border-t border-slate-200 shrink-0">
            <form id="chatbot-form" onsubmit="handleVolunteerChatSubmit(event)" class="flex items-center gap-2">
                <input type="text" 
                       id="chatbot-input" 
                       placeholder="Ask Who/What/When/Where/Why/How — or talk to your org…" 
                       required 
                       autocomplete="off"
                       class="flex-grow border border-slate-200 rounded-xl px-3 py-2 text-xs focus:ring-1 focus:ring-jci-blue focus:outline-none bg-slate-50">
                <button type="submit" 
                        id="chatbot-send-btn"
                        class="bg-jci-blue hover:bg-jci-dark text-white rounded-xl h-8 w-8 flex items-center justify-center transition shadow-md shrink-0">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                </button>
            </form>
        </div>
    </div>
</div>

@php
    $chatUser = Auth::user();
    if ($chatUser && method_exists($chatUser, 'loadMissing')) {
        $chatUser->loadMissing('primaryOrganization');
    }
    $chatOrg = $chatUser?->primaryOrganization;
    $chatSelfPhoto = $chatUser?->profile_photo_path ? asset('storage/'.$chatUser->profile_photo_path) : null;
    $chatOrgPhoto = $chatOrg?->profile_photo_path ? asset('storage/'.$chatOrg->profile_photo_path) : null;
    $chatSelfInitials = collect(preg_split('/\s+/', trim((string) ($chatUser->name ?? ''))) ?: [])
        ->filter()->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->join('') ?: '?';
    $chatOrgInitials = collect(preg_split('/\s+/', trim((string) ($chatOrg->name ?? 'Org'))) ?: [])
        ->filter()->take(2)->map(fn ($p) => strtoupper(substr($p, 0, 1)))->join('') ?: 'O';
@endphp
<!-- Chatbot JS Controller -->
<script>
    let volunteerChatLoaded = false;
    let volunteerChatStickBottom = true;
    let volunteerChatFingerprint = '';
    let volunteerChatLoading = false;
    let volunteerChatScrollBound = false;
    const volunteerChatAvatars = {
        selfPhoto: @json($chatSelfPhoto),
        orgPhoto: @json($chatOrgPhoto),
        selfInitials: @json($chatSelfInitials),
        orgInitials: @json($chatOrgInitials),
    };

    function toggleVolunteerChatbot() {
        const drawer = document.getElementById('volunteer-chatbot-drawer');
        if (!drawer) return;

        const isHidden = drawer.classList.contains('pointer-events-none');
        if (isHidden) {
            drawer.classList.remove('scale-90', 'opacity-0', 'pointer-events-none');
            drawer.classList.add('scale-100', 'opacity-100', 'pointer-events-auto');
            document.getElementById('chatbot-input')?.focus();
            bindChatScrollWatcher();

            if (!volunteerChatLoaded) {
                volunteerChatStickBottom = true;
                loadChatbotHistory({ forceScroll: true });
                volunteerChatLoaded = true;
                if (!window.volunteerChatPoll) {
                    window.volunteerChatPoll = setInterval(function () {
                        const open = !document.getElementById('volunteer-chatbot-drawer')?.classList.contains('pointer-events-none');
                        if (open) {
                            loadChatbotHistory();
                        }
                    }, 10000);
                }
            }
        } else {
            drawer.classList.add('scale-90', 'opacity-0', 'pointer-events-none');
            drawer.classList.remove('scale-100', 'opacity-100', 'pointer-events-auto');
        }
    }

    function bindChatScrollWatcher() {
        if (volunteerChatScrollBound) return;
        const container = document.getElementById('chatbot-messages');
        if (!container) return;
        volunteerChatScrollBound = true;
        container.addEventListener('scroll', function () {
            volunteerChatStickBottom = isChatNearBottom(container);
        }, { passive: true });
    }

    function isChatNearBottom(container, threshold) {
        const gap = (container.scrollHeight - container.scrollTop - container.clientHeight);
        return gap < (threshold || 72);
    }

    function sendQuickChip(text) {
        const input = document.getElementById('chatbot-input');
        if (input) {
            input.value = text;
            handleVolunteerChatSubmit(new Event('submit'));
        }
    }

    function historyFingerprint(history) {
        return (history || []).map(function (item) {
            return [
                item.message || '',
                item.response || '',
                item.admin_reply || '',
                item.time || '',
                item.admin_time || ''
            ].join('|');
        }).join('||');
    }

    async function loadChatbotHistory(options) {
        const opts = options || {};
        if (volunteerChatLoading && !opts.force) return;
        volunteerChatLoading = true;

        try {
            const res = await fetch("{{ route('volunteer.chatbot.history') }}");
            const data = await res.json();
            const container = document.getElementById('chatbot-messages');
            if (!container) return;

            const title = document.getElementById('chatbot-title');
            const status = document.getElementById('chatbot-status');
            if (data.mode === 'agent' && data.organization) {
                if (title) title.textContent = data.organization;
                if (status) status.textContent = 'Talking with ' + data.organization;
            } else if (status) {
                status.textContent = data.organization_online && data.organization
                    ? data.organization + ' is online'
                    : 'Assistant + organization';
            }

            const nextFingerprint = historyFingerprint(data.history || []);
            if (!opts.force && nextFingerprint === volunteerChatFingerprint) {
                return;
            }

            const stayPut = !opts.forceScroll && !volunteerChatStickBottom;
            const previousTop = container.scrollTop;
            const previousHeight = container.scrollHeight;

            container.querySelectorAll('[data-chat-row]').forEach(function (row) { row.remove(); });

            const greeting = container.querySelector('[data-chat-greeting]');
            if (greeting) {
                greeting.classList.toggle('hidden', !!(data.success && data.history && data.history.length));
            }

            if (data.success && data.history && data.history.length > 0) {
                data.history.forEach(function (item) {
                    if (item.message) {
                        appendUserBubble(item.message, item.time);
                    }
                    if (item.show_assistant && item.response) {
                        appendAiBubble(item.response, item.time);
                    }
                    if (item.admin_reply) {
                        appendAdminBubble(item.admin_reply, item.admin_time || item.time, item.organization || data.organization || 'Organization');
                    }
                });
            }

            volunteerChatFingerprint = nextFingerprint;

            if (stayPut) {
                const heightDelta = container.scrollHeight - previousHeight;
                container.scrollTop = previousTop + Math.max(heightDelta, 0);
            } else {
                scrollToChatBottom();
                volunteerChatStickBottom = true;
            }
        } catch (e) {
            console.error('Failed to load chat history', e);
        } finally {
            volunteerChatLoading = false;
        }
    }

    async function handleVolunteerChatSubmit(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('chatbot-input');
        const sendBtn = document.getElementById('chatbot-send-btn');
        const typing = document.getElementById('chatbot-typing');

        const message = input.value.trim();
        if (!message || sendBtn.disabled) return;

        input.value = '';
        volunteerChatStickBottom = true;
        const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        appendUserBubble(message, now);
        scrollToChatBottom();

        typing.classList.remove('hidden');
        sendBtn.disabled = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch("{{ route('volunteer.chatbot.ask') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify({ message: message })
            });

            const data = await res.json();
            typing.classList.add('hidden');
            sendBtn.disabled = false;

            if (data.success) {
                const title = document.getElementById('chatbot-title');
                const status = document.getElementById('chatbot-status');
                if (data.with_agent && data.organization) {
                    if (title) title.textContent = data.organization;
                    if (status) status.textContent = 'Talking with ' + data.organization;
                } else {
                    if (title) title.textContent = 'Messages';
                    if (status) status.textContent = 'Assistant + organization';
                }
                // Replace optimistic bubbles with server history once (avoids duplicates + scroll fights).
                volunteerChatStickBottom = true;
                await loadChatbotHistory({ force: true, forceScroll: true });
            } else {
                appendAiBubble("Sorry, I couldn't process your question right now. Please try again.", now);
                scrollToChatBottom();
            }
        } catch (err) {
            typing.classList.add('hidden');
            sendBtn.disabled = false;
            appendAiBubble("Connection issue. Please check your network and try again.", now);
            scrollToChatBottom();
        }
    }

    function appendUserBubble(text, time) {
        const container = document.getElementById('chatbot-messages');
        const greeting = container?.querySelector('[data-chat-greeting]');
        if (greeting) greeting.classList.add('hidden');
        const div = document.createElement('div');
        div.className = 'flex items-end justify-end gap-2';
        div.setAttribute('data-chat-row', '1');
        div.innerHTML = `
            <div class="bg-jci-blue text-white p-3 rounded-2xl rounded-tr-xs max-w-[85%] shadow-xs leading-relaxed">
                ${escapeHtml(text)}
                <span class="block text-[9px] text-sky-200 mt-1 font-semibold text-right">${time}</span>
            </div>
            ${volunteerChatAvatarHtml(volunteerChatAvatars.selfPhoto, volunteerChatAvatars.selfInitials)}
        `;
        container.appendChild(div);
    }

    function appendAiBubble(text, time) {
        const container = document.getElementById('chatbot-messages');
        const div = document.createElement('div');
        div.className = 'flex items-start gap-2.5';
        div.setAttribute('data-chat-row', '1');
        div.innerHTML = `
            <div class="h-7 w-7 rounded-full bg-jci-blue text-white flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5 shadow-sm">
                <i class="fa-solid fa-robot"></i>
            </div>
            <div class="bg-slate-100 text-slate-800 p-3.5 rounded-2xl rounded-tl-xs max-w-[85%] shadow-xs text-[12px] leading-relaxed">
                <div class="chat-body space-y-2">${formatChatText(text)}</div>
                <span class="block text-[9px] text-slate-400 mt-2 font-semibold">${time}</span>
            </div>
        `;
        container.appendChild(div);
    }

    function appendAdminBubble(text, time, organization) {
        const container = document.getElementById('chatbot-messages');
        const div = document.createElement('div');
        div.className = 'flex items-start gap-2.5';
        div.setAttribute('data-chat-row', '1');
        div.innerHTML = `
            ${volunteerChatAvatarHtml(volunteerChatAvatars.orgPhoto, volunteerChatAvatars.orgInitials)}
            <div class="bg-sky-50 border border-sky-100 text-slate-800 p-3.5 rounded-2xl rounded-tl-xs max-w-[85%] shadow-xs text-[12px] leading-relaxed">
                <span class="block text-[9px] font-black uppercase text-jci-blue mb-1.5">${escapeHtml(organization || 'Organization')}</span>
                <div class="chat-body space-y-2">${formatChatText(text)}</div>
                <span class="block text-[9px] text-slate-400 mt-2 font-semibold">${time}</span>
            </div>
        `;
        container.appendChild(div);
    }

    function scrollToChatBottom() {
        const container = document.getElementById('chatbot-messages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function volunteerChatAvatarHtml(photo, initials) {
        if (photo) {
            return '<img src="' + escapeHtml(photo) + '" alt="" class="h-7 w-7 shrink-0 rounded-full object-cover mt-0.5">';
        }
        return '<span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-500 mt-0.5">' + escapeHtml(initials) + '</span>';
    }

    function formatChatText(str) {
        const blocks = String(str || '').trim().split(/\n{2,}/);
        return blocks.map(function (block) {
            const lines = block.split('\n').map(function (line) {
                const t = escapeHtml(line.trimEnd());
                if (/^•\s/.test(line) || /^\d+\.\s/.test(line)) {
                    return '<div class="pl-0.5">' + t + '</div>';
                }
                return t;
            }).join('<br>');
            return '<p class="m-0">' + lines + '</p>';
        }).join('');
    }
</script>
