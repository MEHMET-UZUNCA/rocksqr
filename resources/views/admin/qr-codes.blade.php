@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
            <div class="p-6 md:p-8 bg-white border-b border-gray-200">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">
                            <i class="fas fa-qrcode mr-2 text-gold"></i>Masa QR Oluştur
                        </h2>
                        <p class="text-sm text-gray-500 mt-2">Masa numarasına göre toplu QR üretin, önizleyin, A4 baskıya hazırlayın ve arşivleyin.</p>
                    </div>
                    <div class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
                        QR linkleri otomatik olarak <span class="font-semibold">/table/{masaNo}</span> adresine gider.
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-[1.1fr_0.9fr] gap-6">
                    <form method="POST" action="{{ route('admin.qr-codes.preview') }}" class="space-y-6 bg-gray-50 border border-gray-200 rounded-2xl p-6">
                        @csrf
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Toplu Üretim</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="start_table" class="block text-sm font-semibold text-gray-700 mb-2">Başlangıç Masa No</label>
                                    <input type="number" min="1" max="500" name="start_table" id="start_table"
                                           value="{{ old('start_table', $formData['start_table']) }}"
                                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-gold focus:border-gold">
                                </div>
                                <div>
                                    <label for="end_table" class="block text-sm font-semibold text-gray-700 mb-2">Bitiş Masa No</label>
                                    <input type="number" min="1" max="500" name="end_table" id="end_table"
                                           value="{{ old('end_table', $formData['end_table']) }}"
                                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-gold focus:border-gold">
                                </div>
                            </div>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center text-xs uppercase">
                                <span class="bg-gray-50 px-3 text-gray-500 tracking-[0.2em]">veya</span>
                            </div>
                        </div>

                        <div>
                            <label for="table_numbers" class="block text-sm font-semibold text-gray-700 mb-2">Özel Masa Listesi</label>
                            <textarea name="table_numbers" id="table_numbers" rows="4"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-gold focus:border-gold"
                                      placeholder="1,2,3,10-15">{{ old('table_numbers', $formData['table_numbers']) }}</textarea>
                            <p class="text-xs text-gray-500 mt-2">Virgülle ayırın. Aralık için <span class="font-semibold">10-15</span> kullanabilirsiniz.</p>
                        </div>

                        <div class="flex flex-col md:flex-row gap-3">
                            <button type="submit"
                                    class="flex-1 py-3 px-4 bg-primary text-white font-bold rounded-lg hover:bg-light-primary transition">
                                <i class="fas fa-eye mr-2 text-gold"></i>Önizle
                            </button>
                            <button type="submit" formaction="{{ route('admin.qr-codes.print') }}" formtarget="_blank"
                                    class="flex-1 py-3 px-4 bg-white border border-gray-300 text-gray-900 font-bold rounded-lg hover:bg-gray-100 transition">
                                <i class="fas fa-print mr-2 text-primary"></i>A4 Yazdır
                            </button>
                            <button type="submit" formaction="{{ route('admin.qr-codes.download') }}"
                                    class="flex-1 py-3 px-4 bg-gold text-primary font-bold rounded-lg hover:brightness-95 transition">
                                <i class="fas fa-file-archive mr-2"></i>ZIP İndir
                            </button>
                        </div>
                    </form>

                    <div class="bg-primary text-white rounded-2xl p-6 relative overflow-hidden">
                        <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-gold/10"></div>
                        <div class="absolute -bottom-12 -left-8 h-40 w-40 rounded-full bg-white/5"></div>
                        <div class="relative">
                            <h3 class="text-lg font-semibold mb-4">Kullanım Notları</h3>
                            <div class="space-y-3 text-sm text-gray-200">
                                <p>Her QR doğrudan ilgili masa menüsünü açar.</p>
                                <p>Toplu üretimle aynı anda en fazla 200 masa için çıktı alınabilir.</p>
                                <p>Önizleme sonrası ister A4 baskı alın, ister ZIP indirin, ister arşive kaydedin.</p>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-3">
                                <div class="bg-white/10 rounded-xl p-4 border border-white/10">
                                    <div class="text-2xl font-bold">{{ count($generatedQrs) }}</div>
                                    <div class="text-xs uppercase tracking-[0.2em] text-gray-300 mt-1">Hazır QR</div>
                                </div>
                                <div class="bg-white/10 rounded-xl p-4 border border-white/10">
                                    <div class="text-2xl font-bold">SVG</div>
                                    <div class="text-xs uppercase tracking-[0.2em] text-gray-300 mt-1">Format</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(count($generatedQrs) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 md:p-8 bg-white border-b border-gray-200">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">QR Önizleme</h3>
                            <p class="text-sm text-gray-500 mt-1">Toplam {{ count($generatedQrs) }} masa için QR hazırlandı.</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <form method="POST" action="{{ route('admin.qr-codes.print') }}" target="_blank">
                                @csrf
                                <input type="hidden" name="start_table" value="{{ $formData['start_table'] }}">
                                <input type="hidden" name="end_table" value="{{ $formData['end_table'] }}">
                                <input type="hidden" name="table_numbers" value="{{ $formData['table_numbers'] }}">
                                <button type="submit" class="inline-flex items-center px-5 py-3 bg-white border border-gray-300 text-gray-900 font-bold rounded-lg hover:bg-gray-100 transition w-full">
                                    <i class="fas fa-print mr-2 text-primary"></i>A4 Baskı Aç
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.qr-codes.save') }}">
                                @csrf
                                <input type="hidden" name="start_table" value="{{ $formData['start_table'] }}">
                                <input type="hidden" name="end_table" value="{{ $formData['end_table'] }}">
                                <input type="hidden" name="table_numbers" value="{{ $formData['table_numbers'] }}">
                                <button type="submit" class="inline-flex items-center px-5 py-3 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-700 transition w-full">
                                    <i class="fas fa-box-archive mr-2"></i>Arşive Kaydet
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.qr-codes.download') }}">
                                @csrf
                                <input type="hidden" name="start_table" value="{{ $formData['start_table'] }}">
                                <input type="hidden" name="end_table" value="{{ $formData['end_table'] }}">
                                <input type="hidden" name="table_numbers" value="{{ $formData['table_numbers'] }}">
                                <button type="submit" class="inline-flex items-center px-5 py-3 bg-gold text-primary font-bold rounded-lg hover:brightness-95 transition w-full">
                                    <i class="fas fa-download mr-2"></i>Tümünü ZIP İndir
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                        @foreach($generatedQrs as $qr)
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                                <div class="rounded-2xl bg-white border border-gray-200 p-4 shadow-sm">
                                    <img src="{{ $qr['image_data_uri'] }}" alt="Masa {{ $qr['table_no'] }} QR" class="w-full h-auto object-contain rounded-xl">
                                    <div class="mt-4 text-center border-t border-dashed border-gray-200 pt-4">
                                        <div class="text-xs uppercase tracking-[0.25em] text-gray-400">Rocks Hotel</div>
                                        <div class="text-3xl font-bold text-primary mt-1">Masa {{ $qr['table_no'] }}</div>
                                        <div class="text-xs text-gray-500 mt-1">Menü için QR kodu okutun</div>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.2em] text-gray-400">Masa</div>
                                        <div class="text-2xl font-bold text-gray-900">{{ $qr['table_no'] }}</div>
                                    </div>
                                    <a href="{{ $qr['url'] }}" target="_blank" class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-lg bg-primary text-white hover:bg-light-primary transition">
                                        <i class="fas fa-external-link-alt mr-2 text-gold"></i>Aç
                                    </a>
                                </div>
                                <div class="mt-3 text-xs text-gray-500 break-all">{{ $qr['url'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
            <div class="p-6 md:p-8 bg-white border-b border-gray-200">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">QR Arşivi</h3>
                        <p class="text-sm text-gray-500 mt-1">Kaydedilen toplu QR setleri daha sonra tekrar indirilebilir veya yazdırılabilir.</p>
                    </div>
                </div>

                @if(count($archives) === 0)
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center text-gray-500">
                        Henüz kaydedilmiş QR arşivi yok.
                    </div>
                @else
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @foreach($archives as $archive)
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.2em] text-gray-400">Arşiv</div>
                                        <div class="text-lg font-bold text-gray-900 mt-1">{{ $archive['summary'] }}</div>
                                        <div class="text-sm text-gray-500 mt-1">{{ \Illuminate\Support\Carbon::parse($archive['created_at'])->format('d.m.Y H:i') }}</div>
                                    </div>
                                    <div class="px-3 py-2 rounded-xl bg-primary text-white text-sm font-semibold">
                                        {{ $archive['count'] }} QR
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-col sm:flex-row gap-3">
                                    <a href="{{ route('admin.qr-codes.archive.print', $archive['id']) }}" target="_blank" class="inline-flex items-center justify-center px-4 py-3 bg-white border border-gray-300 text-gray-900 font-bold rounded-lg hover:bg-gray-100 transition flex-1">
                                        <i class="fas fa-print mr-2 text-primary"></i>Yazdır
                                    </a>
                                    <a href="{{ route('admin.qr-codes.archive.download', $archive['id']) }}" class="inline-flex items-center justify-center px-4 py-3 bg-gold text-primary font-bold rounded-lg hover:brightness-95 transition flex-1">
                                        <i class="fas fa-download mr-2"></i>ZIP İndir
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection