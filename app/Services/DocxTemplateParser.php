<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;
use ZipArchive;

/**
 * Service: DocxTemplateParser
 *
 * Bertanggung jawab mengekstrak dokumen Word (.docx) menjadi struktur template E-Surat:
 * - Membaca isi paragraf dan tabel dari word/document.xml melalui ZipArchive native PHP.
 * - Mendeteksi pola variabel: kurung kurawal {{ var }}, kurung siku [var], dan pola titik-titik isian (Nama: ......).
 * - Menghasilkan representasi HTML dan pemetaan ke No-Code Visual Builder.
 */
class DocxTemplateParser
{
    /**
     * Parse file .docx dari path file fisik.
     *
     * @param  string  $filePath  Path absolut file .docx
     * @return array{
     *     name: string,
     *     title_text: string,
     *     letter_code: string,
     *     opening_text: string,
     *     middle_text: string,
     *     closing_text: string,
     *     identity_fields: array<int, string>,
     *     detail_fields: array<int, string>,
     *     content: string,
     *     variables: array<int, string>,
     *     use_advanced_html: bool
     * }
     */
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("File tidak ditemukan: {$filePath}");
        }

        $xmlContent = $this->extractDocumentXml($filePath);
        $paragraphs = $this->extractParagraphsAndTables($xmlContent);

        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        $cleanName = Str::headline(preg_replace('/[~_\-\.]/', ' ', $filename));

        return $this->buildTemplateStructure($paragraphs, $cleanName);
    }

    /**
     * Ekstrak isi XML dokumen dari berkas .docx.
     */
    protected function extractDocumentXml(string $filePath): string
    {
        $zip = new ZipArchive();
        $status = $zip->open($filePath);

        if ($status !== true) {
            throw new \RuntimeException("Gagal membuka berkas docx (kode: {$status})");
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException("Berkas word/document.xml tidak ditemukan di dalam docx.");
        }

        return $xml;
    }

    /**
     * Ekstrak teks paragraf dan tabel secara berurutan.
     *
     * @return array<int, array{type: 'p'|'tbl', text?: string, rows?: array<int, array<int, string>>}>
     */
    protected function extractParagraphsAndTables(string $xmlContent): array
    {
        $items = [];
        $dom = new \DOMDocument();
        $previousState = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xmlContent, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($previousState);

        if (! $loaded) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $bodyNodes = $xpath->query('//w:body/*');
        if (! $bodyNodes) {
            return [];
        }

        foreach ($bodyNodes as $node) {
            if ($node->localName === 'p') {
                $textNodes = $xpath->query('.//w:t', $node);
                $pText = '';
                if ($textNodes) {
                    foreach ($textNodes as $t) {
                        $pText .= $t->textContent;
                    }
                }
                $pText = trim($pText);
                if ($pText !== '') {
                    $items[] = [
                        'type' => 'p',
                        'text' => $pText,
                    ];
                }
            } elseif ($node->localName === 'tbl') {
                $rowNodes = $xpath->query('.//w:tr', $node);
                $tableData = [];

                if ($rowNodes) {
                    foreach ($rowNodes as $row) {
                        $cellNodes = $xpath->query('.//w:tc', $row);
                        $rowCells = [];

                        if ($cellNodes) {
                            foreach ($cellNodes as $cell) {
                                $cellTextNodes = $xpath->query('.//w:t', $cell);
                                $cText = '';
                                if ($cellTextNodes) {
                                    foreach ($cellTextNodes as $ct) {
                                        $cText .= $ct->textContent;
                                    }
                                }
                                $rowCells[] = trim($cText);
                            }
                        }

                        if (! empty(array_filter($rowCells))) {
                            $tableData[] = $rowCells;
                        }
                    }
                }

                if (! empty($tableData)) {
                    $items[] = [
                        'type' => 'tbl',
                        'rows' => $tableData,
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Susun struktur template E-Surat dari hasil ekstraksi paragraf & tabel.
     *
     * @param  array<int, array{type: 'p'|'tbl', text?: string, rows?: array<int, array<int, string>>}>  $elements
     * @return array{
     *     name: string,
     *     title_text: string,
     *     letter_code: string,
     *     opening_text: string,
     *     middle_text: string,
     *     closing_text: string,
     *     identity_fields: array<int, string>,
     *     detail_fields: array<int, string>,
     *     content: string,
     *     variables: array<int, string>,
     *     use_advanced_html: bool
     * }
     */
    protected function buildTemplateStructure(array $elements, string $fallbackName): array
    {
        $detectedTitle = '';
        $paragraphsText = [];
        $htmlParts = [];
        $allVariables = [];

        foreach ($elements as $el) {
            if ($el['type'] === 'p') {
                $rawText = $el['text'] ?? '';

                // Deteksi kemungkinan judul surat (huruf kapital dengan kata SURAT / KEPUTUSAN / PERNYATAAN / dsb.)
                if (empty($detectedTitle) && $this->isProbableLetterTitle($rawText)) {
                    $detectedTitle = $this->normalizeTitle($rawText);
                    continue;
                }

                // Abaikan baris nomor surat / lampiran / hal di header dokumen karena sistem E-Surat sudah memiliki kop & nomor otomatis
                if ($this->isHeaderMetaLine($rawText)) {
                    continue;
                }

                // Abaikan baris tanda tangan di bawah dokumen karena sistem E-Surat sudah otomatis menambahkan titi mangsa & QR kepsek
                if ($this->isFooterSignatureLine($rawText)) {
                    continue;
                }

                $transformedText = $this->convertPlaceholders($rawText);
                $paragraphsText[] = $transformedText;
                $htmlParts[] = '<p style="margin: 6px 0; text-align: justify; line-height: 1.5;">' . e($transformedText) . '</p>';

                $this->extractVariablesFromString($transformedText, $allVariables);
            } elseif ($el['type'] === 'tbl') {
                $rows = $el['rows'] ?? [];
                $tableHtml = '<table style="width: 100%; margin: 10px 0; border-collapse: collapse;">';

                foreach ($rows as $rowIndex => $cells) {
                    $tableHtml .= '<tr>';
                    $colCount = count($cells);

                    foreach ($cells as $colIndex => $cellVal) {
                        $cellTransformed = $this->convertPlaceholders($cellVal);
                        $this->extractVariablesFromString($cellTransformed, $allVariables);

                        $isHeader = ($rowIndex === 0 && count($rows) > 1);
                        $tag = $isHeader ? 'th' : 'td';
                        $borderStyle = 'border: 1px solid #ddd; padding: 5px 8px;';

                        // Jika format key: value (2 atau 3 kolom tanpa border penuh)
                        if ($colCount <= 3 && ! $isHeader && ($cells[1] ?? '') === ':') {
                            $borderStyle = 'padding: 4px 0; border: none;';
                        }

                        $tableHtml .= "<{$tag} style=\"{$borderStyle}\">" . e($cellTransformed) . "</{$tag}>";
                    }
                    $tableHtml .= '</tr>';
                }
                $tableHtml .= '</table>';
                $htmlParts[] = $tableHtml;
            }
        }

        // Tentukan nama template
        $templateTitle = ! empty($detectedTitle) ? $detectedTitle : $fallbackName;
        $letterCode = $this->generateLetterCode($templateTitle);

        // Klasifikasi paragraf ke opening, middle, closing untuk No-Code form
        [$openingText, $middleText, $closingText] = $this->classifyParagraphs($paragraphsText);

        // Identifikasi field identitas & detail
        $identityFields = [];
        $detailFields = [];

        $identityKeywords = [
            'nama', 'nip', 'jabatan', 'alamat', 'sekolah', 'nisn', 'nis',
            'tempat_tanggal_lahir', 'ttl', 'jenis_kelamin', 'kelas', 'jurusan',
            'nama_orang_tua', 'pekerjaan_orang_tua', 'no_hp', 'mata_pelajaran',
        ];
        foreach ($allVariables as $var) {
            if (in_array(strtolower($var), $identityKeywords, true)) {
                $identityFields[] = strtolower($var);
            } else {
                $detailFields[] = strtolower($var);
            }
        }

        if (empty($identityFields)) {
            $identityFields = ['nama', 'nip', 'jabatan'];
        }

        // Susun HTML lengkap
        $fullHtml = implode("\n", $htmlParts);

        // Jika HTML kosong karena file tidak ada paragraf utama, buat fallback HTML
        if (trim($fullHtml) === '') {
            $fullHtml = '<p>Yang bertanda tangan di bawah ini menerangkan bahwa:</p>' .
                        '<p>Nama: {{ nama }}</p>' .
                        '<p>NIP: {{ nip }}</p>' .
                        '<p>Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya.</p>';
            $allVariables = ['nama', 'nip'];
        }

        return [
            'name' => $templateTitle,
            'title_text' => strtoupper($templateTitle),
            'letter_code' => $letterCode,
            'opening_text' => $openingText,
            'middle_text' => $middleText,
            'closing_text' => $closingText,
            'identity_fields' => array_values(array_unique($identityFields)),
            'detail_fields' => array_values(array_unique($detailFields)),
            'content' => $fullHtml,
            'variables' => array_values(array_unique($allVariables)),
            'use_advanced_html' => false,
        ];
    }

    /**
     * Deteksi apakah teks merupakan judul surat resmi.
     */
    protected function isProbableLetterTitle(string $text): bool
    {
        $upper = strtoupper(trim(preg_replace('/\s+/', ' ', $text)));

        $titlePrefixes = [
            'SURAT TUGAS',
            'SURAT DISPENSASI',
            'SURAT PERINGATAN',
            'SURAT PERMOHONAN',
            'SURAT KEPUTUSAN',
            'SURAT KETERANGAN',
            'SURAT PANGGILAN',
            'SURAT REKOMENDASI',
            'SURAT UNDANGAN',
            'SURAT PERNYATAAN',
            'SURAT PEMBERITAHUAN',
            'SURAT PENGANTAR',
            'SURAT IZIN',
            'SURAT IJIN',
            'SURAT PINDAH',
            'SURAT KUASA',
            'SPPD',
            'SURAT PERINTAH PERJALANAN DINAS',
        ];

        foreach ($titlePrefixes as $prefix) {
            if (str_starts_with($upper, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalisasi teks judul surat (menghilangkan spasi renggang S U R A T).
     */
    protected function normalizeTitle(string $rawTitle): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($rawTitle));

        // Normalisasi format "S U R A T   T U G A S" -> "SURAT TUGAS"
        $clean = preg_replace_callback('/\b([A-Z])(?:\s+([A-Z]))+\b/', function ($m) {
            return str_replace(' ', '', $m[0]);
        }, $clean);

        return strtoupper($clean);
    }

    /**
     * Filter baris header metadata seperti No, Lamp, Hal.
     */
    protected function isHeaderMetaLine(string $text): bool
    {
        $lower = strtolower(trim($text));

        return str_starts_with($lower, 'nomor:') ||
               str_starts_with($lower, 'no.:') ||
               str_starts_with($lower, 'no :') ||
               str_starts_with($lower, 'lamp:') ||
               str_starts_with($lower, 'lampiran:') ||
               str_starts_with($lower, 'hal:') ||
               str_starts_with($lower, 'perihal:');
    }

    /**
     * Filter baris tanda tangan di footer.
     */
    protected function isFooterSignatureLine(string $text): bool
    {
        $lower = strtolower(trim($text));

        return str_starts_with($lower, 'kepala sekolah') ||
               str_starts_with($lower, 'waka kesiswaan') ||
               str_starts_with($lower, 'mengetahui,') ||
               preg_match('/^majenang,\s*\d+\s+[a-z]+\s+\d{4}/i', $lower) === 1;
    }

    /**
     * Konversi berbagai format placeholder ke format standar {{ variabel }}.
     *
     * Contoh:
     * - [nama] -> {{ nama }}
     * - {nama} -> {{ nama }}
     * - Nama : .......... -> Nama : {{ nama }}
     */
    public function convertPlaceholders(string $text): string
    {
        // 1. Tangani kurung siku [var] -> {{ var }}
        $text = preg_replace('/\[([a-zA-Z0-9_]+)\]/', '{{ $1 }}', $text);

        // 2. Tangani kurung kurawal tunggal yang bukan ganda {var} -> {{ var }}
        $text = preg_replace('/(?<!\{)\{([a-zA-Z0-9_]+)\}(?!\})/', '{{ $1 }}', $text);

        // 3. Tangani pola baris titik-titik isian umum (urutan: frasa spesifik/majemuk lebih dahulu daripada kata tunggal)
        $replacements = [
            // Identitas & keluarga spesifik
            '/(nama\s+(?:orang\s+tua|ortu|ayah|ibu)|orang\s+tua|wali)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ nama_orang_tua }}',
            '/(pekerjaan(?:\s+orang\s+tua|\s+ortu)?)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ pekerjaan_orang_tua }}',
            '/(tempat(?:[\s\,\/]+tanggal)?\s+lahir|ttl)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ tempat_tanggal_lahir }}',
            '/(jenis\s+kelamin|jk)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ jenis_kelamin }}',
            '/(nama(?:\s+lengkap)?)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ nama }}',
            '/(nip)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ nip }}',
            '/(nisn)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ nisn }}',
            '/(?<![a-zA-Z0-9])(nis)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ nis }}',
            '/(kelas)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ kelas }}',
            '/(jurusan|kompetensi\s+keahlian|program\s+keahlian)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ jurusan }}',
            '/(jabatan)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ jabatan }}',
            '/(mata\s+pelajaran(?:[\s\w]+diampu)?|mapel)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ mata_pelajaran }}',
            '/(alamat(?:[\s\/]+domisili)?)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ alamat }}',
            '/(no(?:mor)?\s*(?:hp|telepon|telp|wa))\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ no_hp }}',

            // Keperluan, tujuan, & sekolah majemuk
            '/(sekolah\s+(?:yang\s+)?dituju|sekolah\s+tujuan)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ sekolah_tujuan }}',
            '/(alasan\s+pindah|alasan\s+kepindahan)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ alasan_pindah }}',
            '/(sekolah|unit\s+kerja)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ sekolah }}',
            '/(tujuan(?:\s+dinas)?)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ tujuan }}',
            '/(keperluan|maksud)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ keperluan }}',

            // Tanggal, waktu, tempat
            '/(tanggal\s+berangkat)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ tanggal_berangkat }}',
            '/(tanggal\s+kembali)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ tanggal_kembali }}',
            '/(hari\s*[\/\,]\s*tanggal)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ hari_tanggal }}',
            '/(waktu|pukul)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ waktu }}',
            '/(tempat|lokasi)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ tempat }}',

            // SPPD, Perintah & Anggaran
            '/(pejabat\s+(?:yang\s+)?memberi\s+perintah|pejabat\s+pemberi\s+perintah)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ pejabat_pemberi_perintah }}',
            '/(pangkat(?:[\s\,\/]+golongan)?)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ pangkat_golongan }}',
            '/(tingkat\s+biaya(?:[\s\w]+dinas)?)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ tingkat_biaya }}',
            '/(atas\s+beban|beban\s+anggaran|pasal\s+anggaran)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ beban_anggaran }}',
            '/(lamanya\s+perjalanan|lama\s+perjalanan)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ lama_perjalanan }}',

            // Tata tertib & pembinaan
            '/(bentuk\s+pelanggaran|uraian\s+pelanggaran|melanggar)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ bentuk_pelanggaran }}',
            '/(poin\s+pelanggaran|skor\s+pelanggaran)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ poin_pelanggaran }}',
            '/(tindakan(?:\s+pembinaan)?|sanksi)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ tindakan_pembinaan }}',

            // DUDI, Kelulusan & Lainnya
            '/(dudi|mitra\s+industri|nama\s+perusahaan|nama\s+instansi)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ dudi_mitra }}',
            '/(tahun\s+lulus|tahun\s+kelulusan)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ tahun_lulus }}',
            '/(no(?:mor)?\s*(?:seri\s*)?(?:ijazah|sttb))\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ no_ijazah }}',
            '/(keterangan)\s*:\s*[\.\_\-]{3,}/i' => '$1: {{ keterangan }}',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    /**
     * Ekstrak nama variabel {{ nama_var }} ke dalam daftar variabel.
     *
     * @param  array<int, string>  $allVariables
     */
    protected function extractVariablesFromString(string $text, array &$allVariables): void
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $text, $matches);
        if (! empty($matches[1])) {
            foreach ($matches[1] as $var) {
                $cleanVar = strtolower(trim($var));
                if (! in_array($cleanVar, $allVariables, true)) {
                    $allVariables[] = $cleanVar;
                }
            }
        }
    }

    /**
     * Mengklasifikasikan paragraf yang diekstrak menjadi pembuka, antara, dan penutup.
     *
     * @param  array<int, string>  $paragraphs
     * @return array{0: string, 1: string, 2: string}
     */
    protected function classifyParagraphs(array $paragraphs): array
    {
        $opening = 'Yang bertanda tangan di bawah ini Kepala Sekolah menerangkan bahwa:';
        $middle = '';
        $closing = 'Demikian surat ini dibuat untuk dipergunakan sebagaimana mestinya dan dilaksanakan dengan penuh tanggung jawab.';

        $cleanParagraphs = array_values(array_filter($paragraphs, function ($p) {
            return strlen(trim($p)) > 10 && ! str_starts_with(strtolower($p), 'kepada yth');
        }));

        if (count($cleanParagraphs) >= 1) {
            $opening = $cleanParagraphs[0];
        }

        if (count($cleanParagraphs) >= 3) {
            $middle = $cleanParagraphs[1];
            $closing = $cleanParagraphs[count($cleanParagraphs) - 1];
        } elseif (count($cleanParagraphs) === 2) {
            $closing = $cleanParagraphs[1];
        }

        return [$opening, $middle, $closing];
    }

    /**
     * Buat kode surat singkat dari judul surat.
     */
    protected function generateLetterCode(string $title): string
    {
        $titleUpper = strtoupper($title);

        if (str_contains($titleUpper, 'DISPENSASI')) {
            return 'DISPEN';
        }
        if (str_contains($titleUpper, 'PANGGILAN')) {
            return 'SP-ORTU';
        }
        if (str_contains($titleUpper, 'PERINGATAN')) {
            return str_contains($titleUpper, 'SISWA') ? 'SP-SISWA' : 'SP-GUKAR';
        }
        if (str_contains($titleUpper, 'HOME VISIT')) {
            return 'SHV';
        }
        if (str_contains($titleUpper, 'SPPD') || str_contains($titleUpper, 'PERJALANAN DINAS')) {
            return 'SPPD';
        }
        if (str_contains($titleUpper, 'TUGAS')) {
            return str_contains($titleUpper, 'SISWA') ? 'ST-SISWA' : 'ST-GUKAR';
        }
        if (str_contains($titleUpper, 'REKOMENDASI')) {
            return 'REKOM';
        }
        if (str_contains($titleUpper, 'PINDAH')) {
            return 'SK-PINDAH';
        }
        if (str_contains($titleUpper, 'UNDANGAN')) {
            return 'UND';
        }
        if (str_contains($titleUpper, 'KETERANGAN')) {
            return 'SK';
        }
        if (str_contains($titleUpper, 'IZIN') || str_contains($titleUpper, 'IJIN')) {
            return 'SI';
        }

        // Singkatan umum dari kata-kata
        $words = explode(' ', preg_replace('/[^A-Za-z0-9 ]/', '', $titleUpper));
        $code = '';
        foreach ($words as $w) {
            if (strlen($w) > 2 && ! in_array($w, ['DAN', 'YANG', 'UNTUK', 'DENGAN'])) {
                $code .= substr($w, 0, 1);
            }
        }

        return substr($code ?: 'SURAT', 0, 8);
    }
}
