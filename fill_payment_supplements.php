<?php

use App\Models\Cfdi;
use App\Services\CfdiXmlParserService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

// Load parser service
$parser = new CfdiXmlParserService();

// Get all Payment CFDIs
$cfdis = Cfdi::where('tipo_comprobante', 'P')->get();
$total = $cfdis->count();
$updated = 0;
$errors = 0;

echo "Starting Payment Supplement (Taxes) backfill for {$total} CFDIs...\n";

foreach ($cfdis as $index => $cfdi) {
    try {
        if (!$cfdi->uuid) {
            continue;
        }

        // Use stored XML path
        $xmlPath = $cfdi->xml_url;
        if (!$xmlPath || !Storage::disk('public')->exists($xmlPath)) {
             echo "XML not found for CFDI {$cfdi->uuid}\n";
             continue;
        }

        $xmlContent = Storage::disk('public')->get($xmlPath);
        
        // Parse with new logic (including taxes)
        $data = $parser->parseCfdiXml($xmlContent);

        if (!empty($data['complementos'])) {
             $cfdi->update([
                 'complementos' => $data['complementos']
             ]);
             $updated++;
        }

        if ($index % 10 === 0) {
            echo "Processed {$index}/{$total}...\n";
        }

    } catch (\Exception $e) {
        $errors++;
        echo "Error processing CFDI {$cfdi->id}: " . $e->getMessage() . "\n";
    }
}

echo "Backfill complete. Updated: {$updated}, Errors: {$errors}\n";
