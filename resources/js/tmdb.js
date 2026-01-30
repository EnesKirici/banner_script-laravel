export function initTMDB() {
    const searchInput = document.getElementById('movieSearch');
    const moviesGrid = document.getElementById('moviesGrid');
    const loading = document.getElementById('loading');
    const emptyState = document.getElementById('emptyState');
    const initialState = document.getElementById('initialState');
    const modal = document.getElementById('imageModal');
    
    // Filtre Butonları
    const filterBtns = document.querySelectorAll('.filter-btn');
    let currentResults = []; // Sonuçları hafızada tut
    let activeFilter = 'all';

    window.closeModal = closeModal;
    
    if (!searchInput) return;

    // Filtre Eventleri
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Aktif sınıfını değiştir
            filterBtns.forEach(b => b.classList.remove('bg-white', 'text-black'));
            filterBtns.forEach(b => b.classList.add('bg-neutral-800', 'text-white'));
            
            btn.classList.remove('bg-neutral-800', 'text-white');
            btn.classList.add('bg-white', 'text-black');
            
            activeFilter = btn.dataset.filter;
            renderMovies(currentResults); // Yeniden render et
        });
    });

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
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
            if (!response.ok) throw new Error('API Hatası');
            
            const data = await response.json();
            currentResults = data.results; // Veriyi kaydet
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
        
        // Filtreleme
        let filtered = movies;
        if (activeFilter !== 'all') {
            filtered = movies.filter(m => m.raw_type === activeFilter);
        }

        if (!filtered || filtered.length === 0) {
            if (movies.length > 0) {
                 // Sonuç var ama filtreye takıldı
                 moviesGrid.innerHTML = '<div class="col-span-full text-center text-neutral-500 py-10">Bu kategoride sonuç bulunamadı.</div>';
            } else {
                emptyState.classList.remove('hidden');
            }
            return;
        }

        emptyState.classList.add('hidden');

        filtered.forEach(movie => {
            const imageUrl = `https://image.tmdb.org/t/p/original${movie.backdrop_path}`;
            const thumbUrl = `https://image.tmdb.org/t/p/w780${movie.backdrop_path}`;
            
            const card = document.createElement('div');
            card.className = 'group relative bg-neutral-900 rounded-xl overflow-hidden cursor-pointer border border-neutral-800 hover:border-fuchsia-500/50 transition-all duration-300 hover:shadow-[0_0_30px_rgba(217,70,239,0.15)] hover:-translate-y-1 flex flex-col';
            card.onclick = () => openModal(movie, imageUrl);
            
            // Rastgele ID oluştur (img onload için)
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
                        <span id="${dimId}" class="font-mono bg-neutral-800 px-1.5 py-0.5 rounded text-[10px]">Hesaplanıyor...</span>
                        <span>${movie.release_date ? movie.release_date.split('-')[0] : ''}</span>
                    </div>
                </div>
            `;
            
            moviesGrid.appendChild(card);

            // Görsel yüklendiğinde boyutunu al
            const imgEl = document.getElementById(imgId);
            imgEl.onload = function() {
                const dimEl = document.getElementById(dimId);
                if(dimEl) {
                    dimEl.textContent = `${this.naturalWidth} x ${this.naturalHeight}`;
                    dimEl.classList.add('text-fuchsia-500/80');
                }
            }
        });
    }

    function openModal(movie, highResUrl) {
        const modalImg = document.getElementById('modalImage');
        const modalTitle = document.getElementById('modalTitle');
        const modalDate = document.getElementById('modalDate');
        const downloadBtn = document.getElementById('downloadBtn');
        const modalDimensions = document.getElementById('modalDimensions');
        const modalContainer = document.getElementById('modalContainer');

        modalImg.src = highResUrl;
        modalTitle.textContent = movie.title;
        modalDate.textContent = movie.release_date ? movie.release_date.split('-')[0] : '';
        
        modalImg.onload = function() {
            modalDimensions.textContent = `${this.naturalWidth} x ${this.naturalHeight}`;
        };

        const downloadUrl = `https://image.tmdb.org/t/p/original${movie.backdrop_path}`;
        downloadBtn.href = downloadUrl;
        downloadBtn.setAttribute('target', '_blank');

        modal.classList.remove('hidden');
        // Animasyon için frame bekle
        requestAnimationFrame(() => {
            modal.querySelector('.backdrop-blur-md').classList.remove('opacity-0'); // Backdrop fade-in
            modalContainer.classList.remove('scale-95', 'opacity-0');
            modalContainer.classList.add('scale-100', 'opacity-100');
        });
        
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        const modalContainer = document.getElementById('modalContainer');
        
        modalContainer.classList.remove('scale-100', 'opacity-100');
        modalContainer.classList.add('scale-95', 'opacity-0');
        modal.querySelector('.backdrop-blur-md').classList.add('opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.getElementById('modalImage').src = '';
            document.body.style.overflow = '';
        }, 300);
    }

    modal.addEventListener('click', (e) => {
        // Backdrop'a tıklandıysa kapat (parent div)
        if (e.target === modal || e.target.classList.contains('backdrop-blur-md')) closeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    searchInput.addEventListener('input', debounce(handleSearch, 500));
}