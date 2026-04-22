<?php

require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;

try {
    $parser = new Parser();
    $pdf = $parser->parseFile('menu.pdf');
    $text = $pdf->getText();

    file_put_contents('menu_text.txt', $text);

    echo "Text extracted to menu_text.txt\n";
    echo "Length: " . strlen($text) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}