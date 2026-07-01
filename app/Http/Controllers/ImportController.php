<?php

namespace App\Http\Controllers;

use App\Services\Import\ImportRegistry;
use App\Services\Import\ImportResult;
use App\Services\Import\SpreadsheetReader;
use App\Services\Import\TemplateBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * นำเข้าข้อมูล KPI จากไฟล์ Excel — เฉพาะผู้ดูแลระบบสูงสุด/ผู้ดูแลตัวชี้วัดทั้งหมด
 */
class ImportController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly ImportRegistry $registry,
    ) {}

    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                abort_unless(
                    (bool) $request->user()?->isTopAdmin(),
                    403,
                    'คุณไม่มีสิทธิ์ใช้งานเมนูนำเข้าข้อมูล (เฉพาะผู้ดูแลระบบสูงสุด/ผู้ดูแลตัวชี้วัดทั้งหมด)'
                );

                return $next($request);
            },
        ];
    }

    public function index(): View
    {
        return view('imports.index', [
            'types' => $this->registry->all(),
        ]);
    }

    public function template(string $type): StreamedResponse
    {
        abort_unless($this->registry->has($type), 404);

        $importType = $this->registry->get($type);
        $spreadsheet = app(TemplateBuilder::class)->build($importType);
        $writer = new Xlsx($spreadsheet);

        $filename = 'เทมเพลตนำเข้า_' . $importType->label() . '.xlsx';

        return response()->streamDownload(
            function () use ($writer) {
                $writer->save('php://output');
            },
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        abort_unless($this->registry->has($type), 404);

        $request->validate(
            ['file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:5120']],
            [
                'file.required' => 'กรุณาเลือกไฟล์ที่จะนำเข้า',
                'file.extensions' => 'รองรับเฉพาะไฟล์ .xlsx, .xls หรือ .csv',
                'file.max' => 'ไฟล์ต้องมีขนาดไม่เกิน 5 MB',
            ]
        );

        $importType = $this->registry->get($type);
        $reader = app(SpreadsheetReader::class);

        $pre = new ImportResult();
        $rows = $reader->read($request->file('file'), $importType, $pre);

        if ($pre->hasErrors()) {
            return $this->backWith($type, $pre);
        }

        if ($rows->isEmpty()) {
            $pre->addFatal('ไม่พบข้อมูลในไฟล์ — กรุณากรอกข้อมูลในชีต "ข้อมูล" ตั้งแต่แถวที่ 2');

            return $this->backWith($type, $pre);
        }

        $result = $importType->import($rows);

        return $this->backWith($type, $result);
    }

    private function backWith(string $type, ImportResult $result): RedirectResponse
    {
        return redirect()
            ->route('imports.index')
            ->with('import_type', $type)
            ->with('import_result', $result->toArray())
            ->withFragment('type-' . $type);
    }
}
