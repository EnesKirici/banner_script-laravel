<?php

use App\Models\BlockedIp;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;

new #[Layout('admin.layout')] #[Title('Engelli IP\'ler')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterType = '';

    public string $newIp = '';
    public string $newReason = 'Manuel engelleme';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function blockIp(): void
    {
        $this->validate([
            'newIp' => ['required', 'ip'],
            'newReason' => ['required', 'string', 'max:255'],
        ], [
            'newIp.required' => 'IP adresi gereklidir.',
            'newIp.ip' => 'Geçerli bir IP adresi girin.',
        ]);

        BlockedIp::updateOrCreate(
            ['ip_address' => $this->newIp],
            [
                'reason' => $this->newReason,
                'ban_type' => 'manual',
                'blocked_until' => null,
            ]
        );

        Cache::forget("blocked_ip_{$this->newIp}");

        $this->newIp = '';
        $this->newReason = 'Manuel engelleme';
    }

    public function unblock(int $id): void
    {
        $blocked = BlockedIp::findOrFail($id);
        Cache::forget("blocked_ip_{$blocked->ip_address}");
        $blocked->delete();
    }

    public function rendering(): void
    {
        BlockedIp::whereNotNull('blocked_until')
            ->where('blocked_until', '<=', now())
            ->delete();
    }

    public function with(): array
    {
        $query = BlockedIp::query()->latest();

        if ($this->search) {
            $query->where(function ($q) {
                $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->search);
                $q->where('ip_address', 'like', "%{$escaped}%")
                    ->orWhere('reason', 'like', "%{$escaped}%");
            });
        }

        if ($this->filterType) {
            $query->where('ban_type', $this->filterType);
        }

        $stats = [
            'total' => BlockedIp::count(),
            'permanent' => BlockedIp::whereNull('blocked_until')->count(),
            'temporary' => BlockedIp::whereNotNull('blocked_until')->where('blocked_until', '>', now())->count(),
            'rate_limit' => BlockedIp::where('ban_type', 'rate_limit')->count(),
            'suspicious_upload' => BlockedIp::where('ban_type', 'suspicious_upload')->count(),
            'brute_force' => BlockedIp::where('ban_type', 'brute_force')->count(),
            'manual' => BlockedIp::where('ban_type', 'manual')->count(),
        ];

        return [
            'blockedIps' => $query->paginate(15),
            'stats' => $stats,
        ];
    }
};
?>

<div>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Engelli IP'ler</h1>
            <p class="text-sm text-neutral-500 mt-1">Toplam {{ $stats['total'] }} engelli IP</p>
        </div>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-neutral-900 rounded-xl border border-white/5 p-4">
            <p class="text-xs text-neutral-500 uppercase tracking-wider">Kalıcı Ban</p>
            <p class="text-2xl font-bold text-red-400 mt-1">{{ $stats['permanent'] }}</p>
        </div>
        <div class="bg-neutral-900 rounded-xl border border-white/5 p-4">
            <p class="text-xs text-neutral-500 uppercase tracking-wider">Geçici Ban</p>
            <p class="text-2xl font-bold text-yellow-400 mt-1">{{ $stats['temporary'] }}</p>
        </div>
        <div class="bg-neutral-900 rounded-xl border border-white/5 p-4">
            <p class="text-xs text-neutral-500 uppercase tracking-wider">Rate Limit</p>
            <p class="text-2xl font-bold text-orange-400 mt-1">{{ $stats['rate_limit'] }}</p>
        </div>
        <div class="bg-neutral-900 rounded-xl border border-white/5 p-4">
            <p class="text-xs text-neutral-500 uppercase tracking-wider">Şüpheli Yükleme</p>
            <p class="text-2xl font-bold text-purple-400 mt-1">{{ $stats['suspicious_upload'] }}</p>
        </div>
    </div>

    {{-- Yeni IP Engelleme Formu --}}
    <div class="bg-neutral-900 rounded-xl border border-white/5 p-6 mb-6">
        <h3 class="text-sm font-semibold text-neutral-400 mb-4">Manuel IP Engelle</h3>
        <form wire:submit="blockIp" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1">
                <input type="text" wire:model="newIp" placeholder="IP Adresi (örn: 192.168.1.1)"
                    class="w-full bg-neutral-800 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-fuchsia-500/50 transition-colors">
                @error('newIp') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div class="flex-1">
                <input type="text" wire:model="newReason" placeholder="Sebep"
                    class="w-full bg-neutral-800 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-fuchsia-500/50 transition-colors">
            </div>
            <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium transition-colors shrink-0">
                Engelle
            </button>
        </form>
    </div>

    {{-- Arama & Filtre --}}
    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="IP veya sebep ara..."
            class="w-full md:w-80 bg-neutral-800 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-fuchsia-500/50 transition-colors">
        <select wire:model.live="filterType"
            class="bg-neutral-800 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-fuchsia-500/50 transition-colors">
            <option value="">Tüm Tipler</option>
            <option value="manual">Manuel</option>
            <option value="rate_limit">Rate Limit</option>
            <option value="suspicious_upload">Şüpheli Yükleme</option>
            <option value="brute_force">Brute Force</option>
        </select>
    </div>

    {{-- Tablo --}}
    <div class="bg-neutral-900 rounded-xl border border-white/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">IP Adresi</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">Tip</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">Sebep</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">İstek</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">İhlal</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">Süre</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">Tarih</th>
                        <th class="text-right text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($blockedIps as $blocked)
                    <tr class="hover:bg-white/2 transition-colors" wire:key="blocked-{{ $blocked->id }}">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-red-400">{{ $blocked->ip_address }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @switch($blocked->ban_type)
                                @case('rate_limit')
                                    <span class="text-xs px-2 py-1 rounded bg-orange-500/10 text-orange-400">Rate Limit</span>
                                    @break
                                @case('suspicious_upload')
                                    <span class="text-xs px-2 py-1 rounded bg-purple-500/10 text-purple-400">Şüpheli Yükleme</span>
                                    @break
                                @case('brute_force')
                                    <span class="text-xs px-2 py-1 rounded bg-red-500/10 text-red-400">Brute Force</span>
                                    @break
                                @default
                                    <span class="text-xs px-2 py-1 rounded bg-neutral-500/10 text-neutral-400">Manuel</span>
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-sm text-neutral-400 max-w-xs truncate" title="{{ $blocked->reason }}">{{ $blocked->reason }}</td>
                        <td class="px-6 py-4 text-sm text-neutral-400 font-mono">{{ $blocked->request_count ?: '-' }}</td>
                        <td class="px-6 py-4 text-sm text-neutral-400 font-mono">{{ $blocked->violation_count ?: '-' }}</td>
                        <td class="px-6 py-4">
                            @if($blocked->blocked_until)
                                <span class="text-xs px-2 py-1 rounded bg-yellow-500/10 text-yellow-400">
                                    {{ $blocked->blocked_until->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-xs px-2 py-1 rounded bg-red-500/10 text-red-400">Kalıcı</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-neutral-500 whitespace-nowrap">{{ $blocked->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="unblock({{ $blocked->id }})"
                                wire:confirm="Bu IP'nin engelini kaldırmak istediğinize emin misiniz?"
                                class="text-xs px-3 py-1.5 rounded-lg bg-emerald-600/10 text-emerald-400 hover:bg-emerald-600/20 transition-colors">
                                Engeli Kaldır
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-neutral-500">
                            Engelli IP bulunmuyor.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($blockedIps->hasPages())
        <div class="mt-4">
            {{ $blockedIps->links() }}
        </div>
    @endif
</div>
