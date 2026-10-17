@php
    $showPhotoDialog = $errors->hasAny(['photos', 'photos.*', 'event_id']);
@endphp
<div class="space-y-6">
    @if($galleryEvents->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No events yet</p>
            <p class="mt-1 text-xs text-slate-400">Event albums appear here after an event is created.</p>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($galleryEvents as $event)
                @php
                    $cover = $event->photos->first();
                    $open = $openGalleryEvent && (int) $openGalleryEvent->id === (int) $event->id;
                    $when = $event->start_time?->format(\App\Support\OrgPreferences::dateFormat($organization));
                @endphp
                <a href="{{ route('org.dashboard', ['section' => 'gallery', 'event' => $event->id]) }}"
                   class="group relative aspect-[4/5] overflow-hidden rounded-2xl border bg-slate-900 shadow-sm {{ $open ? 'border-jci-blue ring-2 ring-jci-blue' : 'border-slate-200 dark:border-slate-700' }}">
                    @if($cover)
                        <img src="{{ asset('storage/'.$cover->photo_path) }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                    @else
                        <div class="h-full w-full bg-gradient-to-br from-jci-blue to-[#0073a8]"></div>
                    @endif
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-4 pb-4 pt-16">
                        <p class="line-clamp-2 text-sm font-semibold leading-snug text-white">{{ $event->title }}</p>
                        <p class="mt-1 text-xs text-white/80">{{ $when }}@if($event->photos->isNotEmpty()) · {{ $event->photos->count() }} {{ \Illuminate\Support\Str::plural('photo', $event->photos->count()) }}@endif</p>
                    </div>
                </a>
            @endforeach
        </div>

        @if($openGalleryEvent)
            @php
                $openWhen = $openGalleryEvent->start_time?->format(\App\Support\OrgPreferences::dateFormat($organization).' g:i A');
            @endphp
            <section class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $openGalleryEvent->title }}</h2>
                        <p class="text-sm text-slate-500">{{ $openWhen }}@if($openGalleryEvent->location) · {{ $openGalleryEvent->location }}@endif</p>
                        <p class="text-sm text-slate-500">{{ $openGalleryEvent->photos->count() }} {{ \Illuminate\Support\Str::plural('photo', $openGalleryEvent->photos->count()) }}</p>
                    </div>
                    <button type="button" data-open-dialog="add-photos-dialog" class="inline-flex items-center justify-center gap-2 rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">
                        <i class="fa-solid fa-plus text-xs"></i>
                        Add photos
                    </button>
                </div>

                @if($openGalleryEvent->photos->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No photos in this album yet</p>
                        <p class="mt-1 text-xs text-slate-400">Add pictures from {{ $openGalleryEvent->title }}.</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach($openGalleryEvent->photos as $photo)
                            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <img src="{{ asset('storage/'.$photo->photo_path) }}" alt="{{ $openGalleryEvent->title }}" class="aspect-[4/3] w-full object-cover">
                                <form action="{{ route('org.gallery.destroy', $photo) }}" method="POST" class="px-3 py-2 text-center">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] font-semibold text-slate-400 hover:text-rose-600">Remove</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    @endif
</div>

@if($openGalleryEvent)
    <div id="add-photos-dialog" class="fixed inset-0 z-[999] items-center justify-center p-4 {{ $showPhotoDialog ? 'flex' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="add-photos-title">
        <button type="button" class="absolute inset-0 bg-slate-950/50" data-close-dialog="add-photos-dialog" aria-label="Close"></button>
        <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="add-photos-title" class="text-lg font-semibold text-slate-900 dark:text-white">Add photos</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $openGalleryEvent->title }}</p>
                </div>
                <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800" data-close-dialog="add-photos-dialog" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('org.gallery.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="event_id" value="{{ $openGalleryEvent->id }}">
                <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-dashed border-slate-300 p-4 transition hover:border-jci-blue dark:border-slate-600">
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                        <i class="fa-solid fa-images"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">Pictures</span>
                        <span id="gallery-photo-name" class="mt-0.5 block text-xs text-slate-500">JPG, PNG, or WEBP. Up to 12.</span>
                    </span>
                    <input id="gallery-photos" name="photos[]" type="file" required multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
                </label>
                <div id="gallery-photo-previews" class="grid grid-cols-4 gap-2 empty:hidden"></div>
                @error('photos')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
                @error('photos.*')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" data-close-dialog="add-photos-dialog">Cancel</button>
                    <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save photos</button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
    (function () {
        document.querySelectorAll('[role="dialog"]').forEach(function (dialog) {
            document.body.appendChild(dialog);
        });

        const openDialog = function (id) {
            const dialog = document.getElementById(id);
            if (!dialog) return;
            dialog.classList.remove('hidden');
            dialog.classList.add('flex');
        };
        const closeDialog = function (id) {
            const dialog = document.getElementById(id);
            if (!dialog) return;
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
        };

        document.querySelectorAll('[data-open-dialog]').forEach(function (button) {
            button.addEventListener('click', function () {
                openDialog(button.getAttribute('data-open-dialog'));
            });
        });
        document.querySelectorAll('[data-close-dialog]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeDialog(button.getAttribute('data-close-dialog'));
            });
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            document.querySelectorAll('[role="dialog"].flex').forEach(function (dialog) {
                closeDialog(dialog.id);
            });
        });

        const input = document.getElementById('gallery-photos');
        input?.addEventListener('change', function () {
            const files = Array.from(input.files || []).slice(0, 12);
            const label = document.getElementById('gallery-photo-name');
            const previews = document.getElementById('gallery-photo-previews');
            if (!label || !previews) return;
            label.textContent = files.length === 0
                ? 'JPG, PNG, or WEBP. Up to 12.'
                : files.length + (files.length === 1 ? ' picture selected' : ' pictures selected');
            previews.replaceChildren();
            files.forEach(function (file) {
                const image = document.createElement('img');
                image.alt = '';
                image.className = 'aspect-square w-full rounded-xl object-cover';
                previews.appendChild(image);
                const reader = new FileReader();
                reader.onload = function () {
                    image.src = reader.result;
                };
                reader.readAsDataURL(file);
            });
        });
    })();
</script>
