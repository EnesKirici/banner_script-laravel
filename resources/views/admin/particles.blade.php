@extends('admin.layout')

@section('title', 'Particles Yönetimi')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-neutral-400">Background efektlerini buradan yönetebilirsiniz.</p>
    <form action="{{ route('admin.particles.seed') }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="px-4 py-2 bg-neutral-800 hover:bg-neutral-700 text-white rounded-lg transition-colors text-sm">
            Preset'leri Yükle
        </button>
    </form>
</div>

<!-- Theme Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    @forelse($themes as $theme)
    <div class="bg-neutral-900 rounded-xl border {{ $theme->is_active ? 'border-fuchsia-500' : 'border-white/5' }} overflow-hidden group">
        <!-- Preview -->
        <div class="h-32 relative" style="background: linear-gradient(135deg, {{ $theme->preview_color }}20, transparent)">
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="w-16 h-16 rounded-full opacity-30" style="background: {{ $theme->preview_color }}; filter: blur(20px);"></div>
            </div>
            @if($theme->is_active)
            <div class="absolute top-3 right-3 px-2 py-1 bg-fuchsia-500 text-white text-xs font-bold rounded">
                AKTİF
            </div>
            @endif
            @if($theme->is_preset)
            <div class="absolute top-3 left-3 px-2 py-1 bg-neutral-800 text-neutral-400 text-xs rounded">
                Preset
            </div>
            @endif
        </div>
        
        <!-- Info -->
        <div class="p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-4 h-4 rounded-full" style="background: {{ $theme->preview_color }}"></div>
                <h3 class="font-semibold">{{ $theme->name }}</h3>
            </div>
            
            <div class="flex items-center gap-2">
                @if(!$theme->is_active)
                <form action="{{ route('admin.particles.activate', $theme) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full px-3 py-2 bg-fuchsia-600 hover:bg-fuchsia-500 text-white text-sm rounded-lg transition-colors">
                        Aktifleştir
                    </button>
                </form>
                @else
                <span class="flex-1 px-3 py-2 bg-neutral-800 text-neutral-500 text-sm rounded-lg text-center cursor-not-allowed">
                    Aktif
                </span>
                @endif
                
                <button type="button" onclick="editTheme({{ json_encode($theme) }})" 
                        class="px-3 py-2 bg-neutral-800 hover:bg-neutral-700 text-white text-sm rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                </button>
                
                @if(!$theme->is_preset && !$theme->is_active)
                <form action="{{ route('admin.particles.destroy', $theme) }}" method="POST" 
                      onsubmit="return confirm('Bu temayı silmek istediğinizden emin misiniz?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-2 bg-red-600/20 hover:bg-red-600 text-red-400 hover:text-white text-sm rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full bg-neutral-900 rounded-xl border border-white/5 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-neutral-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
        </svg>
        <p class="text-neutral-500 mb-4">Henüz tema yok.</p>
        <form action="{{ route('admin.particles.seed') }}" method="POST">
            @csrf
            <button type="submit" class="px-6 py-2 bg-fuchsia-600 hover:bg-fuchsia-500 text-white rounded-lg transition-colors">
                Preset Temaları Yükle
            </button>
        </form>
    </div>
    @endforelse
</div>

<!-- Create New Theme Button -->
<button onclick="openCreateModal()" 
        class="w-full py-4 border-2 border-dashed border-neutral-800 hover:border-fuchsia-500/50 rounded-xl text-neutral-500 hover:text-fuchsia-400 transition-colors">
    + Yeni Tema Oluştur
</button>

