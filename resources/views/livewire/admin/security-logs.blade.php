<?php

use App\Models\SecurityLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('admin.layout')] #[Title('Güvenlik Logları')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $eventType = '';

    public string $deleteIp = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEventType(): void
    {
        $this->resetPage();
    }

    public function deleteByIp(): void
    {
        $this->validate([
            'deleteIp' => ['required', 'ip'],
        ], [
            'deleteIp.required' => 'IP adresi gereklidir.',
            'deleteIp.ip' => 'Geçerli bir IP adresi girin.',
        ]);

        $count = SecurityLog::where('ip_address', $this->deleteIp)->count();
        SecurityLog::where('ip_address', $this->deleteIp)->delete();

        $this->deleteIp = '';

        session()->flash('seclog_message', "{$count} log kaydı silindi.");
    }

    public function clearOldLogs(): void
    {
        SecurityLog::where('created_at', '<', now()->subDays(30))->delete();
    }

    public function with(): array
    {
        $query = SecurityLog::query()->latest();

        if ($this->search) {
            $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->search);
            $query->where(function ($q) use ($escaped) {
                $q->where('ip_address', 'like', "%{$escaped}%")
                    ->orWhere('description', 'like', "%{$escaped}%")
                    ->orWhere('user_agent', 'like', "%{$escaped}%");
            });
        }

        if ($this->eventType) {
            $query->where('event_type', $this->eventType);
        }

        $today = now()->startOfDay();

        $stats = [
            'total' => SecurityLog::count(),
            'today' => SecurityLog::where('created_at', '>=', $today)->count(),
            'bans_today' => SecurityLog::where('event_type', 'like', 'ban_%')->where('created_at', '>=', $today)->count(),
            'warnings_today' => SecurityLog::where('event_type', 'rate_warning')->where('created_at', '>=', $today)->count(),
            'uploads_today' => SecurityLog::where('event_type', 'suspicious_upload')->where('created_at', '>=', $today)->count(),
            'unique_ips_today' => SecurityLog::where('created_at', '>=', $today)->distinct('ip_address')->count('ip_address'),
        ];

        $eventTypes = SecurityLog::select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        return [
            'logs' => $query->paginate(25),
            'stats' => $stats,
            'eventTypes' => $eventTypes,
        ];
    }
};
?>

