const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p/';

const IMAGE_SIZES = {
    backdrop: ['w300', 'w780', 'w1280', 'original'],
    poster: ['w92', 'w154', 'w185', 'w342', 'w500', 'w780', 'original'],
    logo: ['w45', 'w92', 'w154', 'w185', 'w300', 'w500', 'original'],
};

export function initTMDB() {
    const searchInput = document.getElementById('movieSearch');
    const moviesGrid = document.getElementById('moviesGrid');
    const loading = document.getElementById('loading');
    const emptyState = document.getElementById('emptyState');
    const initialState = document.getElementById('initialState');
    const modal = document.getElementById('imageModal');

    const filterBtns = document.querySelectorAll('.filter-btn');
    let currentResults = [];
    let activeFilter = 'all';

    window.closeModal = closeModal;

    if (! searchInput) return;

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => {
                b.classList.remove('bg-white', 'text-black', 'shadow-[0_0_15px_rgba(255,255,255,0.3)]');
                b.classList.add('bg-neutral-800/80', 'text-white');
            });
            btn.classList.remove('bg-neutral-800/80', 'text-white');
            btn.classList.add('bg-white', 'text-black', 'shadow-[0_0_15px_rgba(255,255,255,0.3)]');
            activeFilter = btn.dataset.filter;
            renderMovies(currentResults);
        });
    });

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    }

    const handleSearch = async (e) => {
        const query = e.target.value.trim();

        if (query.length < 2) {
            moviesGrid.innerHTML = '';
            initialState.classList.remove('hidden');
            emptyState.classList.add('hidden');
            return;
        }

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
        } catch (error) {
            console.error('Hata:', error);
        } finally {
            loading.classList.add('hidden');
            moviesGrid.style.opacity = '1';
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
    };

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
        return `${TMDB_IMAGE_BASE}${size}${filePath}`;
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
            { key: 'backdrops', label: 'Backdrop', count: counts.backdrops },
            { key: 'posters', label: 'Poster', count: counts.posters },
            { key: 'logos', label: 'Logo', count: counts.logos },
        ].filter(t => t.count > 0);

        const currentImages = images[modalState.activeTab] || [];
        const sizes = getAvailableSizes();
        const selectedImg = modalState.selectedImage;
        const previewUrl = selectedImg ? getImageUrl(selectedImg.file_path, modalState.selectedSize) : '';
        const thumbSize = modalState.activeTab === 'posters' ? 'w185' : 'w300';

        modalContent.innerHTML = `
            <!-- Header -->
            <div class="p-4 md:p-6 border-b border-white/5 flex items-center justify-between shrink-0">
                <div>
                    <h2 class="text-lg md:text-xl font-bold text-white">${modalState.movie.title}</h2>
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
                <div class="text-xs text-neutral-400 space-y-1">
                    <div class="flex gap-2"><span class="font-bold text-white">BOYUT:</span> <span id="modalFooterDim">${selectedImg ? selectedImg.width + ' x ' + selectedImg.height : '...'}</span></div>
                    <div class="flex gap-2"><span class="font-bold text-white">FORMAT:</span> <span>WebP</span></div>
                </div>
                <button id="downloadBtn" class="px-6 py-2.5 bg-white text-black font-bold rounded-lg hover:bg-fuchsia-500 hover:text-white transition-colors text-sm flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>WebP İndir</span>
                </button>
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
                // Reset size to original for new tab
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

        // Download event
        const downloadBtn = document.getElementById('downloadBtn');
        if (downloadBtn && selectedImg) {
            downloadBtn.addEventListener('click', () => downloadAsWebP(selectedImg, modalState.selectedSize, modalState.movie.title));
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
        }, 300);
    }

    // WebP download via canvas
    async function downloadAsWebP(imageData, size, title) {
        const downloadBtn = document.getElementById('downloadBtn');
        const originalHTML = downloadBtn.innerHTML;
        downloadBtn.disabled = true;
        downloadBtn.innerHTML = `
            <div class="w-4 h-4 border-2 border-neutral-400 border-t-black rounded-full animate-spin"></div>
            <span>İndiriliyor...</span>
        `;

        try {
            const imageUrl = getImageUrl(imageData.file_path, size);
            const response = await fetch(imageUrl);
            const blob = await response.blob();

            const img = new Image();
            img.crossOrigin = 'anonymous';

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

            const webpBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', 0.92));

            const url = URL.createObjectURL(webpBlob);
            const a = document.createElement('a');
            const safeName = title.replace(/[^a-zA-Z0-9\u00C0-\u024F\u0400-\u04FF\u0600-\u06FF\u4E00-\u9FFF ]/g, '').replace(/\s+/g, '_');
            a.href = url;
            a.download = `${safeName}_${size}.webp`;
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
        if (e.key === 'Escape' && ! modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    searchInput.addEventListener('input', debounce(handleSearch, 500));
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

        let scrollPos = 0;
        let speed = 0.5;
        let isPaused = false;
        let animationId;

        function animate() {
            if (! isPaused) {
                scrollPos += speed;
                // Reset when first set is fully scrolled
                const halfHeight = list.scrollHeight / 2;
                if (scrollPos >= halfHeight) {
                    scrollPos = 0;
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
