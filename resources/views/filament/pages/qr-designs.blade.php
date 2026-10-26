{{-- Filament yönetim panelindeki 'QR tasarımları' sayfası: kayıtlı QR tasarımlarını tablo halinde listeler. --}}
{{-- Veriyi $this->getDesigns() getirir. Her satırda ID, ad, logo var/yok bilgisi ve son güncelleme tarihi gösterilir. --}}
{{-- <x-filament-panels::page> Filament'in sayfa düzenidir; içerik bu etiketin içine yazılır. --}}
<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <p class="text-sm text-gray-500">
                Kaydedilmiş QR tasarımlarınız.
            </p>

            <a href="{{ route('qr.index') }}"
               class="text-primary-600 hover:underline"
               target="_blank" rel="noopener">
                QR Tasarım Stüdyosu'nu Aç
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="p-4">ID</th>
                        <th class="p-4">Tasarım Adı</th>
                        <th class="p-4">Logo</th>
                        <th class="p-4">Güncelleme</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- @forelse: hiç tasarım yoksa @empty bloğundaki 'kayıtlı tasarım yok' satırı gösterilir. --}}
                    @forelse ($this->getDesigns() as $design)
                        <tr class="border-t border-gray-200 dark:border-gray-700">
                            <td class="p-4">{{ $design->id }}</td>
                            <td class="p-4 font-medium">
                                {{ $design->name }}
                            </td>
                            <td class="p-4">
                                {{ !empty($design->settings['logoFile']) ? 'Var' : 'Yok' }}
                            </td>
                            <td class="p-4">
                                {{ $design->updated_at?->format('d.m.Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center">
                                Henüz kayıtlı QR tasarımı bulunmuyor.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