<!-- Modal -->
<div id="themeModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="absolute inset-4 md:inset-10 lg:inset-20 bg-neutral-900 rounded-2xl border border-white/10 flex flex-col overflow-hidden">
        <!-- Modal Header -->
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <h2 id="modalTitle" class="text-xl font-bold">Tema Düzenle</h2>
            <button onclick="closeModal()" class="p-2 hover:bg-white/5 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Editor -->
            <div class="w-1/2 p-6 overflow-auto border-r border-white/5">
                <form id="themeForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Tema Adı</label>
                            <input type="text" name="name" id="themeName" required
                                   class="w-full px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg focus:outline-none focus:border-fuchsia-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Önizleme Rengi</label>
                            <div class="flex gap-2">
                                <input type="color" name="preview_color" id="themeColor" value="#a855f7"
                                       class="w-12 h-10 rounded cursor-pointer bg-transparent border-0">
                                <input type="text" id="themeColorText" value="#a855f7"
                                       class="flex-1 px-4 py-2 bg-neutral-800 border border-neutral-700 rounded-lg focus:outline-none focus:border-fuchsia-500 font-mono text-sm">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-neutral-300 mb-2">Konfigürasyon (JSON)</label>
                            <textarea name="config" id="themeConfig" rows="20" required
                                      class="w-full px-4 py-3 bg-neutral-800 border border-neutral-700 rounded-lg focus:outline-none focus:border-fuchsia-500 font-mono text-sm resize-none"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex gap-3">
                        <button type="submit" class="flex-1 py-3 bg-fuchsia-600 hover:bg-fuchsia-500 text-white font-semibold rounded-lg transition-colors">
                            Kaydet
                        </button>
                        <button type="button" onclick="closeModal()" class="px-6 py-3 bg-neutral-800 hover:bg-neutral-700 text-white rounded-lg transition-colors">
                            İptal
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Preview -->
            <div class="w-1/2 bg-neutral-950 relative">
                <div id="previewParticles" class="absolute inset-0"></div>
                <div class="absolute bottom-4 right-4">
                    <button onclick="refreshPreview()" class="px-4 py-2 bg-neutral-800/80 hover:bg-neutral-700 text-white text-sm rounded-lg transition-colors backdrop-blur-sm">
                        Önizle
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('themeModal');
    const form = document.getElementById('themeForm');
    const colorInput = document.getElementById('themeColor');
    const colorText = document.getElementById('themeColorText');
    
    colorInput.addEventListener('input', (e) => colorText.value = e.target.value);
    colorText.addEventListener('input', (e) => colorInput.value = e.target.value);
    
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Yeni Tema Oluştur';
        form.action = '{{ route("admin.particles.store") }}';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('themeName').value = '';
        document.getElementById('themeColor').value = '#a855f7';
        document.getElementById('themeColorText').value = '#a855f7';
        document.getElementById('themeConfig').value = JSON.stringify({
            fpsLimit: 60,
            particles: {
                number: { value: 50 },
                color: { value: "#a855f7" },
                shape: { type: "circle" },
                opacity: { value: 0.5 },
                size: { value: 3 },
                move: { enable: true, speed: 1 }
            },
            interactivity: {
                events: {
                    onHover: { enable: true, mode: "grab" },
                    onClick: { enable: true, mode: "push" }
                }
            },
            detectRetina: true
        }, null, 2);
        modal.classList.remove('hidden');
    }
    
    function editTheme(theme) {
        document.getElementById('modalTitle').textContent = 'Tema Düzenle: ' + theme.name;
        form.action = '/admin/particles/theme/' + theme.id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('themeName').value = theme.name;
        document.getElementById('themeColor').value = theme.preview_color || '#a855f7';
        document.getElementById('themeColorText').value = theme.preview_color || '#a855f7';
        document.getElementById('themeConfig').value = JSON.stringify(theme.config, null, 2);
        modal.classList.remove('hidden');
    }
    
    function closeModal() {
        modal.classList.add('hidden');
    }
    
    function refreshPreview() {
        // Preview functionality - would need tsParticles integration
        alert('Önizleme için sayfayı yenileyin ve temayı aktifleştirin.');
    }
    
    // Close modal on escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
</script>
@endsection
