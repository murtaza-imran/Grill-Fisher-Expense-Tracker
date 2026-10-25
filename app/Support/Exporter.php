<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * Turns one table (title, headers, rows, summary) into a download:
 * CSV, Excel (.xlsx), PDF, Word (.docx) or JSON.
 */
class Exporter
{
    public const FORMATS = ['csv', 'xlsx', 'pdf', 'docx', 'json'];

    /**
     * @param  string[]  $headers
     * @param  array<int, array<int, string|int|null>>  $rows
     * @param  int[]  $moneyCols  column indexes holding rupee amounts
     * @param  array<string, string>  $summary  label => value
     */
    public function __construct(
        private string $title,
        private string $period,
        private array $headers,
        private array $rows,
        private array $moneyCols,
        private array $summary,
        private string $filename,
    ) {}

    public function download(string $format): Response
    {
        return match ($format) {
            'csv' => $this->csv(),
            'xlsx' => $this->xlsx(),
            'pdf' => $this->pdf(),
            'docx' => $this->docx(),
            'json' => $this->json(),
        };
    }

    private function file(string $body, string $ext, string $type): Response
    {
        return response($body, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => 'attachment; filename="' . $this->filename . '.' . $ext . '"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function money($v): string
    {
        return ((int) $v < 0 ? '-' : '') . 'Rs. ' . number_format(abs((int) $v));
    }

    private function x(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    // ---------------------------------------------------------------- CSV
    private function csv(): Response
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads special characters correctly
        fputcsv($out, [$this->title . ' — ' . $this->period]);
        fputcsv($out, []);
        fputcsv($out, $this->headers);
        foreach ($this->rows as $r) {
            fputcsv($out, $r);
        }
        fputcsv($out, []);
        foreach ($this->summary as $k => $v) {
            fputcsv($out, [$k, $v]);
        }
        rewind($out);
        $body = stream_get_contents($out);
        fclose($out);
        return $this->file($body, 'csv', 'text/csv; charset=UTF-8');
    }

    // ---------------------------------------------------------------- JSON
    private function json(): Response
    {
        $data = [
            'title' => $this->title,
            'period' => $this->period,
            'generated_at' => now()->toIso8601String(),
            'summary' => $this->summary,
            'rows' => array_map(fn ($r) => array_combine($this->headers, $r), $this->rows),
        ];
        return $this->file(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'json', 'application/json');
    }

    // ---------------------------------------------------------------- PDF
    private function pdf(): Response
    {
        $html = view('exports.pdf', [
            'title' => $this->title, 'period' => $this->period, 'headers' => $this->headers,
            'rows' => $this->rows, 'moneyCols' => $this->moneyCols, 'summary' => $this->summary,
            'money' => fn ($v) => $this->money($v),
        ])->render();
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('fontCache', storage_path('fonts'));
        $options->set('tempDir', storage_path('app'));
        $options->set('chroot', base_path());
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();
        return $this->file($pdf->output(), 'pdf', 'application/pdf');
    }

    // ---------------------------------------------------------------- Excel (.xlsx)
    private function zip(array $files): string
    {
        $path = tempnam(storage_path('app'), 'gfx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $body = file_get_contents($path);
        @unlink($path);
        return $body;
    }

    private function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private function xlsx(): Response
    {
        // styles: 0 normal, 1 title, 2 header, 3 money, 4 muted, 5 bold, 6 bold money
        $cell = function (int $c, int $r, $v, int $style = 0, bool $num = false) {
            $ref = $this->col($c) . $r;
            if ($num) {
                return '<c r="' . $ref . '" s="' . $style . '"><v>' . (int) $v . '</v></c>';
            }
            return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $this->x((string) $v) . '</t></is></c>';
        };
        $xmlRows = [];
        $r = 1;
        $xmlRows[] = '<row r="1" ht="24" customHeight="1">' . $cell(0, 1, $this->title, 1) . '</row>';
        $xmlRows[] = '<row r="2">' . $cell(0, 2, $this->period, 4) . '</row>';
        $r = 4;
        $xmlRows[] = '<row r="4">' . implode('', array_map(fn ($h, $i) => $cell($i, 4, $h, 2), $this->headers, array_keys($this->headers))) . '</row>';
        foreach ($this->rows as $row) {
            $r++;
            $cells = '';
            foreach (array_values($row) as $i => $v) {
                $isMoney = in_array($i, $this->moneyCols, true);
                $cells .= $cell($i, $r, $v ?? '', $isMoney ? 3 : 0, $isMoney);
            }
            $xmlRows[] = '<row r="' . $r . '">' . $cells . '</row>';
        }
        $r++;
        foreach ($this->summary as $k => $v) {
            $r++;
            $xmlRows[] = '<row r="' . $r . '">' . $cell(0, $r, $k, 5) . $cell(1, $r, $v, 5) . '</row>';
        }
        $widths = array_map(function ($i) {
            $len = mb_strlen($this->headers[$i]);
            foreach ($this->rows as $row) {
                $len = max($len, mb_strlen((string) (array_values($row)[$i] ?? '')));
            }
            return min(60, max(10, $len + 3));
        }, array_keys($this->headers));
        $cols = implode('', array_map(fn ($w, $i) => '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>', $widths, array_keys($widths)));
        $last = $this->col(count($this->headers) - 1);

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols><sheetData>' . implode('', $xmlRows) . '</sheetData>'
            . '<autoFilter ref="A4:' . $last . max(4, 4 + count($this->rows)) . '"/></worksheet>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rs. &quot;#,##0;[Red]&quot;-Rs. &quot;#,##0"/></numFmts>'
            . '<fonts count="5"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF1A1105"/><name val="Calibri"/></font><font><sz val="10"/><color rgb="FF7A7064"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE8A91A"/></patternFill></fill></fills>'
            . '<borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="7"><xf/><xf fontId="1" applyFont="1"/><xf fontId="2" fillId="2" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" applyNumberFormat="1"/><xf fontId="3" applyFont="1"/><xf fontId="4" applyFont="1"/><xf numFmtId="164" fontId="4" applyNumberFormat="1" applyFont="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        $body = $this->zip([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . $this->x(mb_substr($this->title, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets><definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">\'' . $this->x(mb_substr($this->title, 0, 31)) . '\'!$A$4:$' . $last . '$' . max(4, 4 + count($this->rows)) . '</definedName></definedNames></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => $styles,
            'xl/worksheets/sheet1.xml' => $sheet,
        ]);
        return $this->file($body, 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    // ---------------------------------------------------------------- Word (.docx)
    private function docx(): Response
    {
        $p = fn (string $text, string $props = '', string $runProps = '') => '<w:p>' . ($props ? '<w:pPr>' . $props . '</w:pPr>' : '')
            . '<w:r>' . ($runProps ? '<w:rPr>' . $runProps . '</w:rPr>' : '') . '<w:t xml:space="preserve">' . $this->x($text) . '</w:t></w:r></w:p>';
        $tc = fn (string $text, bool $head = false, bool $right = false) => '<w:tc><w:tcPr>' . ($head ? '<w:shd w:val="clear" w:color="auto" w:fill="E8A91A"/>' : '') . '</w:tcPr>'
            . '<w:p><w:pPr><w:spacing w:before="40" w:after="40"/>' . ($right ? '<w:jc w:val="right"/>' : '') . '</w:pPr><w:r><w:rPr>' . ($head ? '<w:b/>' : '') . '<w:sz w:val="18"/></w:rPr><w:t xml:space="preserve">' . $this->x($text) . '</w:t></w:r></w:p></w:tc>';
        $rows = '<w:tr><w:trPr><w:tblHeader/></w:trPr>' . implode('', array_map(fn ($h, $i) => $tc($h, true, in_array($i, $this->moneyCols, true)), $this->headers, array_keys($this->headers))) . '</w:tr>';
        foreach ($this->rows as $row) {
            $rows .= '<w:tr>';
            foreach (array_values($row) as $i => $v) {
                $isMoney = in_array($i, $this->moneyCols, true);
                $rows .= $tc($isMoney ? $this->money($v) : (string) ($v ?? ''), false, $isMoney);
            }
            $rows .= '</w:tr>';
        }
        $border = '<w:top w:val="single" w:sz="4" w:color="D9D2C5"/><w:bottom w:val="single" w:sz="4" w:color="D9D2C5"/><w:insideH w:val="single" w:sz="4" w:color="D9D2C5"/>';
        $table = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblBorders>' . $border . '</w:tblBorders><w:tblCellMar><w:left w:w="100" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar></w:tblPr>' . $rows . '</w:tbl>';
        $summary = implode('', array_map(fn ($k, $v) => '<w:p><w:pPr><w:spacing w:after="40"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">' . $this->x($k) . ': </w:t></w:r><w:r><w:t xml:space="preserve">' . $this->x($v) . '</w:t></w:r></w:p>', array_keys($this->summary), $this->summary));
        $doc = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . $p('Grill Fisher & BBQ', '<w:spacing w:after="0"/>', '<w:color w:val="C98500"/><w:b/><w:sz w:val="20"/>')
            . $p($this->title, '<w:spacing w:after="40"/>', '<w:b/><w:sz w:val="36"/>')
            . $p($this->period . ' · Generated ' . now()->format('j M Y, g:i A'), '<w:spacing w:after="240"/>', '<w:color w:val="7A7064"/><w:sz w:val="18"/>')
            . $summary . $p('', '<w:spacing w:after="120"/>') . $table
            . '<w:sectPr><w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/><w:pgMar w:top="900" w:right="900" w:bottom="900" w:left="900" w:header="0" w:footer="0" w:gutter="0"/></w:sectPr>'
            . '</w:body></w:document>';
        $body = $this->zip([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'word/document.xml' => $doc,
        ]);
        return $this->file($body, 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }
}
