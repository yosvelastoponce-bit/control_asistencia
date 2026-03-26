<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

require __DIR__ . '/../vendor/autoload.php';

$source = __DIR__ . '/../docs/manual-operativo-y-tecnico.html';
$target = __DIR__ . '/../docs/manual-operativo-y-tecnico.pdf';

if (! file_exists($source)) {
    fwrite(STDERR, "No se encontro el archivo fuente HTML.\n");
    exit(1);
}

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->setDefaultFont('DejaVu Sans');

$dompdf = new Dompdf($options);
$html = file_get_contents($source);

if ($html === false) {
    fwrite(STDERR, "No se pudo leer el archivo fuente HTML.\n");
    exit(1);
}

$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$output = $dompdf->output();

if (file_put_contents($target, $output) === false) {
    fwrite(STDERR, "No se pudo escribir el archivo PDF.\n");
    exit(1);
}

fwrite(STDOUT, "PDF generado en: {$target}\n");
