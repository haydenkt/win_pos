<?php

// Use the recorded input unit; legacy items retain the original feet display.
function itemMeasurementLabel(array $item): string
{
    $isMm = ($item['measurement_unit'] ?? '') === 'MM';
    $width = $isMm ? (float) ($item['width_mm'] ?? 0) : 0;
    $height = $isMm ? (float) ($item['height_mm'] ?? 0) : 0;
    $unit = 'mm';
    if ($width <= 0 && $height <= 0) {
        $width = (float) ($item['width_ft'] ?? 0);
        $height = (float) ($item['height_ft'] ?? 0);
        if ($width <= 0 && $height <= 0) {
            $width = (float) ($item['width'] ?? 0);
            $height = (float) ($item['height'] ?? 0);
        }
        $unit = 'ft';
    }
    if ($width <= 0 && $height <= 0) return '-';
    $format = static fn(float $value): string => rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
    return $format($width) . ' × ' . $format($height) . ' ' . $unit;
}
