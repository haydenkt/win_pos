<?php

final class SimpleXlsx
{
    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }
        return $name;
    }

    private static function cellXml($cell, int $row, int $column): string
    {
        $address = self::columnName($column) . $row;
        if (!is_array($cell)) {
            $cell = ['value' => $cell];
        }
        $value = $cell['value'] ?? null;
        $style = isset($cell['style']) ? ' s="' . (int) $cell['style'] . '"' : '';
        $type = $cell['type'] ?? (is_numeric($value) ? 'number' : 'string');

        if ($value === null || $value === '') {
            return '<c r="' . $address . '"' . $style . '/>';
        }
        if ($type === 'date') {
            $timestamp = strtotime((string) $value . ' UTC');
            $serial = ($timestamp / 86400) + 25569;
            return '<c r="' . $address . '"' . $style . '><v>' . $serial . '</v></c>';
        }
        if ($type === 'number') {
            return '<c r="' . $address . '"' . $style . '><v>' . (float) $value . '</v></c>';
        }

        return '<c r="' . $address . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">'
            . self::escape((string) $value) . '</t></is></c>';
    }

    private static function sheetXml(array $sheet): string
    {
        $rows = $sheet['rows'] ?? [];
        $widths = $sheet['widths'] ?? [];
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>';
        if ($widths) {
            $xml .= '<cols>';
            foreach ($widths as $index => $width) {
                $column = $index + 1;
                $xml .= '<col min="' . $column . '" max="' . $column . '" width="' . (float) $width . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        foreach ($rows as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $xml .= '<row r="' . $rowNumber . '">';
            foreach ($cells as $columnIndex => $cell) {
                $xml .= self::cellXml($cell, $rowNumber, $columnIndex + 1);
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';
        if (!empty($sheet['merges'])) {
            $xml .= '<mergeCells count="' . count($sheet['merges']) . '">';
            foreach ($sheet['merges'] as $merge) {
                $xml .= '<mergeCell ref="' . self::escape($merge) . '"/>';
            }
            $xml .= '</mergeCells>';
        }
        $xml .= '<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>';
        return $xml . '</worksheet>';
    }

    public static function download(string $filename, array $sheets): void
    {
        $path = tempnam(sys_get_temp_dir(), 'winpos_xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create Excel file.');
        }

        $overrides = '';
        $workbookSheets = '';
        $relationships = '';
        foreach (array_values($sheets) as $index => $sheet) {
            $number = $index + 1;
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $number . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $workbookSheets .= '<sheet name="' . self::escape($sheet['name']) . '" sheetId="' . $number . '" r:id="rId' . $number . '"/>';
            $relationships .= '<Relationship Id="rId' . $number . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $number . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $number . '.xml', self::sheetXml($sheet));
        }
        $styleRelationship = count($sheets) + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $overrides . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $workbookSheets . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $relationships . '<Relationship Id="rId' . $styleRelationship . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        readfile($path);
        unlink($path);
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Arial"/></font><font><b/><color rgb="FF101B2D"/><sz val="15"/><name val="Arial"/></font></fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF2563EB"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF101B2D"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border/><border><bottom style="thin"><color rgb="FFDDE5EE"/></bottom></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="8"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFill="1" applyFont="1"><alignment horizontal="center"/></xf><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="4" fontId="1" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1"/><xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="14" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
