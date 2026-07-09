<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class EwaBillParser
{
    public function parse(string $filePath): array
    {
        $text = $this->extractText($filePath);

        return [
            'ewa_account_number'  => $this->extractAccountNumber($text),
            'billing_period'      => $this->extractBillingPeriod($text),
            'reading_date'        => $this->extractReadingDate($text),
            'reading_type'        => $this->extractReadingType($text),
            'elec_prev_reading'   => $this->extractElecPrevReading($text),
            'elec_curr_reading'   => $this->extractElecCurrReading($text),
            'elec_charges'        => $this->extractElecCharges($text),
            'water_prev_reading'  => $this->extractWaterPrevReading($text),
            'water_curr_reading'  => $this->extractWaterCurrReading($text),
            'water_charges'       => $this->extractWaterCharges($text),
            'total_amount'        => $this->extractTotalAmount($text),
            'due_date'            => $this->extractDueDate($text),
            'tenant_name'         => $this->extractTenantName($text),
            'property_name'       => $this->extractPropertyName($text),
            '_raw_text'           => $text, // for debugging
        ];
    }

    private function extractText(string $filePath): string
    {
        try {
            $parser = new Parser();
            $pdf    = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Throwable $e) {
            return '';
        }
    }

    // ── Account Number ────────────────────────────────────────
    private function extractAccountNumber(string $text): ?string
    {
        // Patterns: "Account No: 12345678", "Account Number: 12345678", "Customer Account: 12345678"
        if (preg_match('/(?:account\s*(?:no|number|#)\s*[:\-]?\s*)(\d{5,12})/i', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    // ── Billing Period ────────────────────────────────────────
    private function extractBillingPeriod(string $text): ?string
    {
        // "Billing Period: March 2024" or "Bill Date: 01/03/2024" or "March 2024"
        $months = 'January|February|March|April|May|June|July|August|September|October|November|December';

        if (preg_match('/(?:billing\s*period|bill\s*month|period)\s*[:\-]?\s*((?:'.$months.')\s+\d{4})/i', $text, $m)) {
            return trim($m[1]);
        }
        // Standalone "March 2024"
        if (preg_match('/\b((?:'.$months.')\s+\d{4})\b/i', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    // ── Reading Date ──────────────────────────────────────────
    private function extractReadingDate(string $text): ?string
    {
        // "Reading Date: 15/03/2024" or "Read Date: 15-03-2024"
        if (preg_match('/(?:read(?:ing)?\s*date|meter\s*read)\s*[:\-]?\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})/i', $text, $m)) {
            return $this->parseDate($m[1]);
        }
        return null;
    }

    // ── Reading Type ──────────────────────────────────────────
    private function extractReadingType(string $text): string
    {
        if (preg_match('/\b(estimated|estimate)\b/i', $text)) {
            return 'estimated';
        }
        return 'actual';
    }

    // ── Electricity Previous Reading ──────────────────────────
    private function extractElecPrevReading(string $text): ?string
    {
        // "Previous Reading: 12345" near electricity/kWh context
        // Try electricity-specific block first
        if (preg_match('/electr\w+[\s\S]{0,300}?prev(?:ious)?\s*(?:reading|meter)?\s*[:\-]?\s*(\d+)/i', $text, $m)) {
            return $m[1];
        }
        // Generic previous reading (first occurrence)
        if (preg_match('/prev(?:ious)?\s*(?:reading|meter)?\s*[:\-]?\s*(\d{3,7})/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Electricity Current Reading ───────────────────────────
    private function extractElecCurrReading(string $text): ?string
    {
        if (preg_match('/electr\w+[\s\S]{0,300}?curr?(?:ent)?\s*(?:reading|meter)?\s*[:\-]?\s*(\d+)/i', $text, $m)) {
            return $m[1];
        }
        if (preg_match('/curr?(?:ent)?\s*(?:reading|meter)?\s*[:\-]?\s*(\d{3,7})/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Electricity Charges ───────────────────────────────────
    private function extractElecCharges(string $text): ?string
    {
        // "Electricity: 7.190" or "Electricity Charges: BD 7.190"
        if (preg_match('/electr\w+\s*(?:charges?|amount|cost)?\s*[:\-]?\s*(?:BD|BHD|BD)?\s*(\d+\.\d{1,3})/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Water Previous Reading ────────────────────────────────
    private function extractWaterPrevReading(string $text): ?string
    {
        if (preg_match('/water[\s\S]{0,300}?prev(?:ious)?\s*(?:reading|meter)?\s*[:\-]?\s*(\d+(?:\.\d+)?)/i', $text, $m)) {
            return $m[1];
        }
        // Second occurrence of "previous reading"
        preg_match_all('/prev(?:ious)?\s*(?:reading|meter)?\s*[:\-]?\s*(\d+(?:\.\d+)?)/i', $text, $matches);
        return isset($matches[1][1]) ? $matches[1][1] : null;
    }

    // ── Water Current Reading ─────────────────────────────────
    private function extractWaterCurrReading(string $text): ?string
    {
        if (preg_match('/water[\s\S]{0,300}?curr?(?:ent)?\s*(?:reading|meter)?\s*[:\-]?\s*(\d+(?:\.\d+)?)/i', $text, $m)) {
            return $m[1];
        }
        preg_match_all('/curr?(?:ent)?\s*(?:reading|meter)?\s*[:\-]?\s*(\d+(?:\.\d+)?)/i', $text, $matches);
        return isset($matches[1][1]) ? $matches[1][1] : null;
    }

    // ── Water Charges ─────────────────────────────────────────
    private function extractWaterCharges(string $text): ?string
    {
        if (preg_match('/water\s*(?:charges?|amount|cost)?\s*[:\-]?\s*(?:BD|BHD)?\s*(\d+\.\d{1,3})/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Total Amount ──────────────────────────────────────────
    private function extractTotalAmount(string $text): ?string
    {
        // "Total Amount Due: BD 38.440" or "Amount Due: 38.440"
        if (preg_match('/total\s*(?:amount\s*)?due\s*[:\-]?\s*(?:BD|BHD)?\s*(\d+\.\d{1,3})/i', $text, $m)) {
            return $m[1];
        }
        if (preg_match('/amount\s*due\s*[:\-]?\s*(?:BD|BHD)?\s*(\d+\.\d{1,3})/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Due Date ──────────────────────────────────────────────
    private function extractDueDate(string $text): ?string
    {
        // "Due before 18/04/2023" or "Payment Due: 18/04/2023" or "Due Date: 18 April 2023"
        $months = 'Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?';

        if (preg_match('/(?:due\s*(?:before|date|by)|payment\s*due)\s*[:\-]?\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})/i', $text, $m)) {
            return $this->parseDate($m[1]);
        }
        if (preg_match('/(?:due\s*(?:before|date|by)|payment\s*due)\s*[:\-]?\s*(\d{1,2}\s+(?:'.$months.')\s+\d{4})/i', $text, $m)) {
            return $this->parseDate($m[1]);
        }
        return null;
    }

    // ── Tenant Name ───────────────────────────────────────────
    private function extractTenantName(string $text): ?string
    {
        // "Customer Name: John Smith" or "Account Holder: ..."
        if (preg_match('/(?:customer\s*(?:name)?|account\s*holder|name)\s*[:\-]\s*([A-Za-z][A-Za-z\s]{2,50})/i', $text, $m)) {
            $name = trim($m[1]);
            // Avoid matching section headers
            if (strlen($name) > 3 && !preg_match('/^(number|address|date|period|type|charges|amount)/i', $name)) {
                return $name;
            }
        }
        return null;
    }

    // ── Property / Supply Address ─────────────────────────────
    private function extractPropertyName(string $text): ?string
    {
        if (preg_match('/(?:supply\s*address|property|premises)\s*[:\-]\s*(.{5,80}?)(?:\n|$)/i', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    // ── Date normalizer ───────────────────────────────────────
    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        // Try d/m/Y and d-m-Y formats
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y'] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $raw);
            if ($d) return $d->format('Y-m-d');
        }
        // Natural language "15 April 2024"
        try {
            return (new \DateTime($raw))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
