const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p/';

const IMAGE_SIZES = {
    backdrop: ['w300', 'w780', 'w1280', 'w1920', 'original'],
    poster: ['w92', 'w154', 'w185', 'w342', 'w500', 'w780', 'original'],
    logo: ['w45', 'w92', 'w154', 'w185', 'w300', 'w500', 'original'],
};

function parseSearchInput(value) {
    const trimmed = value.trim();
    let batchCount = null;
    let filmsPart = trimmed;

    const pipeMatch = trimmed.match(/\|(\d+)\s*$/);
    if (pipeMatch) {
        batchCount = parseInt(pipeMatch[1], 10);
        filmsPart = trimmed.slice(0, pipeMatch.index).trim();
    }

    const films = filmsPart
        .split(',')
        .map(f => f.trim())
        .filter(f => f.length > 0);

    return { films, batchCount };
}

export function initTMDB() {
    const searchInput = document.getElementById('movieSearch');
    const moviesGrid = document.getElementById('moviesGrid');
    const loading = document.getElementById('loading');
    const emptyState = document.getElementById('emptyState');
    const initialState = document.getElementById('initialState');
    const modal = document.getElementById('imageModal');
    const queueStatus = document.getElementById('queueStatus');
    const batchProgress = document.getElementById('batchProgress');
    const batchProgressBar = document.getElementById('batchProgressBar');
    const batchProgressText = document.getElementById('batchProgressText');

    const filterBtns = document.querySelectorAll('.filter-btn');
    let currentResults = [];
    let activeFilter = 'all';

    let searchQueue = {
        films: [],
        currentIndex: 0,
        batchCount: null,
        active: false,
    };

    window.closeModal = closeModal;

    if (! searchInput) return;

    function setActiveFilter(filter) {
        activeFilter = filter;

        filterBtns.forEach(btn => {
            btn.classList.remove('bg-white', 'text-black', 'shadow-[0_0_15px_rgba(255,255,255,0.3)]');
            btn.classList.add('bg-neutral-800/80', 'text-white');
        });

        const activeBtn = Array.from(filterBtns).find(btn => btn.dataset.filter === filter);

        if (activeBtn) {
            activeBtn.classList.remove('bg-neutral-800/80', 'text-white');
            activeBtn.classList.add('bg-white', 'text-black', 'shadow-[0_0_15px_rgba(255,255,255,0.3)]');
        }
    }

    function getSidebarItemPayload(trigger) {
        return {
            id: Number(trigger.dataset.tmdbId),
            title: trigger.dataset.tmdbTitle || '',
            overview: trigger.dataset.tmdbOverview || '',
            poster_path: trigger.dataset.tmdbPosterPath || '',
            backdrop_path: trigger.dataset.tmdbBackdropPath || '',
            vote_average: Number(trigger.dataset.tmdbVoteAverage || 0),
            release_date: trigger.dataset.tmdbReleaseDate || null,
            type: trigger.dataset.tmdbType || '',
            raw_type: trigger.dataset.tmdbRawType || 'movie',
        };
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            setActiveFilter(btn.dataset.filter);
            renderMovies(currentResults);
        });
    });

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-sidebar-item]');

        if (! trigger) {
            return;
        }

        event.preventDefault();

        const selectedItem = getSidebarItemPayload(trigger);

        currentResults = [selectedItem];
        searchInput.value = selectedItem.title;
        initialState.classList.add('hidden');
        emptyState.classList.add('hidden');
        setActiveFilter(selectedItem.raw_type);
        renderMovies(currentResults);
        moviesGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });

        if (window.innerWidth < 768) {
            document.getElementById('sidebar')?.classList.add('-translate-x-full');
        }

        openModal(selectedItem);
    });

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    }

    function updateQueueStatus() {
        if (! queueStatus) return;

        if (! searchQueue.active || searchQueue.films.length <= 1) {
            queueStatus.classList.add('hidden');
            return;
        }

        const current = searchQueue.films[searchQueue.currentIndex] || '';
        const next = searchQueue.currentIndex + 1 < searchQueue.films.length
            ? searchQueue.films[searchQueue.currentIndex + 1]
            : null;

        let html = `<span class="text-fuchsia-400 font-bold">Film ${searchQueue.currentIndex + 1}/${searchQueue.films.length}:</span> ${current}`;
        if (next) {
            html += ` <span class="text-neutral-600 mx-1">|</span> <span class="text-neutral-500">Sıradaki: ${next}</span>`;
        }
        if (searchQueue.batchCount) {
            html += ` <span class="text-neutral-600 mx-1">|</span> <span class="text-purple-400">Batch: ${searchQueue.batchCount}</span>`;
        }

        queueStatus.innerHTML = html;
        queueStatus.classList.remove('hidden');
    }

    function showBatchProgress(current, total, filmName) {
        if (! batchProgress) return;
        const percent = Math.round((current / total) * 100);
        batchProgressBar.style.width = `${percent}%`;
        batchProgressText.textContent = `İndiriliyor: ${current}/${total} — ${filmName} (Film ${searchQueue.currentIndex + 1}/${searchQueue.films.length})`;
        batchProgress.classList.remove('hidden');
    }

    function hideBatchProgress() {
        if (batchProgress) batchProgress.classList.add('hidden');
    }

    function resetQueue() {
        searchQueue = { films: [], currentIndex: 0, batchCount: null, active: false };
        if (queueStatus) queueStatus.classList.add('hidden');
        hideBatchProgress();
    }

    async function searchFilm(query) {
        loading.classList.remove('hidden');
        initialState.classList.add('hidden');
        emptyState.classList.add('hidden');
        moviesGrid.style.opacity = '0.5';

        try {
            const response = await fetch(`/search?query=${encodeURIComponent(query)}`);
            if (! response.ok) throw new Error('API Hatası');

            const data = await response.json();
            currentResults = data.results;
            renderMovies(currentResults);
            return data.results;
        } catch (error) {
            console.error('Hata:', error);
            return [];
        } finally {
            loading.classList.add('hidden');
            moviesGrid.style.opacity = '1';
        }
    }

    async function processNextInQueue() {
        if (! searchQueue.active) return;
        if (searchQueue.currentIndex >= searchQueue.films.length) {
            resetQueue();
            return;
        }

        const filmName = searchQueue.films[searchQueue.currentIndex];
        updateQueueStatus();

        const results = await searchFilm(filmName);

        if (searchQueue.batchCount && searchQueue.batchCount > 0) {
            if (results.length > 0) {
                const movie = results[0];
                try {
                    const response = await fetch(`/images/${movie.raw_type}/${movie.id}`);
                    if (! response.ok) throw new Error('API Hatası');

                    const images = await response.json();
                    if (images.backdrops && images.backdrops.length > 0) {
                        await batchDownloadBackdrops(movie, images.backdrops, searchQueue.batchCount);
                    }
                } catch (error) {
                    console.error('Batch indirme hatası:', error);
                }
            }

            searchQueue.currentIndex++;
            processNextInQueue();
        }
    }

    async function batchDownloadBackdrops(movie, backdrops, count) {
        const total = Math.min(count, backdrops.length);
        const title = movie.title;
        const format = modalState.selectedFormat;
        const { mimeType, quality, ext } = getFormatInfo(format);

        for (let i = 0; i < total; i++) {
            showBatchProgress(i + 1, total, title);

            try {
                const proxyUrl = `/proxy-image?path=${encodeURIComponent(backdrops[i].file_path)}&size=original`;
                const response = await fetch(proxyUrl);
                const blob = await response.blob();

                const img = new Image();

                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    img.src = URL.createObjectURL(blob);
                });

                const canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                URL.revokeObjectURL(img.src);

                const outputBlob = await new Promise(resolve => canvas.toBlob(resolve, mimeType, quality));
                const url = URL.createObjectURL(outputBlob);
                const a = document.createElement('a');
                const safeName = title.replace(/[^a-zA-Z0-9\u00C0-\u024F\u0400-\u04FF\u0600-\u06FF\u4E00-\u9FFF ]/g, '').replace(/\s+/g, '_');
                a.href = url;
                a.download = `${safeName}_banner_${i + 1}.${ext}`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch (error) {
                console.error(`Banner ${i + 1} indirme hatası:`, error);
            }

            if (i < total - 1) {
                await new Promise(resolve => setTimeout(resolve, 500));
            }
        }

        hideBatchProgress();
    }

    const handleSearch = async (e) => {
        const rawValue = e.target.value.trim();

        if (rawValue.length < 2) {
            moviesGrid.innerHTML = '';
            initialState.classList.remove('hidden');
            emptyState.classList.add('hidden');
            resetQueue();
            return;
        }

        const parsed = parseSearchInput(rawValue);

        if (parsed.films.length > 1) {
            searchQueue.films = parsed.films;
            searchQueue.currentIndex = 0;
            searchQueue.batchCount = parsed.batchCount;
            searchQueue.active = true;
            processNextInQueue();
        } else {
            resetQueue();
            searchFilm(parsed.films[0] || rawValue);
        }
    };

    function renderMovies(movies) {
        moviesGrid.innerHTML = '';

        let filtered = movies;
        if (activeFilter !== 'all') {
            filtered = movies.filter(m => m.raw_type === activeFilter);
        }

        if (! filtered || filtered.length === 0) {
            if (movies.length > 0) {
                moviesGrid.innerHTML = '<div class="col-span-full text-center text-neutral-500 py-10">Bu kategoride sonuç bulunamadı.</div>';
            } else {
                emptyState.classList.remove('hidden');
            }
            return;
        }

        emptyState.classList.add('hidden');

        filtered.forEach(movie => {
            const thumbUrl = `${TMDB_IMAGE_BASE}w780${movie.backdrop_path}`;

            const card = document.createElement('div');
            card.className = 'group relative bg-neutral-900 rounded-xl overflow-hidden cursor-pointer border border-neutral-800 hover:border-fuchsia-500/50 transition-all duration-300 hover:shadow-[0_0_30px_rgba(217,70,239,0.15)] hover:-translate-y-1 flex flex-col';
            card.onclick = () => openModal(movie);

            const imgId = 'img-' + Math.random().toString(36).slice(2, 11);
            const dimId = 'dim-' + imgId;

            card.innerHTML = `
                <div class="aspect-video w-full overflow-hidden bg-neutral-950 relative">
                    <img id="${imgId}" src="${thumbUrl}" alt="${movie.title}"
                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                    <div class="absolute top-2 right-2 bg-black/60 backdrop-blur-md px-2 py-1 rounded text-[10px] font-bold text-white border border-white/10">
                        ${movie.type}
                    </div>
                </div>
                <div class="p-4 flex flex-col gap-2">
                    <h3 class="text-white font-bold text-base leading-tight truncate group-hover:text-fuchsia-400 transition-colors">${movie.title}</h3>
                    <div class="flex items-center justify-between text-xs text-neutral-500 mt-auto">
                        <span id="${dimId}" class="font-mono bg-neutral-800 px-1.5 py-0.5 rounded text-[10px]">...</span>
                        <span>${movie.release_date ? movie.release_date.split('-')[0] : ''}</span>
                    </div>
                </div>
            `;

            moviesGrid.appendChild(card);

            const imgEl = document.getElementById(imgId);
            imgEl.onload = function () {
                const dimEl = document.getElementById(dimId);
                if (dimEl) {
                    dimEl.textContent = `${this.naturalWidth} x ${this.naturalHeight}`;
                    dimEl.classList.add('text-fuchsia-500/80');
                }
            };
        });
    }

    // --- MODAL ---
    let modalState = {
        movie: null,
        images: { backdrops: [], posters: [], logos: [] },
        activeTab: 'backdrops',
        selectedImage: null,
        selectedSize: 'original',
        selectedFormat: localStorage.getItem('downloadFormat') || 'webp',
    };

    function getFormatInfo(format) {
        switch (format) {
            case 'png':
                return { mimeType: 'image/png', quality: undefined, ext: 'png' };
            case 'jpg':
                return { mimeType: 'image/jpeg', quality: 0.92, ext: 'jpg' };
            default:
                return { mimeType: 'image/webp', quality: 0.92, ext: 'webp' };
        }
    }

    async function openModal(movie) {
        modalState.movie = movie;
        modalState.activeTab = 'backdrops';
        modalState.selectedSize = 'original';
        modalState.selectedImage = null;

        const modalContent = document.getElementById('modalContent');
        modalContent.innerHTML = `
            <div class="flex items-center justify-center py-20">
                <div class="w-10 h-10 border-4 border-neutral-800 border-t-fuchsia-500 rounded-full animate-spin"></div>
            </div>
        `;

        showModal();

        try {
            const response = await fetch(`/images/${movie.raw_type}/${movie.id}`);
            if (! response.ok) throw new Error('API Hatası');

            modalState.images = await response.json();

            if (modalState.images.backdrops.length > 0) {
                modalState.selectedImage = modalState.images.backdrops[0];
            } else if (modalState.images.posters.length > 0) {
                modalState.activeTab = 'posters';
                modalState.selectedImage = modalState.images.posters[0];
            } else if (modalState.images.logos.length > 0) {
                modalState.activeTab = 'logos';
                modalState.selectedImage = modalState.images.logos[0];
            }

            renderModal();
        } catch (error) {
            console.error('Resim yükleme hatası:', error);
            modalContent.innerHTML = `<div class="text-center py-20 text-neutral-500">Resimler yüklenemedi.</div>`;
        }
    }

    function getImageTypeKey() {
        if (modalState.activeTab === 'backdrops') return 'backdrop';
        if (modalState.activeTab === 'posters') return 'poster';
        return 'logo';
    }

    function getAvailableSizes() {
        return IMAGE_SIZES[getImageTypeKey()] || [];
    }

    function getSizeLabel(size) {
        if (size === 'original') return 'Original';
        return size.replace('w', '') + 'px';
    }

    function getImageUrl(filePath, size) {
        const tmdbSize = size === 'w1920' ? 'original' : size;
        return `${TMDB_IMAGE_BASE}${tmdbSize}${filePath}`;
    }

    function renderModal() {
        const modalContent = document.getElementById('modalContent');
        const images = modalState.images;
        const counts = {
            backdrops: images.backdrops.length,
            posters: images.posters.length,
            logos: images.logos.length,
        };

        const tabs = [
            { key: 'backdrops', label: 'Banner', count: counts.backdrops },
            { key: 'posters', label: 'Poster', count: counts.posters },
            { key: 'logos', label: 'Logo', count: counts.logos },
        ].filter(t => t.count > 0);

        const currentImages = images[modalState.activeTab] || [];
        const sizes = getAvailableSizes();
        const selectedImg = modalState.selectedImage;
        const previewUrl = selectedImg ? getImageUrl(selectedImg.file_path, modalState.selectedSize) : '';
        const thumbSize = modalState.activeTab === 'posters' ? 'w185' : 'w300';

        const queueInfo = searchQueue.active && searchQueue.films.length > 1
            ? `<span class="inline-block ml-2 px-2 py-0.5 rounded-full bg-fuchsia-600/20 text-fuchsia-400 text-[10px] font-bold border border-fuchsia-500/30">${searchQueue.currentIndex + 1}/${searchQueue.films.length}</span>`
            : '';

        const formatBtns = ['webp', 'png', 'jpg'].map(f => `
            <button data-format="${f}" class="format-btn px-2.5 py-0.5 rounded text-[11px] font-mono font-bold transition-all cursor-pointer ${modalState.selectedFormat === f ? 'bg-fuchsia-600 text-white' : 'bg-neutral-800 text-neutral-400 hover:bg-neutral-700 border border-white/5'}">
                ${f.toUpperCase()}
            </button>
        `).join('');

        modalContent.innerHTML = `
            <!-- Header -->
            <div class="p-4 md:p-6 border-b border-white/5 flex items-center justify-between shrink-0">
                <div>
                    <h2 class="text-lg md:text-xl font-bold text-white">${modalState.movie.title}${queueInfo}</h2>
                    <p class="text-xs text-neutral-500 mt-1">${modalState.movie.type} &middot; ${modalState.movie.release_date ? modalState.movie.release_date.split('-')[0] : ''}</p>
                </div>
                <button onclick="closeModal()" class="p-2 bg-neutral-800 rounded-full hover:bg-fuchsia-600 text-white transition-colors shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Tabs -->
            <div class="px-4 md:px-6 pt-4 flex items-center gap-2 shrink-0">
                ${tabs.map(t => `
                    <button data-tab="${t.key}" class="modal-tab px-4 py-1.5 rounded-full text-xs font-bold transition-all ${modalState.activeTab === t.key ? 'bg-fuchsia-600 text-white' : 'bg-neutral-800 text-neutral-400 hover:bg-neutral-700'}">
                        ${t.label} <span class="ml-1 opacity-60">${t.count}</span>
                    </button>
                `).join('')}
            </div>

            <!-- Resolution Filter -->
            <div class="px-4 md:px-6 pt-3 flex items-center gap-2 flex-wrap shrink-0">
                <span class="text-[10px] uppercase tracking-widest text-neutral-600 font-bold mr-1">Çözünürlük:</span>
                ${sizes.map(s => `
                    <button data-size="${s}" class="size-btn px-3 py-1 rounded-full text-[11px] font-mono font-bold transition-all ${modalState.selectedSize === s ? 'bg-white text-black shadow-[0_0_10px_rgba(255,255,255,0.2)]' : 'bg-neutral-800/80 text-neutral-400 hover:bg-neutral-700 border border-white/5'}">
                        ${getSizeLabel(s)}
                    </button>
                `).join('')}
            </div>

            <!-- Content: Preview + Thumbnails -->
            <div class="flex-1 flex flex-col md:flex-row overflow-hidden min-h-0 p-4 md:p-6 gap-4">
                <!-- Preview -->
                <div class="flex-1 flex flex-col items-center justify-center bg-black/50 rounded-xl overflow-hidden relative min-h-[200px]">
                    ${selectedImg ? `
                        <img id="previewImage" src="${previewUrl}" alt="Preview"
                            class="max-w-full max-h-[55vh] object-contain">
                        <div id="previewDimensions" class="absolute bottom-3 left-3 bg-black/70 backdrop-blur-sm px-3 py-1.5 rounded-lg text-xs font-mono text-fuchsia-400 border border-white/10">
                            ...
                        </div>
                    ` : '<p class="text-neutral-600">Bir resim seçin</p>'}
                </div>

                <!-- Thumbnails -->
                <div class="w-full md:w-56 shrink-0 overflow-y-auto scrollbar-hide">
                    <div class="${modalState.activeTab === 'posters' ? 'grid grid-cols-3 md:grid-cols-2 gap-2' : 'flex flex-row md:flex-col gap-2 overflow-x-auto md:overflow-x-visible'}">
                        ${currentImages.map((img, i) => `
                            <div data-thumb-index="${i}"
                                class="thumb-item cursor-pointer rounded-lg overflow-hidden border-2 transition-all shrink-0 ${selectedImg && selectedImg.file_path === img.file_path ? 'border-fuchsia-500 shadow-[0_0_10px_rgba(217,70,239,0.3)]' : 'border-transparent hover:border-white/20'}">
                                <img src="${getImageUrl(img.file_path, thumbSize)}" alt="Thumb ${i + 1}"
                                    class="w-full h-auto object-cover bg-neutral-900" loading="lazy">
                                <div class="bg-neutral-900 px-1.5 py-1 text-[9px] font-mono text-neutral-500 text-center">
                                    ${img.width}x${img.height}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 md:p-6 border-t border-white/5 flex items-center justify-between shrink-0">
                <div class="text-xs text-neutral-400 space-y-2">
                    <div class="flex gap-2"><span class="font-bold text-white">BOYUT:</span> <span id="modalFooterDim">${selectedImg ? selectedImg.width + ' x ' + selectedImg.height : '...'}</span></div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-white">FORMAT:</span>
                        ${formatBtns}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="generateQuotesBtn" class="px-5 py-2.5 bg-linear-to-r from-purple-600 to-fuchsia-600 text-white font-bold rounded-lg hover:from-purple-500 hover:to-fuchsia-500 transition-all text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                        <span>AI Sözleri</span>
                    </button>
                    <button id="downloadBtn" class="px-6 py-2.5 bg-white text-black font-bold rounded-lg hover:bg-fuchsia-500 hover:text-white transition-colors text-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>${modalState.selectedFormat.toUpperCase()} İndir</span>
                    </button>
                </div>
            </div>
        `;

        // Preview image load → show real dimensions
        const previewImg = document.getElementById('previewImage');
        if (previewImg) {
            previewImg.onload = function () {
                const dimEl = document.getElementById('previewDimensions');
                if (dimEl) dimEl.textContent = `${this.naturalWidth} x ${this.naturalHeight}`;
            };
        }

        // Tab events
        modalContent.querySelectorAll('.modal-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                modalState.activeTab = btn.dataset.tab;
                const imgs = modalState.images[modalState.activeTab] || [];
                modalState.selectedImage = imgs.length > 0 ? imgs[0] : null;
                modalState.selectedSize = 'original';
                renderModal();
            });
        });

        // Size events
        modalContent.querySelectorAll('.size-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                modalState.selectedSize = btn.dataset.size;
                renderModal();
            });
        });

        // Thumbnail events
        modalContent.querySelectorAll('.thumb-item').forEach(el => {
            el.addEventListener('click', () => {
                const idx = parseInt(el.dataset.thumbIndex);
                const imgs = modalState.images[modalState.activeTab] || [];
                modalState.selectedImage = imgs[idx];
                renderModal();
            });
        });

        // Format events
        modalContent.querySelectorAll('.format-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                modalState.selectedFormat = btn.dataset.format;
                localStorage.setItem('downloadFormat', modalState.selectedFormat);
                renderModal();
            });
        });

        // Download event
        const downloadBtn = document.getElementById('downloadBtn');
        if (downloadBtn && selectedImg) {
            downloadBtn.addEventListener('click', () => downloadImage(selectedImg, modalState.selectedSize, modalState.movie.title));
        }

        // Generate Quotes event
        const generateQuotesBtn = document.getElementById('generateQuotesBtn');
        if (generateQuotesBtn) {
            generateQuotesBtn.addEventListener('click', () => {
                openQuotesModal(modalState.movie, selectedImg);
            });
        }
    }

    function showModal() {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.querySelector('.modal-backdrop').classList.remove('opacity-0');
            document.getElementById('modalContainer').classList.remove('scale-95', 'opacity-0');
            document.getElementById('modalContainer').classList.add('scale-100', 'opacity-100');
        });
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modalContainer = document.getElementById('modalContainer');
        modalContainer.classList.remove('scale-100', 'opacity-100');
        modalContainer.classList.add('scale-95', 'opacity-0');
        modal.querySelector('.modal-backdrop').classList.add('opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';

            // Queue'da sıradaki filme geç (sadece manuel mod)
            if (searchQueue.active && ! searchQueue.batchCount) {
                searchQueue.currentIndex++;
                processNextInQueue();
            }
        }, 300);
    }

    // --- QUOTES MODAL ---
    const quotesModal = document.getElementById('quotesModal');

    let quotesState = {
        movie: null,
        bannerImage: null,
        quotes: [],
        loading: false,
        error: null,
        model: '',
        allMovies: [],
        currentMovieIndex: 0,
        quotesCache: {},
    };

    window.closeQuotesModal = closeQuotesModal;

    async function openQuotesModal(movie, selectedImg, { regenerate = false, style = '', fromNavigation = false } = {}) {
        if (! fromNavigation && ! regenerate) {
            const movieIndex = currentResults.findIndex(m => m.id === movie.id && m.raw_type === movie.raw_type);
            if (movieIndex !== -1 && currentResults.length > 1) {
                quotesState.allMovies = [...currentResults];
                quotesState.currentMovieIndex = movieIndex;
            } else {
                quotesState.allMovies = [movie];
                quotesState.currentMovieIndex = 0;
            }
            quotesState.quotesCache = {};
        }

        const cacheKey = `${movie.raw_type}_${movie.id}`;

        if (! regenerate && quotesState.quotesCache[cacheKey]) {
            const cached = quotesState.quotesCache[cacheKey];
            quotesState.movie = movie;
            quotesState.bannerImage = cached.bannerImage;
            quotesState.quotes = cached.quotes;
            quotesState.model = cached.model;
            quotesState.loading = false;
            quotesState.error = null;
            if (! fromNavigation) showQuotesModal();
            renderQuotesModal();
            return;
        }

        quotesState.movie = movie;
        quotesState.bannerImage = selectedImg;
        quotesState.quotes = [];
        quotesState.loading = true;
        quotesState.error = null;
        quotesState.model = '';

        if (! fromNavigation) showQuotesModal();
        renderQuotesModal();

        const generatingFor = cacheKey;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const response = await fetch('/generate-quotes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    id: movie.id,
                    title: movie.title,
                    overview: movie.overview || '',
                    type: movie.raw_type,
                    ...(regenerate ? { regenerate: true } : {}),
                    ...(style ? { style } : {}),
                }),
            });

            if (! response.ok) {
                const errorData = await response.json().catch(() => ({}));
                if (errorData.debug) {
                    console.error('API Debug:', errorData.debug);
                }
                throw new Error(errorData.error || 'API Hatası');
            }

            const data = await response.json();
            const quotes = data.quotes || [];
            const model = data.model || '';

            if (quotes.length > 0) {
                quotesState.quotesCache[generatingFor] = { quotes, model, bannerImage: selectedImg };
            }

            const currentKey = `${quotesState.movie.raw_type}_${quotesState.movie.id}`;
            if (generatingFor !== currentKey) return;

            quotesState.quotes = quotes;
            quotesState.model = model;
            quotesState.loading = false;

            if (quotes.length === 0) {
                quotesState.error = 'Söz üretilemedi. Lütfen tekrar deneyin.';
            }
        } catch (error) {
            const currentKey = `${quotesState.movie.raw_type}_${quotesState.movie.id}`;
            if (generatingFor !== currentKey) return;

            console.error('Söz üretme hatası:', error);
            quotesState.loading = false;
            quotesState.error = error.message || 'Sözler üretilemedi. Lütfen tekrar deneyin.';
        }

        renderQuotesModal();
    }

    function navigateQuotesMovie(direction) {
        const newIndex = quotesState.currentMovieIndex + direction;
        if (newIndex < 0 || newIndex >= quotesState.allMovies.length) return;

        quotesState.currentMovieIndex = newIndex;
        const movie = quotesState.allMovies[newIndex];
        openQuotesModal(movie, null, { fromNavigation: true });
    }

    function renderQuotesModal() {
        const quotesContent = document.getElementById('quotesContent');
        const movie = quotesState.movie;
        const bannerImg = quotesState.bannerImage;
        const bannerUrl = bannerImg ? getImageUrl(bannerImg.file_path, 'w1280') : (movie.backdrop_path ? `${TMDB_IMAGE_BASE}w1280${movie.backdrop_path}` : '');
        const hasNextFilm = searchQueue.active && ! searchQueue.batchCount && searchQueue.currentIndex < searchQueue.films.length - 1;
        const nextFilmName = hasNextFilm ? searchQueue.films[searchQueue.currentIndex + 1] : '';

        let contentHTML = '';

        if (quotesState.loading) {
            contentHTML = `
                <div class="flex-1 flex flex-col md:flex-row overflow-hidden min-h-0 p-4 md:p-6 gap-4">
                    <div class="flex-1 flex items-center justify-center bg-black/50 rounded-xl overflow-hidden min-h-[200px]">
                        ${bannerUrl ? `<img src="${bannerUrl}" alt="${movie.title}" class="max-w-full max-h-[50vh] object-contain opacity-50">` : ''}
                    </div>
                    <div class="w-full md:w-80 shrink-0 flex flex-col items-center justify-center gap-4">
                        <div class="w-12 h-12 border-4 border-neutral-800 border-t-purple-500 rounded-full animate-spin"></div>
                        <p class="text-neutral-400 text-sm">AI sözler üretiyor...</p>
                        <p class="text-neutral-600 text-xs">Bu işlem birkaç saniye sürebilir</p>
                    </div>
                </div>
            `;
        } else if (quotesState.error) {
            contentHTML = `
                <div class="flex-1 flex flex-col md:flex-row overflow-hidden min-h-0 p-4 md:p-6 gap-4">
                    <div class="flex-1 flex items-center justify-center bg-black/50 rounded-xl overflow-hidden min-h-[200px]">
                        ${bannerUrl ? `<img src="${bannerUrl}" alt="${movie.title}" class="max-w-full max-h-[50vh] object-contain opacity-50">` : ''}
                    </div>
                    <div class="w-full md:w-80 shrink-0 flex flex-col items-center justify-center gap-4">
                        <svg class="w-12 h-12 text-red-500/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <p class="text-neutral-400 text-sm text-center">${quotesState.error}</p>
                        <button id="retryQuotesBtn" class="px-5 py-2 bg-purple-600 text-white font-bold rounded-lg hover:bg-purple-500 transition-colors text-sm">
                            Tekrar Dene
                        </button>
                    </div>
                </div>
            `;
        } else {
            const quotesListHTML = quotesState.quotes.map((quote, i) => `
                <div data-quote-index="${i}" class="quote-card group flex items-start gap-3 p-4 bg-neutral-800/50 rounded-xl border border-white/5 hover:border-purple-500/30 transition-all cursor-pointer active:scale-[0.98]">
                    <span class="text-purple-500/50 font-mono text-xs mt-1 shrink-0">${String(i + 1).padStart(2, '0')}</span>
                    <p class="flex-1 text-white text-sm leading-relaxed">${quote}</p>
                    <div class="copy-icon shrink-0 p-2 rounded-lg bg-neutral-700/50 text-neutral-400 transition-all opacity-0 group-hover:opacity-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
            `).join('');

            contentHTML = `
                <div class="flex-1 flex flex-col md:flex-row overflow-hidden min-h-0 p-4 md:p-6 gap-4">
                    <!-- Banner Preview -->
                    <div class="md:flex-1 flex items-center justify-center bg-black/50 rounded-xl overflow-hidden">
                        ${bannerUrl ? `<img src="${bannerUrl}" alt="${movie.title}" class="max-w-full max-h-full object-contain">` : '<p class="text-neutral-600 p-8">Görsel bulunamadı</p>'}
                    </div>

                    <!-- Quotes List -->
                    <div class="w-full md:w-80 shrink-0 flex flex-col gap-3 overflow-y-auto min-h-0 scrollbar-hide">
                        ${quotesListHTML}
                    </div>
                </div>
            `;
        }

        quotesContent.innerHTML = `
            <!-- Header -->
            <div class="p-4 md:p-6 border-b border-white/5 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    ${quotesState.allMovies.length > 1 ? `
                        <div class="flex items-center gap-1 shrink-0">
                            <button id="prevMovieBtn" class="p-1.5 rounded-lg bg-neutral-800 text-white hover:bg-purple-600 transition-colors disabled:opacity-30 disabled:cursor-not-allowed" ${quotesState.currentMovieIndex === 0 ? 'disabled' : ''}>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </button>
                            <span class="text-xs text-neutral-500 font-mono px-1">${quotesState.currentMovieIndex + 1}/${quotesState.allMovies.length}</span>
                            <button id="nextMovieBtn" class="p-1.5 rounded-lg bg-neutral-800 text-white hover:bg-purple-600 transition-colors disabled:opacity-30 disabled:cursor-not-allowed" ${quotesState.currentMovieIndex >= quotesState.allMovies.length - 1 ? 'disabled' : ''}>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                    ` : ''}
                    <div class="min-w-0">
                        <h2 class="text-lg md:text-xl font-bold text-white flex items-center gap-2">
                            <span class="truncate">${movie.title}</span>
                            <span class="inline-block px-2 py-0.5 rounded-full bg-purple-600/20 text-purple-400 text-[10px] font-bold border border-purple-500/30 shrink-0">AI Sözleri</span>
                        </h2>
                        <p class="text-xs text-neutral-500 mt-1">${movie.type} &middot; ${movie.release_date ? movie.release_date.split('-')[0] : ''}</p>
                    </div>
                </div>
                <button onclick="closeQuotesModal()" class="p-2 bg-neutral-800 rounded-full hover:bg-purple-600 text-white transition-colors shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            ${contentHTML}

            <!-- Regenerate Bar -->
            ${!quotesState.loading ? `
            <div class="px-4 md:px-6 pt-3 border-t border-white/5 shrink-0">
                <div class="flex items-center gap-2">
                    <input type="text" id="styleInput" class="flex-1 bg-neutral-800/80 border border-white/10 rounded-lg px-4 py-2 text-sm text-white placeholder-neutral-500 focus:outline-none focus:border-purple-500 transition-colors" placeholder="Stil belirtin (ör: daha dramatik, gizemli, epik...)">
                    <button id="regenerateBtn" class="px-4 py-2 bg-purple-600 text-white font-bold rounded-lg hover:bg-purple-500 transition-colors text-sm flex items-center gap-2 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>Tekrar Üret</span>
                    </button>
                </div>
            </div>
            ` : ''}

            <!-- Footer -->
            <div class="p-4 md:p-6 ${!quotesState.loading ? '' : 'border-t border-white/5'} flex items-center justify-between shrink-0">
                <div class="text-xs text-neutral-400">
                    ${quotesState.quotes.length > 0 ? `<span class="text-purple-400 font-bold">${quotesState.quotes.length}</span> söz üretildi ${quotesState.model ? `<span class="text-neutral-600 ml-1">· ${quotesState.model}</span>` : ''}` : ''}
                </div>
                <div class="flex items-center gap-2">
                    ${quotesState.quotes.length > 0 ? `
                        <button id="copyAllBtn" class="px-5 py-2.5 bg-neutral-800 text-white font-bold rounded-lg hover:bg-purple-600 transition-colors text-sm flex items-center gap-2 border border-white/10">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            <span>Tümünü Kopyala</span>
                        </button>
                    ` : ''}
                    ${bannerImg ? `
                        <button id="quotesDownloadBtn" class="px-5 py-2.5 bg-white text-black font-bold rounded-lg hover:bg-purple-500 hover:text-white transition-colors text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            <span>Görseli İndir</span>
                        </button>
                    ` : ''}
                    ${hasNextFilm && ! quotesState.loading ? `
                        <button id="nextFilmBtn" class="px-5 py-2.5 bg-fuchsia-600 text-white font-bold rounded-lg hover:bg-fuchsia-500 transition-colors text-sm flex items-center gap-2">
                            <span>Sonraki: ${nextFilmName}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    ` : ''}
                </div>
            </div>
        `;

        // Event listeners - tüm karta tıklayınca kopyala
        quotesContent.querySelectorAll('.quote-card').forEach(card => {
            card.addEventListener('click', () => {
                const idx = parseInt(card.dataset.quoteIndex);
                const icon = card.querySelector('.copy-icon');
                copyQuote(quotesState.quotes[idx], icon);
            });
        });

        const copyAllBtn = document.getElementById('copyAllBtn');
        if (copyAllBtn) {
            copyAllBtn.addEventListener('click', () => copyAllQuotes(copyAllBtn));
        }

        const quotesDownloadBtn = document.getElementById('quotesDownloadBtn');
        if (quotesDownloadBtn && bannerImg) {
            quotesDownloadBtn.addEventListener('click', () => {
                downloadImage(bannerImg, 'original', movie.title);
            });
        }

        const retryBtn = document.getElementById('retryQuotesBtn');
        if (retryBtn) {
            retryBtn.addEventListener('click', () => {
                openQuotesModal(quotesState.movie, quotesState.bannerImage, { regenerate: true, fromNavigation: true });
            });
        }

        const regenerateBtn = document.getElementById('regenerateBtn');
        if (regenerateBtn) {
            regenerateBtn.addEventListener('click', () => {
                const styleInput = document.getElementById('styleInput');
                const style = styleInput ? styleInput.value.trim() : '';
                openQuotesModal(quotesState.movie, quotesState.bannerImage, { regenerate: true, style, fromNavigation: true });
            });
        }

        const styleInput = document.getElementById('styleInput');
        if (styleInput) {
            styleInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const style = styleInput.value.trim();
                    openQuotesModal(quotesState.movie, quotesState.bannerImage, { regenerate: true, style, fromNavigation: true });
                }
            });
        }

        const prevMovieBtn = document.getElementById('prevMovieBtn');
        if (prevMovieBtn) {
            prevMovieBtn.addEventListener('click', () => navigateQuotesMovie(-1));
        }

        const nextMovieBtn = document.getElementById('nextMovieBtn');
        if (nextMovieBtn) {
            nextMovieBtn.addEventListener('click', () => navigateQuotesMovie(1));
        }

        const nextFilmBtn = document.getElementById('nextFilmBtn');
        if (nextFilmBtn) {
            nextFilmBtn.addEventListener('click', () => {
                closeQuotesModal();
                closeModal();
            });
        }
    }

    function copyQuote(text, btn) {
        navigator.clipboard.writeText(text).then(() => {
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            btn.classList.add('opacity-100', 'bg-green-600/50');
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('opacity-100', 'bg-green-600/50');
            }, 1500);
        });
    }

    function copyAllQuotes(btn) {
        const allText = quotesState.quotes.map((q, i) => `${i + 1}. ${q}`).join('\n');
        navigator.clipboard.writeText(allText).then(() => {
            const originalHTML = btn.innerHTML;
            btn.innerHTML = `
                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>Kopyalandı!</span>
            `;
            btn.classList.add('bg-green-600/50');
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('bg-green-600/50');
            }, 1500);
        });
    }

    function showQuotesModal() {
        quotesModal.classList.remove('hidden');
        requestAnimationFrame(() => {
            quotesModal.querySelector('.quotes-backdrop').classList.remove('opacity-0');
            document.getElementById('quotesContainer').classList.remove('scale-95', 'opacity-0');
            document.getElementById('quotesContainer').classList.add('scale-100', 'opacity-100');
        });
        document.body.style.overflow = 'hidden';
    }

    function closeQuotesModal() {
        const quotesContainer = document.getElementById('quotesContainer');
        quotesContainer.classList.remove('scale-100', 'opacity-100');
        quotesContainer.classList.add('scale-95', 'opacity-0');
        quotesModal.querySelector('.quotes-backdrop').classList.add('opacity-0');

        setTimeout(() => {
            quotesModal.classList.add('hidden');
            if (modal.classList.contains('hidden')) {
                document.body.style.overflow = '';
            }
        }, 300);
    }

    quotesModal.addEventListener('click', (e) => {
        if (e.target.classList.contains('quotes-backdrop')) closeQuotesModal();
    });

    async function downloadImage(imageData, size, title) {
        const downloadBtn = document.getElementById('downloadBtn');
        const originalHTML = downloadBtn.innerHTML;
        downloadBtn.disabled = true;
        downloadBtn.innerHTML = `
            <div class="w-4 h-4 border-2 border-neutral-400 border-t-black rounded-full animate-spin"></div>
            <span>İndiriliyor...</span>
        `;

        try {
            const tmdbSize = size === 'w1920' ? 'original' : size;
            const proxyUrl = `/proxy-image?path=${encodeURIComponent(imageData.file_path)}&size=${tmdbSize}`;
            const response = await fetch(proxyUrl);
            if (! response.ok) throw new Error('Görsel alınamadı');
            const blob = await response.blob();

            const img = new Image();

            await new Promise((resolve, reject) => {
                img.onload = resolve;
                img.onerror = reject;
                img.src = URL.createObjectURL(blob);
            });

            const canvas = document.createElement('canvas');

            if (size === 'w1920') {
                const ratio = img.naturalHeight / img.naturalWidth;
                canvas.width = 1920;
                canvas.height = Math.round(1920 * ratio);
            } else {
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
            }

            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(img.src);

            const { mimeType, quality, ext } = getFormatInfo(modalState.selectedFormat);
            const outputBlob = await new Promise(resolve => canvas.toBlob(resolve, mimeType, quality));

            const url = URL.createObjectURL(outputBlob);
            const a = document.createElement('a');
            const safeName = title.replace(/[^a-zA-Z0-9\u00C0-\u024F\u0400-\u04FF\u0600-\u06FF\u4E00-\u9FFF ]/g, '').replace(/\s+/g, '_');
            a.href = url;
            a.download = `${safeName}_${size}.${ext}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch (error) {
            console.error('İndirme hatası:', error);
            alert('İndirme sırasında bir hata oluştu.');
        } finally {
            downloadBtn.disabled = false;
            downloadBtn.innerHTML = originalHTML;
        }
    }

    modal.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-backdrop')) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (! quotesModal.classList.contains('hidden')) {
                closeQuotesModal();
            } else if (! modal.classList.contains('hidden')) {
                closeModal();
            }
        }

        if (! quotesModal.classList.contains('hidden') && ! quotesState.loading && document.activeElement?.tagName !== 'INPUT') {
            if (e.key === 'ArrowLeft') navigateQuotesMovie(-1);
            if (e.key === 'ArrowRight') navigateQuotesMovie(1);
        }
    });

    searchInput.addEventListener('input', debounce(handleSearch, 500));
}

// --- SIDEBAR TOGGLE ---
export function initSidebarToggle() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const toggleArrow = document.getElementById('toggleArrow');

    if (! sidebar || ! toggleBtn) return;

    let collapsed = false;

    toggleBtn.addEventListener('click', () => {
        collapsed = ! collapsed;

        if (collapsed) {
            sidebar.style.width = '0';
            sidebar.style.minWidth = '0';
            sidebar.classList.add('overflow-hidden');
            sidebar.style.borderRightWidth = '0';
            toggleBtn.style.left = '0px';
            toggleArrow.classList.add('rotate-180');
        } else {
            sidebar.style.width = '';
            sidebar.style.minWidth = '';
            sidebar.classList.remove('overflow-hidden');
            sidebar.style.borderRightWidth = '';
            toggleBtn.style.left = '';
            toggleArrow.classList.remove('rotate-180');
        }
    });
}

// --- SIDEBAR AUTO-SCROLL ---
export function initSidebarSlider() {
    const containers = document.querySelectorAll('.sidebar-slider');

    containers.forEach(container => {
        const list = container.querySelector('.slider-track');
        if (! list || list.children.length === 0) return;

        // Clone items for seamless loop
        const items = Array.from(list.children);
        items.forEach(item => {
            const clone = item.cloneNode(true);
            list.appendChild(clone);
        });

        let speed = 0.5;
        let isPaused = false;
        let animationId;

        // Start from halfway (the cloned set) and scroll upward toward 0
        const halfHeight = list.scrollHeight / 2;
        let scrollPos = halfHeight;
        container.scrollTop = scrollPos;

        function animate() {
            if (! isPaused) {
                scrollPos -= speed;
                // Reset when we reach the top — jump back to the clone boundary
                if (scrollPos <= 0) {
                    scrollPos = halfHeight;
                }
                container.scrollTop = scrollPos;
            }
            animationId = requestAnimationFrame(animate);
        }

        container.addEventListener('mouseenter', () => { isPaused = true; });
        container.addEventListener('mouseleave', () => { isPaused = false; });
        container.addEventListener('touchstart', () => { isPaused = true; }, { passive: true });
        container.addEventListener('touchend', () => { isPaused = false; });

        animate();
    });
}
