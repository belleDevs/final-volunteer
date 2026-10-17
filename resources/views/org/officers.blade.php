@php
    $currentYear = (int) now()->year;
    $showAlbumDialog = $errors->has('year');
    $showOfficerDialog = $errors->hasAny(['name', 'position', 'photo', 'album_id']);
@endphp
<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach($officerAlbums as $album)
            @php
                $cover = $album->officers->first();
                $open = $openOfficerAlbum && (int) $openOfficerAlbum->id === (int) $album->id;
            @endphp
            <a href="{{ route('org.dashboard', ['section' => 'officers', 'year' => $album->year]) }}"
               class="group relative aspect-[4/5] overflow-hidden rounded-2xl border bg-slate-900 shadow-sm {{ $open ? 'border-jci-blue ring-2 ring-jci-blue' : 'border-slate-200 dark:border-slate-700' }}">
                @if($cover)
                    <img src="{{ asset('storage/'.$cover->photo_path) }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                @else
                    <div class="h-full w-full bg-gradient-to-br from-jci-blue to-[#0073a8]"></div>
                @endif
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-4 pb-4 pt-16">
                    <p class="text-3xl font-semibold tracking-tight text-white">{{ $album->year }}</p>
                    <p class="mt-0.5 text-xs text-white/80">
                        @if((int) $album->year === $currentYear)
                            Current year
                        @endif
                        @if((int) $album->year === $currentYear && $album->officers->isNotEmpty())
                            ·
                        @endif
                        @if($album->officers->isNotEmpty())
                            {{ $album->officers->count() }} {{ \Illuminate\Support\Str::plural('officer', $album->officers->count()) }}
                        @elseif((int) $album->year !== $currentYear)
                            Empty album
                        @endif
                    </p>
                </div>
            </a>
        @endforeach

        <button type="button" data-open-dialog="add-album-dialog" class="flex aspect-[4/5] flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-white text-center transition hover:border-jci-blue hover:bg-sky-50/60 dark:border-slate-600 dark:bg-slate-900 dark:hover:bg-slate-800">
            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-sky-50 text-jci-blue dark:bg-sky-950">
                <i class="fa-solid fa-plus"></i>
            </span>
            <span>
                <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">Add album</span>
                <span class="mt-0.5 block text-xs text-slate-400">A new year</span>
            </span>
        </button>
    </div>

    @if($openOfficerAlbum)
        <section class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $openOfficerAlbum->year }}</h2>
                    <p class="text-sm text-slate-500">{{ $openOfficerAlbum->officers->count() }} {{ \Illuminate\Support\Str::plural('officer', $openOfficerAlbum->officers->count()) }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if((int) $openOfficerAlbum->year !== $currentYear)
                        <button type="button" data-open-dialog="delete-album-dialog" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-rose-950">
                            Delete album
                        </button>
                    @endif
                    <button type="button" data-open-dialog="add-officer-dialog" class="inline-flex items-center justify-center gap-2 rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">
                        <i class="fa-solid fa-plus text-xs"></i>
                        Add officer
                    </button>
                </div>
            </div>

            @if($openOfficerAlbum->officers->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">No officers in {{ $openOfficerAlbum->year }} yet</p>
                    <p class="mt-1 text-xs text-slate-400">Add a picture, name, and position.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($openOfficerAlbum->officers as $officer)
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <img src="{{ asset('storage/'.$officer->photo_path) }}" alt="{{ $officer->name }}" class="aspect-[3/4] w-full object-cover object-top">
                            <div class="px-3 py-3 text-center">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $officer->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $officer->position }}</p>
                                <form action="{{ route('org.officers.destroy', $officer) }}" method="POST" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] font-semibold text-slate-400 hover:text-rose-600">Remove</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>

