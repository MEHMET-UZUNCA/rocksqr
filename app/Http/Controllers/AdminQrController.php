<?php

namespace App\Http\Controllers;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class AdminQrController extends Controller
{
    public function index()
    {
        return $this->renderIndex();
    }

    public function preview(Request $request)
    {
        $tableNumbers = $this->resolveTableNumbers($request);

        return $this->renderIndex($this->buildQrPayload($tableNumbers), $this->extractFormData($request));
    }

    public function download(Request $request)
    {
        $tableNumbers = $this->resolveTableNumbers($request);

        if (!class_exists(ZipArchive::class)) {
            return back()->withErrors(['zip' => 'ZIP oluşturma desteği sunucuda aktif değil.']);
        }

        $zipPath = storage_path('app/temp/table-qrs-'.now()->format('Ymd-His').'.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive();
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            return back()->withErrors(['zip' => 'QR ZIP dosyası oluşturulamadı.']);
        }

        $this->addQrsToZip($zip, $this->buildQrPayload($tableNumbers));

        $zip->close();

        return response()->download($zipPath, 'rocks-masa-qr.zip')->deleteFileAfterSend(true);
    }

    public function print(Request $request)
    {
        $tableNumbers = $this->resolveTableNumbers($request);

        return view('admin.qr-print', [
            'generatedQrs' => $this->buildQrPayload($tableNumbers),
            'title' => 'Masa QR Baskı Şablonu',
            'subtitle' => $this->describeTables($tableNumbers),
        ]);
    }

    public function save(Request $request)
    {
        $tableNumbers = $this->resolveTableNumbers($request);
        $qrs = $this->buildQrPayload($tableNumbers);
        $archiveId = now()->format('Ymd-His').'-'.Str::lower(Str::random(6));
        $archivePath = $this->archivePath($archiveId);

        File::ensureDirectoryExists($archivePath);

        foreach ($qrs as $qr) {
            File::put($archivePath.DIRECTORY_SEPARATOR.$qr['filename'], base64_decode($qr['image_base64']));
        }

        File::put($archivePath.DIRECTORY_SEPARATOR.'manifest.json', json_encode([
            'id' => $archiveId,
            'created_at' => now()->toIso8601String(),
            'table_numbers' => $tableNumbers,
            'count' => count($tableNumbers),
            'summary' => $this->describeTables($tableNumbers),
            'files' => array_map(fn (array $qr) => [
                'table_no' => $qr['table_no'],
                'filename' => $qr['filename'],
                'url' => $qr['url'],
            ], $qrs),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return redirect()->route('admin.qr-codes.index')->with('success', 'QR arşivi kaydedildi: '.$this->describeTables($tableNumbers));
    }

    public function archiveDownload(string $archiveId)
    {
        $archive = $this->loadArchive($archiveId);

        if (!class_exists(ZipArchive::class)) {
            return back()->withErrors(['zip' => 'ZIP oluşturma desteği sunucuda aktif değil.']);
        }

        $zipPath = storage_path('app/temp/qr-archive-'.$archiveId.'.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive();
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            return back()->withErrors(['zip' => 'Arşiv ZIP dosyası oluşturulamadı.']);
        }

        $this->addQrsToZip($zip, $archive['qrs']);

        $zip->close();

        return response()->download($zipPath, 'rocks-masa-qr-'.$archiveId.'.zip')->deleteFileAfterSend(true);
    }

    public function archivePrint(string $archiveId)
    {
        $archive = $this->loadArchive($archiveId);

        return view('admin.qr-print', [
            'generatedQrs' => $archive['qrs'],
            'title' => 'Kaydedilmiş QR Arşivi',
            'subtitle' => $archive['summary'].' | '.Carbon::parse($archive['created_at'])->format('d.m.Y H:i'),
        ]);
    }

    private function renderIndex(array $generatedQrs = [], ?array $formData = null)
    {
        return view('admin.qr-codes', [
            'generatedQrs' => $generatedQrs,
            'formData' => $formData ?? [
                'start_table' => 1,
                'end_table' => 20,
                'table_numbers' => '',
            ],
            'archives' => $this->listArchives(),
        ]);
    }

    private function extractFormData(Request $request): array
    {
        return [
            'start_table' => $request->input('start_table', 1),
            'end_table' => $request->input('end_table', 20),
            'table_numbers' => $request->input('table_numbers', ''),
        ];
    }

    private function resolveTableNumbers(Request $request): array
    {
        $request->validate([
            'start_table' => 'nullable|integer|min:1|max:500',
            'end_table' => 'nullable|integer|min:1|max:500',
            'table_numbers' => 'nullable|string|max:1000',
        ]);

        $tableNumbers = [];
        $customTableNumbers = trim((string) $request->input('table_numbers', ''));

        if ($customTableNumbers !== '') {
            foreach (preg_split('/\s*,\s*/', $customTableNumbers) as $segment) {
                if ($segment === '') {
                    continue;
                }

                if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $segment, $matches)) {
                    $rangeStart = (int) $matches[1];
                    $rangeEnd = (int) $matches[2];

                    if ($rangeStart > $rangeEnd) {
                        [$rangeStart, $rangeEnd] = [$rangeEnd, $rangeStart];
                    }

                    foreach (range($rangeStart, $rangeEnd) as $tableNumber) {
                        $tableNumbers[] = $tableNumber;
                    }
                } elseif (ctype_digit($segment)) {
                    $tableNumbers[] = (int) $segment;
                } else {
                    throw ValidationException::withMessages([
                        'table_numbers' => 'Masa listesi formatı hatalı. Örnek: 1,2,5-8',
                    ]);
                }
            }
        } else {
            $startTable = (int) $request->input('start_table', 1);
            $endTable = (int) $request->input('end_table', $startTable);

            if ($startTable > $endTable) {
                [$startTable, $endTable] = [$endTable, $startTable];
            }

            $tableNumbers = range($startTable, $endTable);
        }

        $tableNumbers = array_values(array_unique(array_filter($tableNumbers, fn (int $tableNumber) => $tableNumber >= 1 && $tableNumber <= 500)));
        sort($tableNumbers);

        if ($tableNumbers === []) {
            throw ValidationException::withMessages([
                'table_numbers' => 'En az bir geçerli masa numarası girin.',
            ]);
        }

        if (count($tableNumbers) > 200) {
            throw ValidationException::withMessages([
                'table_numbers' => 'Tek seferde en fazla 200 masa için QR oluşturabilirsiniz.',
            ]);
        }

        return $tableNumbers;
    }

    private function buildQrPayload(array $tableNumbers): array
    {
        $payload = [];

        foreach ($tableNumbers as $tableNumber) {
            $menuUrl = route('menu.table', ['tableNo' => $tableNumber]);
            $imageData = $this->generateQrSvg($menuUrl, $tableNumber);

            $payload[] = [
                'table_no' => $tableNumber,
                'url' => $menuUrl,
                'image_data_uri' => 'data:image/svg+xml;base64,'.base64_encode($imageData),
                'image_base64' => base64_encode($imageData),
                'filename' => 'masa-'.$tableNumber.'.svg',
            ];
        }

        return $payload;
    }

    private function generateQrSvg(string $url, int $tableNumber): string
    {
        $qrSvg = Builder::create()
            ->writer(new SvgWriter())
            ->writerOptions([
                SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true,
            ])
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(360)
            ->margin(18)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->validateResult(false)
            ->build()
            ->getString();

        $label = 'MASA '.$tableNumber;
        $escapedUrl = e($url);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 420 520" width="420" height="520">'
            .'<rect width="420" height="520" rx="28" fill="#ffffff"/>'
            .'<rect x="16" y="16" width="388" height="488" rx="24" fill="#fffdf7" stroke="#d4af37" stroke-width="3"/>'
            .'<g transform="translate(30 28)">'.$qrSvg.'</g>'
            .'<text x="210" y="462" text-anchor="middle" font-family="Arial, sans-serif" font-size="34" font-weight="700" fill="#1a1a2e">'.$label.'</text>'
            .'<text x="210" y="488" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" fill="#6b7280">Menü için QR kodu okutun</text>'
            .'<text x="210" y="506" text-anchor="middle" font-family="Arial, sans-serif" font-size="9" fill="#9ca3af">'.$escapedUrl.'</text>'
            .'</svg>';
    }

    private function listArchives(): array
    {
        $directory = storage_path('app/public/qr-archives');

        if (!File::isDirectory($directory)) {
            return [];
        }

        $archives = [];

        foreach (File::directories($directory) as $archiveDirectory) {
            $manifestPath = $archiveDirectory.DIRECTORY_SEPARATOR.'manifest.json';

            if (!File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode(File::get($manifestPath), true);

            if (!is_array($manifest) || empty($manifest['id'])) {
                continue;
            }

            $archives[] = [
                'id' => $manifest['id'],
                'created_at' => $manifest['created_at'] ?? null,
                'count' => $manifest['count'] ?? 0,
                'summary' => $manifest['summary'] ?? '',
                'table_numbers' => $manifest['table_numbers'] ?? [],
            ];
        }

        usort($archives, fn (array $left, array $right) => strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? '')));

        return array_slice($archives, 0, 12);
    }

