@props([
    'id' => 'modal-event-details',
    'dutiesHeading' => 'Task assignments',
])

<div id="{{ $id }}" class="fixed inset-0 z-[999] hidden items-center justify-center bg-slate-950/60 p-3 backdrop-blur-xs sm:p-4">
    <div class="custom-scrollbar max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl sm:p-6 dark:border-slate-700 dark:bg-slate-900">
        <div class="sticky top-0 z-10 mb-4 flex items-center justify-between border-b border-slate-100 bg-white pb-3 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-sm font-black text-slate-800 sm:text-base dark:text-white">Event Details</h3>
            <button type="button" data-close-event-details="{{ $id }}" class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" aria-label="Close">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <div class="space-y-4 text-sm">
            <div>
                <h4 data-event-detail="title" class="text-base font-semibold text-slate-900 dark:text-white"></h4>
                <p data-event-detail="status" class="mt-1 text-xs font-semibold text-jci-blue"></p>
            </div>
            <div class="space-y-1.5 text-xs text-slate-500">
                <p data-event-detail="when"></p>
                <p data-event-detail="where"></p>
                <p data-event-detail="volunteers"></p>
                <p data-event-detail="organization" class="hidden"></p>
            </div>
            <p data-event-detail="note" class="text-xs leading-relaxed text-slate-600 dark:text-slate-300"></p>
            <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $dutiesHeading }}</h5>
                <div data-event-detail="duties" class="mt-2 space-y-2"></div>
                <p data-event-detail="duties-empty" class="mt-2 hidden text-xs text-slate-400">No task assignments on this event.</p>
            </div>
            <div data-event-detail="extra" class="hidden"></div>
        </div>
    </div>
</div>

<script>
    (function () {
        const modalId = @json($id);
        const modal = document.getElementById(modalId);
        if (!modal || modal.dataset.bound === '1') {
            return;
        }
        modal.dataset.bound = '1';
        document.body.appendChild(modal);

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        modal.querySelectorAll('[data-close-event-details="' + modalId + '"]').forEach(function (button) {
            button.addEventListener('click', closeModal);
        });
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        window.openSharedEventDetails = function (button) {
            const targetId = button.dataset.modalId || 'modal-event-details';
            const target = document.getElementById(targetId) || modal;
            if (!target) {
                return;
            }

            const setText = function (key, value) {
                const node = target.querySelector('[data-event-detail="' + key + '"]');
                if (node) {
                    node.textContent = value || '';
                }
            };

            setText('title', button.dataset.title);
            setText('status', button.dataset.status);
            setText('when', button.dataset.when);
            setText('where', button.dataset.where);
            setText('volunteers', button.dataset.volunteers || 'No volunteer limit');
            setText('note', button.dataset.note);

            const organization = target.querySelector('[data-event-detail="organization"]');
            if (organization) {
                const orgName = (button.dataset.organization || '').trim();
                organization.textContent = orgName ? ('Organization: ' + orgName) : '';
                organization.classList.toggle('hidden', !orgName);
            }

            const list = target.querySelector('[data-event-detail="duties"]');
            const empty = target.querySelector('[data-event-detail="duties-empty"]');
            const extra = target.querySelector('[data-event-detail="extra"]');
            if (list) {
                list.innerHTML = '';
            }
            if (extra) {
                extra.innerHTML = '';
                extra.classList.add('hidden');
            }

            let duties = [];
            try {
                duties = JSON.parse(button.dataset.duties || '[]');
            } catch (error) {
                duties = [];
            }

            if (!duties.length) {
                if (empty) {
                    empty.classList.remove('hidden');
                }
            } else if (list) {
                if (empty) {
                    empty.classList.add('hidden');
                }
                duties.forEach(function (duty) {
                    const card = document.createElement('div');
                    card.className = 'rounded-xl border border-slate-100 px-3 py-3 dark:border-slate-800';

                    const head = document.createElement('div');
                    head.className = 'flex flex-wrap items-center justify-between gap-2';

                    const title = document.createElement('p');
                    title.className = 'text-sm font-semibold text-slate-800 dark:text-slate-100';
                    title.textContent = duty.title || 'Task';

                    const status = document.createElement('span');
                    status.className = 'rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300';
                    status.textContent = duty.status || '';

                    head.appendChild(title);
                    head.appendChild(status);
                    card.appendChild(head);

                    if (duty.description) {
                        const description = document.createElement('p');
                        description.className = 'mt-1.5 text-xs leading-relaxed text-slate-500';
                        description.textContent = duty.description;
                        card.appendChild(description);
                    }

                    const meta = [];
                    if (duty.assignee) {
                        meta.push(duty.assignee);
                    }
                    if (duty.due) {
                        meta.push('Due ' + duty.due);
                    }
                    if (duty.hours) {
                        meta.push(duty.hours + ' hours approved');
                    }
                    if (meta.length) {
                        const metaLine = document.createElement('p');
                        metaLine.className = 'mt-1.5 text-[11px] text-slate-400';
                        metaLine.textContent = meta.join(' · ');
                        card.appendChild(metaLine);
                    }

                    list.appendChild(card);
                });
            }

            if (extra && button.dataset.extraHtml) {
                extra.innerHTML = button.dataset.extraHtml;
                extra.classList.remove('hidden');
            }

            target.classList.remove('hidden');
            target.classList.add('flex');
        };

        window.openEventDetails = window.openSharedEventDetails;
        window.openVolunteerEventDetails = window.openSharedEventDetails;
    })();
</script>
