<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Surat Digital - {{ $letterRequest->uuid }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex items-center justify-center antialiased overflow-x-hidden [padding:max(1rem,env(safe-area-inset-top))_max(1rem,env(safe-area-inset-right))_max(1rem,env(safe-area-inset-bottom))_max(1rem,env(safe-area-inset-left))] sm:[padding:max(1.5rem,env(safe-area-inset-top))_max(1.5rem,env(safe-area-inset-right))_max(1.5rem,env(safe-area-inset-bottom))_max(1.5rem,env(safe-area-inset-left))]">
    <div class="max-w-xl w-full bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
        {{-- Header Kartu Verifikasi --}}
        <div class="bg-slate-900 p-6 sm:p-8 text-white text-center relative overflow-hidden">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl"></div>
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-white">Verifikasi Keaslian Surat Digital</h1>
            <p class="text-slate-400 text-xs mt-1 font-medium">Layanan Verifikasi Dokumen Resmi E-Surat Sekolah</p>
        </div>

        <div class="p-6 sm:p-8 space-y-6">
            {{-- Status Banner --}}
            <div class="text-center pb-2 border-b border-slate-100">
                @switch($letterRequest->status)
                    @case('signed')
                        <div class="inline-flex items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <svg class="w-4 h-4 mr-2 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Dokumen Sah & Valid (TTE Terverifikasi)
                        </div>
                        @break
                    @case('approved_admin')
                        <div class="inline-flex items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                            <svg class="w-4 h-4 mr-2 text-sky-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            Disetujui Admin (Proses TTD Kepsek)
                        </div>
                        @break
                    @case('pending')
                        <div class="inline-flex items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            <svg class="w-4 h-4 mr-2 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            Menunggu Verifikasi Admin
                        </div>
                        @break
                    @case('rejected')
                        <div class="inline-flex items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                            <svg class="w-4 h-4 mr-2 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            Pengajuan Surat Ditolak
                        </div>
                        @break
                    @default
                        <div class="inline-flex items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold bg-slate-100 text-slate-700">
                            Status: {{ $letterRequest->status }}
                        </div>
                @endswitch
            </div>

            {{-- Detail Informasi Surat --}}
            <div class="bg-slate-50/70 rounded-xl p-4 sm:p-5 space-y-3 text-xs sm:text-sm border border-slate-200/60">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1.5 border-b border-slate-200/60 gap-1">
                    <span class="text-slate-500 font-medium">UUID Dokumen</span>
                    <span class="font-mono text-xs text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-200 select-all min-w-0 break-all self-start sm:self-auto">{{ $letterRequest->uuid }}</span>
                </div>

                @if(isset($letterRequest->payload_data['nomor_surat']) && $letterRequest->payload_data['nomor_surat'] !== '')
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1.5 border-b border-slate-200/60 gap-1">
                    <span class="text-slate-500 font-medium">Nomor Surat</span>
                    <span class="font-semibold text-slate-900 min-w-0 break-words">{{ $letterRequest->payload_data['nomor_surat'] }}</span>
                </div>
                @endif

                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1.5 border-b border-slate-200/60 gap-1">
                    <span class="text-slate-500 font-medium">Jenis Surat</span>
                    <span class="font-semibold text-slate-900 min-w-0 break-words">{{ $letterRequest->template?->name }}</span>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1.5 border-b border-slate-200/60 gap-1">
                    <span class="text-slate-500 font-medium">Nama Pengaju</span>
                    <span class="font-semibold text-slate-900 min-w-0 break-words">{{ $letterRequest->payload_data['nama'] ?? $letterRequest->user?->name }}</span>
                </div>

                @if(isset($letterRequest->payload_data['nip']) && $letterRequest->payload_data['nip'] !== '')
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1.5 border-b border-slate-200/60 gap-1">
                    <span class="text-slate-500 font-medium">NIP</span>
                    <span class="text-slate-900 font-mono text-xs min-w-0 break-all">{{ $letterRequest->payload_data['nip'] }}</span>
                </div>
                @endif

                @if(isset($letterRequest->payload_data['jabatan']) && $letterRequest->payload_data['jabatan'] !== '')
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1.5 border-b border-slate-200/60 gap-1">
                    <span class="text-slate-500 font-medium">Jabatan</span>
                    <span class="text-slate-900 min-w-0 break-words">{{ $letterRequest->payload_data['jabatan'] }}</span>
                </div>
                @endif

                @if(isset($letterRequest->payload_data['sekolah']) && $letterRequest->payload_data['sekolah'] !== '')
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1.5 border-b border-slate-200/60 gap-1">
                    <span class="text-slate-500 font-medium">Sekolah</span>
                    <span class="text-slate-900 min-w-0 break-words">{{ $letterRequest->payload_data['sekolah'] }}</span>
                </div>
                @elseif(isset($letterRequest->payload_data['nama_sekolah']) && $letterRequest->payload_data['nama_sekolah'] !== '')
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1.5 border-b border-slate-200/60 gap-1">
                    <span class="text-slate-500 font-medium">Sekolah</span>
                    <span class="text-slate-900 min-w-0 break-words">{{ $letterRequest->payload_data['nama_sekolah'] }}</span>
                </div>
                @endif

                @if(is_array($letterRequest->payload_data))
                    @foreach($letterRequest->payload_data as $key => $value)
                        @if(in_array(strtolower((string)$key), ['daftar_peserta', 'peserta']) && is_array($value) && !empty($value))
                            <div class="py-2 border-b border-slate-200/60">
                                <span class="text-slate-500 font-medium block mb-2">Daftar Peserta / Kontingen</span>
                                <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-2.5 py-2 text-center text-slate-500 font-semibold w-8">No</th>
                                                <th class="px-2.5 py-2 text-left text-slate-500 font-semibold">Nama</th>
                                                <th class="px-2.5 py-2 text-left text-slate-500 font-semibold">NISN / NIP</th>
                                                <th class="px-2.5 py-2 text-left text-slate-500 font-semibold">Kelas / Jabatan</th>
                                                <th class="px-2.5 py-2 text-left text-slate-500 font-semibold">Peran</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($value as $idx => $p)
                                                <tr>
                                                    <td class="px-2.5 py-1.5 text-center text-slate-500">{{ $idx + 1 }}</td>
                                                    <td class="px-2.5 py-1.5 font-semibold text-slate-900">{{ $p['nama'] ?? '-' }}</td>
                                                    <td class="px-2.5 py-1.5 font-mono text-slate-600">{{ $p['identitas'] ?? $p['nisn'] ?? $p['nip'] ?? '-' }}</td>
                                                    <td class="px-2.5 py-1.5 text-slate-700">{{ $p['kelas_jabatan'] ?? $p['kelas'] ?? $p['jabatan'] ?? '-' }}</td>
                                                    <td class="px-2.5 py-1.5 text-slate-600">{{ $p['peran'] ?? $p['keterangan'] ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @elseif(!in_array(strtolower((string)$key), ['nomor_surat', 'nama', 'nip', 'jabatan', 'sekolah', 'nama_sekolah', 'daftar_peserta', 'peserta']) && !empty($value) && !is_array($value))
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start py-1.5 border-b border-slate-200/60 gap-1">
                                <span class="text-slate-500 font-medium capitalize">{{ \Illuminate\Support\Str::headline($key) }}</span>
                                <span class="text-slate-900 sm:text-right min-w-0 max-w-full break-words">{!! nl2br(e($value)) !!}</span>
                            </div>
                        @endif
                    @endforeach
                @endif

                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center py-1.5 gap-1">
                    <span class="text-slate-500 font-medium">Waktu Pengajuan</span>
                    <span class="text-slate-700 font-medium min-w-0 break-words">{{ $letterRequest->created_at->translatedFormat('d F Y - H:i WIB') }}</span>
                </div>
            </div>

            {{-- Tombol Unduh PDF jika sah --}}
            @if($letterRequest->isSigned() && $letterRequest->pdf_path)
                <div class="text-center pt-2">
                    <a href="{{ asset('storage/' . $letterRequest->pdf_path) }}"
                       target="_blank"
                       class="w-full inline-flex items-center justify-center px-6 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition duration-150 shadow-sm shadow-emerald-600/20 group">
                        <svg class="w-5 h-5 mr-2 text-emerald-100 group-hover:translate-y-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Unduh Dokumen Surat Resmi (PDF)
                    </a>
                </div>
            @endif

            {{-- Catatan Keamanan --}}
            <div class="text-center pt-3 border-t border-slate-100 text-xs text-slate-400 leading-relaxed">
                <p>Dokumen ini diterbitkan dan diverifikasi secara elektronik oleh sistem resmi E-Surat Sekolah.</p>
                <p class="mt-0.5">Setiap perubahan atau pemalsuan isi surat di luar sistem dinyatakan tidak sah.</p>
            </div>
        </div>
    </div>
</body>
</html>
