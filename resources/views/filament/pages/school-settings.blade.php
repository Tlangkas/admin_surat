<x-filament-panels::page>
    @php
        $settingsInstance = \App\Models\SchoolSettings::getInstance();
        $logoUtamaUrl = $settingsInstance->getLogoUrlAttribute();
        $logoKananUrl = $settingsInstance->getLogoKananUrlAttribute();
        $ttdKepsekUrl = $settingsInstance->getTtdKepsekUrlAttribute();
    @endphp

    <div class="space-y-6"
         x-data="{
            activeTab: 'left',
            isDraggingLeft: false,
            isDraggingRight: false,
            startX: 0,
            startY: 0,
            startOffsetX: 0,
            startOffsetY: 0,
            
            // Status Logo Sekunder (Kanan)
            hasLogoKanan: @entangle('has_logo_kanan').live,

            // Variabel Logo Diikat Langsung ke Livewire Dedicated Properties
            logoWidth: @entangle('logo_width').live,
            logoOffsetX: @entangle('logo_offset_x').live,
            logoOffsetY: @entangle('logo_offset_y').live,
            logoValign: @entangle('logo_valign').live,

            logoKananWidth: @entangle('logo_kanan_width').live,
            logoKananOffsetX: @entangle('logo_kanan_offset_x').live,
            logoKananOffsetY: @entangle('logo_kanan_offset_y').live,
            logoKananValign: @entangle('logo_kanan_valign').live,

            // Variabel Jarak/Gap Kop Surat
            kopGap: @entangle('kop_gap').live,

            // Variabel Teks Kop Surat (Live Real-Time Binding)
            namaSekolah: @entangle('data.nama_sekolah').live,
            kopLine1: @entangle('data.kop_line_1').live,
            kopLine2: @entangle('data.kop_line_2').live,
            alamat: @entangle('data.alamat').live,
            kodePos: @entangle('data.kode_pos').live,
            telepon: @entangle('data.telepon').live,
            email: @entangle('data.email').live,
            website: @entangle('data.website').live,
            npsn: @entangle('data.npsn').live,
            akreditasi: @entangle('data.akreditasi').live,
            kotaKabupaten: @entangle('data.kota_kabupaten').live,
            kepalaSekolahNama: @entangle('data.kepala_sekolah_nama').live,
            kepalaSekolahNip: @entangle('data.kepala_sekolah_nip').live,
            kepalaSekolahJabatan: @entangle('data.kepala_sekolah_jabatan').live,

            setSize(size) {
                if (this.activeTab === 'left') {
                    this.logoWidth = size;
                    $wire.set('logo_width', size);
                } else {
                    this.logoKananWidth = size;
                    $wire.set('logo_kanan_width', size);
                }
            },

            setValign(valign) {
                if (this.activeTab === 'left') {
                    this.logoValign = valign;
                    $wire.set('logo_valign', valign);
                } else {
                    this.logoKananValign = valign;
                    $wire.set('logo_kanan_valign', valign);
                }
            },

            setGap(gap) {
                this.kopGap = gap;
                $wire.set('kop_gap', gap);
            },

            getSideWidth() {
                const gap = (this.kopGap !== null && this.kopGap !== undefined && !isNaN(this.kopGap)) ? parseInt(this.kopGap) : 10;
                const leftW = parseInt(this.logoWidth || 85) + Math.max(0, parseInt(this.logoOffsetX || 0));
                
                if (this.hasLogoKanan) {
                    const rightW = parseInt(this.logoKananWidth || 85) + Math.max(0, -parseInt(this.logoKananOffsetX || 0));
                    return Math.max(leftW, rightW) + gap;
                }
                
                return leftW + gap;
            },

            resetPositions() {
                if (this.activeTab === 'left') {
                    this.logoWidth = 85;
                    this.logoOffsetX = 0;
                    this.logoOffsetY = 0;
                    this.logoValign = 'middle';
                    $wire.set('logo_width', 85);
                    $wire.set('logo_offset_x', 0);
                    $wire.set('logo_offset_y', 0);
                    $wire.set('logo_valign', 'middle');
                } else {
                    this.logoKananWidth = 85;
                    this.logoKananOffsetX = 0;
                    this.logoKananOffsetY = 0;
                    this.logoKananValign = 'middle';
                    $wire.set('logo_kanan_width', 85);
                    $wire.set('logo_kanan_offset_x', 0);
                    $wire.set('logo_kanan_offset_y', 0);
                    $wire.set('logo_kanan_valign', 'middle');
                }
                this.kopGap = 10;
                $wire.set('kop_gap', 10);
            },

            startDragLeft(e) {
                e.preventDefault();
                this.isDraggingLeft = true;
                this.startX = e.clientX;
                this.startY = e.clientY;
                this.startOffsetX = parseInt(this.logoOffsetX || 0);
                this.startOffsetY = parseInt(this.logoOffsetY || 0);
                
                const onMouseMove = (ev) => {
                    if (!this.isDraggingLeft) return;
                    const deltaX = ev.clientX - this.startX;
                    const deltaY = ev.clientY - this.startY;
                    this.logoOffsetX = Math.max(-100, Math.min(100, this.startOffsetX + Math.round(deltaX)));
                    this.logoOffsetY = Math.max(-100, Math.min(100, this.startOffsetY + Math.round(deltaY)));
                };

                const onMouseUp = () => {
                    this.isDraggingLeft = false;
                    $wire.set('logo_offset_x', this.logoOffsetX);
                    $wire.set('logo_offset_y', this.logoOffsetY);
                    window.removeEventListener('mousemove', onMouseMove);
                    window.removeEventListener('mouseup', onMouseUp);
                };

                window.addEventListener('mousemove', onMouseMove);
                window.addEventListener('mouseup', onMouseUp);
            },

            startDragRight(e) {
                e.preventDefault();
                this.isDraggingRight = true;
                this.startX = e.clientX;
                this.startY = e.clientY;
                this.startOffsetX = parseInt(this.logoKananOffsetX || 0);
                this.startOffsetY = parseInt(this.logoKananOffsetY || 0);

                const onMouseMove = (ev) => {
                    if (!this.isDraggingRight) return;
                    const deltaX = ev.clientX - this.startX;
                    const deltaY = ev.clientY - this.startY;
                    this.logoKananOffsetX = Math.max(-100, Math.min(100, this.startOffsetX + Math.round(deltaX)));
                    this.logoKananOffsetY = Math.max(-100, Math.min(100, this.startOffsetY + Math.round(deltaY)));
                };

                const onMouseUp = () => {
                    this.isDraggingRight = false;
                    $wire.set('logo_kanan_offset_x', this.logoKananOffsetX);
                    $wire.set('logo_kanan_offset_y', this.logoKananOffsetY);
                    window.removeEventListener('mousemove', onMouseMove);
                    window.removeEventListener('mouseup', onMouseUp);
                };

                window.addEventListener('mousemove', onMouseMove);
                window.addEventListener('mouseup', onMouseUp);
            }
         }">

        {{-- STUDIO VISUAL KOP SURAT CARD --}}
        <div style="border-radius: 14px; overflow: hidden; border: 1px solid #334155; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3); background-color: #0f172a;">
            
            {{-- Header Toolbar Studio Visual --}}
            <div style="padding: 16px 22px; border-bottom: 1px solid #1e293b; background-color: #0b1120;">
                <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px;">
                    
                    {{-- Judul & Deskripsi --}}
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 10px; background-color: #1e293b; color: #38bdf8; border: 1px solid #334155; box-shadow: inset 0 1px 0 rgba(255,255,255,0.1);">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </span>
                        <div>
                            <h3 style="font-size: 15px; font-weight: 700; color: #f8fafc; margin: 0; line-height: 1.2;">Visual Kop Surat Studio</h3>
                            <p style="font-size: 12px; color: #94a3b8; margin: 3px 0 0 0;">Drag logo langsung di kertas atau gunakan pengatur ukuran & jarak di bawah.</p>
                        </div>
                    </div>

                    {{-- Tab Pemilih Logo & Tombol Simpan --}}
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        {{-- Toggle Seleksi Logo Sekunder --}}
                        <label style="display: inline-flex; align-items: center; gap: 7px; background-color: #1e293b; padding: 5px 11px; border-radius: 8px; border: 1px solid #334155; cursor: pointer; user-select: none;"
                               title="Centang untuk mengaktifkan logo sekunder di sisi kanan kop surat">
                            <input type="checkbox" 
                                   x-model="hasLogoKanan" 
                                   @change="if (!hasLogoKanan) { activeTab = 'left'; } $wire.set('has_logo_kanan', hasLogoKanan);"
                                   style="accent-color: #0284c7; width: 14px; height: 14px; cursor: pointer;">
                            <span style="font-size: 11.5px; font-weight: 600; color: #e2e8f0;">Logo Sekunder (Kanan)</span>
                        </label>

                        <div style="display: inline-flex; background-color: #1e293b; padding: 3px; border-radius: 8px; border: 1px solid #334155; gap: 4px;">
                            <button type="button" 
                                    @click="activeTab = 'left'" 
                                    :style="activeTab === 'left' ? 'background-color: #0284c7; color: #ffffff; font-weight: 700; box-shadow: 0 1px 3px rgba(0,0,0,0.3);' : 'background-color: transparent; color: #94a3b8; font-weight: 500;'"
                                    style="padding: 6px 14px; border-radius: 6px; font-size: 12px; border: none; cursor: pointer; transition: all 0.2s;">
                                Logo Utama (Kiri)
                            </button>
                            <button type="button" 
                                    x-show="hasLogoKanan"
                                    @click="activeTab = 'right'" 
                                    :style="activeTab === 'right' ? 'background-color: #0284c7; color: #ffffff; font-weight: 700; box-shadow: 0 1px 3px rgba(0,0,0,0.3);' : 'background-color: transparent; color: #94a3b8; font-weight: 500;'"
                                    style="padding: 6px 14px; border-radius: 6px; font-size: 12px; border: none; cursor: pointer; transition: all 0.2s;">
                                Logo Sekunder (Kanan)
                            </button>
                        </div>

                        <button type="button" 
                                wire:click="save"
                                style="background-color: #16a34a; color: #ffffff; font-weight: 700; font-size: 12.5px; padding: 7px 18px; border-radius: 8px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.25); transition: background-color 0.2s;"
                                onmouseover="this.style.backgroundColor='#15803d'"
                                onmouseout="this.style.backgroundColor='#16a34a'">
                            <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>

                {{-- Baris Panel Kontrol Terstruktur & Rapi --}}
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 16px; padding-top: 14px; border-top: 1px solid #1e293b;">
                    
                    {{-- Panel 1: Ukuran Logo & Jarak Celah --}}
                    <div style="background-color: #131d31; padding: 12px 14px; border-radius: 10px; border: 1px solid #1e293b;">
                        {{-- Slider Ukuran --}}
                        <div style="margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; margin-bottom: 4px;">
                                <span style="color: #cbd5e1; font-weight: 600;">Ukuran Logo:</span>
                                <span style="font-weight: 700; font-family: monospace; padding: 1px 6px; border-radius: 4px; background-color: #0f172a; color: #38bdf8; border: 1px solid #334155; font-size: 11px;" 
                                      x-text="activeTab === 'left' ? `${logoWidth || 85} px` : `${logoKananWidth || 85} px`">
                                </span>
                            </div>
                            <template x-if="activeTab === 'left'">
                                <input type="range" min="40" max="160" step="2" x-model.number="logoWidth" 
                                       @input="$wire.set('logo_width', logoWidth)"
                                       style="width: 100%; accent-color: #38bdf8; cursor: pointer; height: 5px;">
                            </template>
                            <template x-if="activeTab === 'right'">
                                <input type="range" min="40" max="160" step="2" x-model.number="logoKananWidth" 
                                       @input="$wire.set('logo_kanan_width', logoKananWidth)"
                                       style="width: 100%; accent-color: #38bdf8; cursor: pointer; height: 5px;">
                            </template>
                        </div>

                        {{-- Slider Jarak Gap --}}
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; margin-bottom: 4px;">
                                <span style="color: #cbd5e1; font-weight: 600;">Jarak / Celah Logo-Teks:</span>
                                <span style="font-weight: 700; font-family: monospace; padding: 1px 6px; border-radius: 4px; background-color: #0f172a; color: #c084fc; border: 1px solid #334155; font-size: 11px;" 
                                      x-text="`${kopGap !== null && kopGap !== undefined && !isNaN(kopGap) ? kopGap : 10} px`">
                                </span>
                            </div>
                            <input type="range" min="0" max="60" step="1" x-model.number="kopGap" 
                                   @input="$wire.set('kop_gap', kopGap)"
                                   style="width: 100%; accent-color: #c084fc; cursor: pointer; height: 5px;">
                        </div>
                    </div>

                    {{-- Panel 2: Preset Ukuran Cepat --}}
                    <div style="background-color: #131d31; padding: 12px 14px; border-radius: 10px; border: 1px solid #1e293b; display: flex; flex-direction: column; justify-content: space-between;">
                        <span style="font-size: 11.5px; color: #cbd5e1; font-weight: 600; display: block; margin-bottom: 8px;">Preset Ukuran Logo:</span>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
                            <button type="button" @click="setSize(65)" style="padding: 6px 2px; font-size: 11px; background-color: #1e293b; color: #f1f5f9; border: 1px solid #334155; border-radius: 6px; cursor: pointer; font-weight: 500; text-align: center;">Kecil<br><span style="font-size: 9.5px; color: #94a3b8;">65px</span></button>
                            <button type="button" @click="setSize(85)" style="padding: 6px 2px; font-size: 11px; background-color: #1e293b; color: #f1f5f9; border: 1px solid #334155; border-radius: 6px; cursor: pointer; font-weight: 500; text-align: center;">Standar<br><span style="font-size: 9.5px; color: #94a3b8;">85px</span></button>
                            <button type="button" @click="setSize(110)" style="padding: 6px 2px; font-size: 11px; background-color: #1e293b; color: #f1f5f9; border: 1px solid #334155; border-radius: 6px; cursor: pointer; font-weight: 500; text-align: center;">Besar<br><span style="font-size: 9.5px; color: #94a3b8;">110px</span></button>
                            <button type="button" @click="setSize(140)" style="padding: 6px 2px; font-size: 11px; background-color: #1e293b; color: #f1f5f9; border: 1px solid #334155; border-radius: 6px; cursor: pointer; font-weight: 500; text-align: center;">Ekstra<br><span style="font-size: 9.5px; color: #94a3b8;">140px</span></button>
                        </div>
                    </div>

                    {{-- Panel 3: Perataan Vertikal & Reset --}}
                    <div style="background-color: #131d31; padding: 12px 14px; border-radius: 10px; border: 1px solid #1e293b; display: flex; flex-direction: column; justify-content: space-between;">
                        <span style="font-size: 11.5px; color: #cbd5e1; font-weight: 600; display: block; margin-bottom: 8px;">Perataan Vertikal Logo:</span>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <div style="display: flex; flex: 1; background-color: #0f172a; padding: 2px; border-radius: 6px; border: 1px solid #334155; gap: 2px;">
                                <button type="button" @click="setValign('top')" 
                                        :style="(activeTab === 'left' ? logoValign : logoKananValign) === 'top' ? 'background-color: #334155; color: #ffffff; font-weight: 700;' : 'background-color: transparent; color: #94a3b8;'"
                                        style="flex: 1; padding: 5px 4px; font-size: 11px; border-radius: 4px; border: none; cursor: pointer;">Atas</button>
                                <button type="button" @click="setValign('middle')" 
                                        :style="(activeTab === 'left' ? logoValign : logoKananValign) === 'middle' ? 'background-color: #334155; color: #ffffff; font-weight: 700;' : 'background-color: transparent; color: #94a3b8;'"
                                        style="flex: 1; padding: 5px 4px; font-size: 11px; border-radius: 4px; border: none; cursor: pointer;">Tengah</button>
                                <button type="button" @click="setValign('bottom')" 
                                        :style="(activeTab === 'left' ? logoValign : logoKananValign) === 'bottom' ? 'background-color: #334155; color: #ffffff; font-weight: 700;' : 'background-color: transparent; color: #94a3b8;'"
                                        style="flex: 1; padding: 5px 4px; font-size: 11px; border-radius: 4px; border: none; cursor: pointer;">Bawah</button>
                            </div>
                            <button type="button" @click="resetPositions()" title="Kembalikan semua posisi & jarak ke standar" style="padding: 6px 12px; font-size: 11px; background-color: #881337; color: #fecdd3; border: 1px solid #be123c; border-radius: 6px; cursor: pointer; font-weight: 600;">Reset</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KANVAS KERTAS DOKUMEN KOP SURAT (Pure High-Contrast White Sheet) --}}
            <div style="background-color: #0b1120; padding: 28px 20px; display: flex; justify-content: center; overflow-x: auto;">
                <div style="width: 100%; max-width: 820px; background-color: #ffffff !important; color: #000000 !important; padding: 32px 40px; border-radius: 6px; box-shadow: 0 15px 35px -5px rgba(0,0,0,0.4); border: 1px solid #e2e8f0; font-family: 'Times New Roman', Times, serif; user-select: none;">
                    
                    {{-- Kop Surat Simetris Standar Instansi Pendidikan (100% Senter Presisi Kertas) --}}
                    <div style="position: relative; width: 100%; min-height: 85px; color: #000000 !important;">
                        
                        {{-- Logo Utama (Kiri) dengan Drag Handle --}}
                        <div style="position: absolute; left: 0; z-index: 10;"
                             :style="{ 
                                 top: (logoValign === 'top' ? '0px' : (logoValign === 'bottom' ? 'auto' : '50%')),
                                 bottom: (logoValign === 'bottom' ? '0px' : 'auto'),
                                 transform: (logoValign === 'middle' ? `translate(${logoOffsetX || 0}px, calc(-50% + ${logoOffsetY || 0}px))` : `translate(${logoOffsetX || 0}px, ${logoOffsetY || 0}px)`)
                             }">
                            <div class="group"
                                 style="position: relative; display: inline-block; cursor: grab; padding: 2px; border-radius: 6px;"
                                 @mousedown="startDragLeft($event)">
                                
                                @if($logoUtamaUrl)
                                    <img src="{{ $logoUtamaUrl }}" 
                                         alt="Logo Kiri" 
                                         draggable="false"
                                         style="display: block; max-width: none; pointer-events: none; user-select: none;"
                                         :style="{ width: `${logoWidth || 85}px` }">
                                @else
                                    <div style="border: 2px dashed #94a3b8; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 8px; color: #64748b; background-color: #f8fafc;"
                                         :style="{ width: `${logoWidth || 85}px`, height: `${logoWidth || 85}px` }">
                                        <span style="font-size: 10px; font-family: sans-serif; font-weight: bold;">Logo Kiri</span>
                                    </div>
                                @endif

                                {{-- Tooltip Geser --}}
                                <div style="position: absolute; top: -26px; left: 50%; transform: translateX(-50%); background-color: #0f172a; color: #38bdf8; font-size: 10px; font-family: monospace; padding: 2px 6px; border-radius: 4px; white-space: nowrap; pointer-events: none; opacity: 0;"
                                     class="group-hover:opacity-100 transition-opacity">
                                    ✥ Drag <span x-text="`(${logoOffsetX || 0}, ${logoOffsetY || 0})`"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Teks Kop Surat: 100% Senter Presisi Kertas (Sejajar Sumbu Tengah Halaman) --}}
                        <div style="width: 100%; text-align: center; margin: 0 auto; color: #000000 !important; font-family: 'Times New Roman', Times, serif; box-sizing: border-box;"
                             :style="{ 
                                 paddingLeft: `${getSideWidth()}px`,
                                 paddingRight: `${getSideWidth()}px`
                             }">
                            <div style="font-size: 12.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2; color: #000000 !important;"
                                 x-text="kopLine1 || '{{ $settingsInstance->kop_line_1 ?? 'PEMERINTAH KOTA SURAKARTA' }}'">
                                {{ $settingsInstance->kop_line_1 ?? 'PEMERINTAH KOTA SURAKARTA' }}
                            </div>
                            
                            <div style="font-size: 14.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.8px; margin-top: 2px; line-height: 1.2; color: #000000 !important;"
                                 x-text="kopLine2 || '{{ $settingsInstance->kop_line_2 ?? 'DINAS PENDIDIKAN' }}'">
                                {{ $settingsInstance->kop_line_2 ?? 'DINAS PENDIDIKAN' }}
                            </div>
                            
                            <div style="font-size: 17px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; line-height: 1.2; color: #000000 !important;"
                                 x-text="namaSekolah || '{{ $settingsInstance->nama_sekolah ?? 'SEKOLAH MENENGAH ATAS NEGERI 1 CONTOH' }}'">
                                {{ $settingsInstance->nama_sekolah ?? 'SEKOLAH MENENGAH ATAS NEGERI 1 CONTOH' }}
                            </div>
                            
                            <div style="font-size: 11px; margin-top: 3px; line-height: 1.25; color: #1e293b !important; font-family: Arial, Helvetica, sans-serif;"
                                 x-text="(alamat || '{{ $settingsInstance->alamat ?? 'Jl. Contoh No. 123' }}') + (kodePos ? ', ' + kodePos : '{{ !empty($settingsInstance->kode_pos) ? ', ' . $settingsInstance->kode_pos : '' }}')">
                                {{ $settingsInstance->alamat ?? 'Jl. Contoh No. 123' }}{{ !empty($settingsInstance->kode_pos) ? ', ' . $settingsInstance->kode_pos : '' }}
                            </div>
                            
                            <div style="font-size: 10.5px; margin-top: 2px; line-height: 1.25; color: #334155 !important; font-family: Arial, Helvetica, sans-serif;"
                                 x-text="[
                                    (telepon ? 'Telp. ' + telepon : '{{ !empty($settingsInstance->telepon) ? 'Telp. ' . $settingsInstance->telepon : '' }}'),
                                    (email ? 'Email: ' + email : '{{ !empty($settingsInstance->email) ? 'Email: ' . $settingsInstance->email : '' }}'),
                                    (website ? website : '{{ !empty($settingsInstance->website) ? $settingsInstance->website : '' }}'),
                                    (npsn ? 'NPSN: ' + npsn : '{{ !empty($settingsInstance->npsn) ? 'NPSN: ' . $settingsInstance->npsn : '' }}'),
                                    (akreditasi ? (akreditasi.toLowerCase().includes('akreditasi') ? akreditasi : 'Akreditasi: ' + akreditasi) : '{{ !empty($settingsInstance->formatted_akreditasi) ? $settingsInstance->formatted_akreditasi : '' }}')
                                 ].filter(Boolean).join(' | ') || 'Telp. (021) 1234567 | Email: info@sekolah.sch.id | www.sekolah.sch.id'">
                                {{ implode(' | ', array_filter([$settingsInstance->kontak_lengkap, $settingsInstance->npsn ? ('NPSN: ' . $settingsInstance->npsn) : null, $settingsInstance->formatted_akreditasi])) ?: 'Telp. (021) 1234567 | Email: info@sekolah.sch.id | www.sekolah.sch.id' }}
                            </div>
                        </div>

                        {{-- Logo Sekunder (Kanan) --}}
                        <template x-if="hasLogoKanan">
                            <div style="position: absolute; right: 0; z-index: 10;"
                                 :style="{ 
                                     top: (logoKananValign === 'top' ? '0px' : (logoKananValign === 'bottom' ? 'auto' : '50%')),
                                     bottom: (logoKananValign === 'bottom' ? '0px' : 'auto'),
                                     transform: (logoKananValign === 'middle' ? `translate(${logoKananOffsetX || 0}px, calc(-50% + ${logoKananOffsetY || 0}px))` : `translate(${logoKananOffsetX || 0}px, ${logoKananOffsetY || 0}px)`)
                                 }">
                                <div class="group"
                                     style="position: relative; display: inline-block; cursor: grab; padding: 2px; border-radius: 6px;"
                                     @mousedown="startDragRight($event)">
                                    
                                    @if($logoKananUrl)
                                        <img src="{{ $logoKananUrl }}" 
                                             alt="Logo Kanan" 
                                             draggable="false"
                                             style="display: block; max-width: none; pointer-events: none; user-select: none;"
                                             :style="{ width: `${logoKananWidth || 85}px` }">
                                    @else
                                        <div style="border: 2px dashed #94a3b8; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 8px; color: #64748b; background-color: #f8fafc;"
                                             :style="{ width: `${logoKananWidth || 85}px`, height: `${logoKananWidth || 85}px` }">
                                            <span style="font-size: 10px; font-family: sans-serif; font-weight: bold;">Logo Kanan</span>
                                        </div>
                                    @endif

                                    <div style="position: absolute; top: -26px; left: 50%; transform: translateX(-50%); background-color: #0f172a; color: #38bdf8; font-size: 10px; font-family: monospace; padding: 2px 6px; border-radius: 4px; white-space: nowrap; pointer-events: none; opacity: 0;"
                                         class="group-hover:opacity-100 transition-opacity">
                                        ✥ Drag <span x-text="`(${logoKananOffsetX || 0}, ${logoKananOffsetY || 0})`"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Garis Pembatas Ganda Kop Surat --}}
                    <div style="margin-top: 10px; border-top: 3px double #000000; padding-top: 2px; width: 100%;"></div>
                </div>
            </div>
        </div>

        {{-- FORMULIR PENGATURAN FILAMENT --}}
        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}

            <x-filament-panels::form.actions 
                :actions="$this->getFormActions()"
            />
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>