<div>
    {{-- İstatistikler --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-neutral-900 rounded-xl border border-white/5 p-4">
            <p class="text-2xl font-bold">{{ $stats['today'] }}</p>
            <p class="text-xs text-neutral-500">Bugün Toplam</p>
        </div>
        <div class="bg-neutral-900 rounded-xl border border-white/5 p-4">
            <p class="text-2xl font-bold text-red-400">{{ $stats['bans_today'] }}</p>
            <p class="text-xs text-neutral-500">Bugün Ban</p>
        </div>
        <div class="bg-neutral-900 rounded-xl border border-white/5 p-4">
            <p class="text-2xl font-bold text-yellow-400">{{ $stats['warnings_today'] }}</p>
            <p class="text-xs text-neutral-500">Bugün Uyarı</p>
        </div>
        <div class="bg-neutral-900 rounded-xl border border-white/5 p-4">
            <p class="text-2xl font-bold text-cyan-400">{{ $stats['unique_ips_today'] }}</p>
            <p class="text-xs text-neutral-500">Tekil Saldırgan IP</p>
        </div>
    </div>

    {{-- IP Bazlı Log Silme --}}
    <div class="bg-neutral-900 rounded-xl border border-white/5 p-6 mb-6">
        <h3 class="text-sm font-semibold text-neutral-400 mb-4">IP Bazlı Log Sil</h3>
        <form wire:submit="deleteByIp" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1">
                <input type="text" wire:model="deleteIp" placeholder="IP Adresi (örn: 192.168.1.1)"
                    class="w-full bg-neutral-800 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-fuchsia-500/50 transition-colors">
                @error('deleteIp') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <button type="submit"
                wire:confirm="Bu IP'ye ait tüm güvenlik logları silinecek. Emin misiniz?"
                class="px-6 py-2.5 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-medium transition-colors shrink-0">
                Logları Sil
            </button>
        </form>

        @if(session('seclog_message'))
            <p class="text-emerald-400 text-sm mt-3">{{ session('seclog_message') }}</p>
        @endif
    </div>

    {{-- Filtreler --}}
    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="IP, açıklama veya user-agent ara..."
            class="w-full md:w-96 bg-neutral-800 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-fuchsia-500/50 transition-colors">

        <select wire:model.live="eventType"
            class="bg-neutral-800 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-fuchsia-500/50 transition-colors">
            <option value="">Tüm Olaylar</option>
            @foreach($eventTypes as $type)
                <option value="{{ $type }}">{{ $type }}</option>
            @endforeach
        </select>

        <button wire:click="clearOldLogs" wire:confirm="30 günden eski loglar silinecek. Emin misiniz?"
            class="ml-auto px-4 py-2 rounded-lg text-xs font-medium bg-neutral-800 text-neutral-400 hover:bg-red-600/20 hover:text-red-400 transition-colors">
            Eski Logları Temizle
        </button>
    </div>

    {{-- Tablo --}}
    <div class="bg-neutral-900 rounded-xl border border-white/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-white/5">
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">Tarih</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">IP</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">Olay</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">Açıklama</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">İstek</th>
                        <th class="text-left text-xs uppercase tracking-wider text-neutral-500 font-medium px-6 py-4">User-Agent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($logs as $log)
                    <tr class="hover:bg-white/2 transition-colors" wire:key="seclog-{{ $log->id }}">
                        <td class="px-6 py-3 whitespace-nowrap">
                            <p class="text-sm">{{ $log->created_at->format('d.m.Y H:i:s') }}</p>
                            <p class="text-xs text-neutral-600">{{ $log->created_at->diffForHumans() }}</p>
                        </td>
                        <td class="px-6 py-3">
                            <span class="font-mono text-xs text-red-400">{{ $log->ip_address }}</span>
                        </td>
                        <td class="px-6 py-3">
                            @php
                                $eventStyles = [
                                    'ban_rate_limit' => 'bg-orange-500/10 text-orange-400',
                                    'ban_suspicious_upload' => 'bg-purple-500/10 text-purple-400',
                                    'ban_brute_force' => 'bg-red-500/10 text-red-400',
                                    'rate_warning' => 'bg-yellow-500/10 text-yellow-400',
                                    'suspicious_upload' => 'bg-purple-500/10 text-purple-300',
                                ];
                                $eventLabels = [
                                    'ban_rate_limit' => 'Ban: Rate Limit',
                                    'ban_suspicious_upload' => 'Ban: Şüpheli Yükleme',
                                    'ban_brute_force' => 'Ban: Brute Force',
                                    'rate_warning' => 'Uyarı: Rate Limit',
                                    'suspicious_upload' => 'Şüpheli Yükleme',
                                ];
                            @endphp
                            <span class="text-xs px-2 py-1 rounded whitespace-nowrap {{ $eventStyles[$log->event_type] ?? 'bg-neutral-500/10 text-neutral-400' }}">
                                {{ $eventLabels[$log->event_type] ?? $log->event_type }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <p class="text-sm text-neutral-300 truncate max-w-xs" title="{{ $log->description }}">{{ $log->description }}</p>
                            @if($log->url)
                                <p class="text-xs text-neutral-600 truncate max-w-xs" title="{{ $log->url }}">{{ $log->url }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm text-neutral-400 font-mono">{{ $log->request_count ?: '-' }}</td>
                        <td class="px-6 py-3">
                            <p class="text-xs text-neutral-500 truncate max-w-[200px]" title="{{ $log->user_agent }}">{{ $log->user_agent ?: '-' }}</p>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-neutral-500">Henüz güvenlik logu yok.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($logs->hasPages())
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