<div id="add-album-dialog" class="fixed inset-0 z-[999] items-center justify-center p-4 {{ $showAlbumDialog ? 'flex' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="add-album-title">
    <button type="button" class="absolute inset-0 bg-slate-950/50" data-close-dialog="add-album-dialog" aria-label="Close"></button>
    <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 id="add-album-title" class="text-lg font-semibold text-slate-900 dark:text-white">Add album</h3>
                <p class="mt-1 text-sm text-slate-500">Create a year for this set of officers.</p>
            </div>
            <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800" data-close-dialog="add-album-dialog" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form action="{{ route('org.officers.albums.store') }}" method="POST" class="mt-5 space-y-4">
            @csrf
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                Year
                <input name="year" type="number" required min="1990" max="2100" value="{{ old('year') }}" placeholder="{{ $currentYear + 1 }}" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-sky-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            </label>
            @error('year')
                <p class="-mt-2 text-xs text-rose-600">{{ $message }}</p>
            @enderror
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" data-close-dialog="add-album-dialog">Cancel</button>
                <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Create album</button>
            </div>
        </form>
    </div>
</div>

@if($openOfficerAlbum && (int) $openOfficerAlbum->year !== $currentYear)
    <div id="delete-album-dialog" class="fixed inset-0 z-[999] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="delete-album-title">
        <button type="button" class="absolute inset-0 bg-slate-950/50" data-close-dialog="delete-album-dialog" aria-label="Close"></button>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <h3 id="delete-album-title" class="text-lg font-semibold text-slate-900 dark:text-white">Delete {{ $openOfficerAlbum->year }}?</h3>
            <p class="mt-2 text-sm text-slate-500">
                @if($openOfficerAlbum->officers->isEmpty())
                    This removes the empty album.
                @else
                    This removes the album and its {{ $openOfficerAlbum->officers->count() }} {{ \Illuminate\Support\Str::plural('officer', $openOfficerAlbum->officers->count()) }}, including their pictures.
                @endif
            </p>
            <form action="{{ route('org.officers.albums.destroy', $openOfficerAlbum) }}" method="POST" class="mt-5 flex justify-end gap-2">
                @csrf
                @method('DELETE')
                <button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" data-close-dialog="delete-album-dialog">Cancel</button>
                <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">Delete album</button>
            </form>
        </div>
    </div>
@endif

@if($openOfficerAlbum)
    <div id="add-officer-dialog" class="fixed inset-0 z-[999] items-center justify-center p-4 {{ $showOfficerDialog ? 'flex' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="add-officer-title">
        <button type="button" class="absolute inset-0 bg-slate-950/50" data-close-dialog="add-officer-dialog" aria-label="Close"></button>
        <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="add-officer-title" class="text-lg font-semibold text-slate-900 dark:text-white">Add officer</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $openOfficerAlbum->year }} album</p>
                </div>
                <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800" data-close-dialog="add-officer-dialog" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form action="{{ route('org.officers.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="album_id" value="{{ $openOfficerAlbum->id }}">
                <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-dashed border-slate-300 p-4 transition hover:border-jci-blue dark:border-slate-600">
                    <img id="officer-photo-preview" alt="" class="hidden h-16 w-16 rounded-xl object-cover">
                    <span id="officer-photo-placeholder" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                        <i class="fa-solid fa-image"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100">Picture</span>
                        <span id="officer-photo-name" class="mt-0.5 block truncate text-xs text-slate-500">JPG, PNG, or WEBP</span>
                    </span>
                    <input id="officer-photo" name="photo" type="file" required accept="image/jpeg,image/png,image/webp" class="sr-only">
                </label>
                @error('photo')
                    <p class="-mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        Name
                        <input name="name" required maxlength="255" value="{{ old('name') }}" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-sky-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    </label>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                        Position
                        <input name="position" required maxlength="255" value="{{ old('position') }}" placeholder="President" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-jci-blue focus:outline-none focus:ring-2 focus:ring-sky-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    </label>
                </div>
                @error('name')
                    <p class="-mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                @error('position')
                    <p class="-mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" data-close-dialog="add-officer-dialog">Cancel</button>
                    <button type="submit" class="rounded-xl bg-jci-blue px-4 py-2.5 text-sm font-semibold text-white hover:bg-jci-dark">Save officer</button>
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

        const photo = document.getElementById('officer-photo');
        photo?.addEventListener('change', function () {
            const file = photo.files && photo.files[0];
            const preview = document.getElementById('officer-photo-preview');
            const placeholder = document.getElementById('officer-photo-placeholder');
            const label = document.getElementById('officer-photo-name');
            if (!file || !preview) return;
            label.textContent = file.name;
            const reader = new FileReader();
            reader.onload = function () {
                preview.src = reader.result;
                preview.classList.remove('hidden');
                placeholder?.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        });
    })();
</script>