    private function loadArchive(string $archiveId): array
    {
        $archivePath = $this->archivePath($archiveId);
        $manifestPath = $archivePath.DIRECTORY_SEPARATOR.'manifest.json';

        abort_unless(File::exists($manifestPath), 404);

        $manifest = json_decode(File::get($manifestPath), true);
        abort_unless(is_array($manifest), 404);

        $qrs = [];

        foreach (($manifest['files'] ?? []) as $file) {
            $filePath = $archivePath.DIRECTORY_SEPARATOR.$file['filename'];

            if (!File::exists($filePath)) {
                continue;
            }

            $content = File::get($filePath);

            $qrs[] = [
                'table_no' => $file['table_no'],
                'url' => $file['url'],
                'filename' => $file['filename'],
                'image_data_uri' => 'data:image/svg+xml;base64,'.base64_encode($content),
                'image_base64' => base64_encode($content),
            ];
        }

        return [
            'id' => $manifest['id'],
            'created_at' => $manifest['created_at'] ?? now()->toIso8601String(),
            'summary' => $manifest['summary'] ?? '',
            'qrs' => $qrs,
        ];
    }

    private function archivePath(string $archiveId): string
    {
        if (!preg_match('/^[A-Za-z0-9\-]+$/', $archiveId)) {
            abort(404);
        }

        return storage_path('app/public/qr-archives'.DIRECTORY_SEPARATOR.$archiveId);
    }

    private function addQrsToZip(ZipArchive $zip, array $qrs): void
    {
        foreach ($qrs as $qr) {
            $zip->addFromString($qr['filename'], base64_decode($qr['image_base64']));
        }
    }

    private function describeTables(array $tableNumbers): string
    {
        if (count($tableNumbers) === 1) {
            return 'Masa '.$tableNumbers[0];
        }

        return 'Masa '.$tableNumbers[0].' - '.$tableNumbers[count($tableNumbers) - 1].' ('.count($tableNumbers).' adet)';
    }
}