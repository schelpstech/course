<?php
$dir = __DIR__;

while (!file_exists($dir . '/vendor/autoload.php')) {
    $parent = dirname($dir);

    if ($parent === $dir) {
        throw new Exception("vendor/autoload.php not found");
    }

    $dir = $parent;
}

require_once $dir . '/vendor/autoload.php';


use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QRCodeGenerator
{
    public function generateQRCode($text)
    {
        $builder = new Builder();

        $result = $builder->build(
            writer: new PngWriter(),
            data: $text,
            size: 300,
            margin: 20
        );

        return $result->getDataUri(); // <-- RETURN, not echo
    }
}
