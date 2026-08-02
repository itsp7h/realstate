<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

/**
 * Extracts data from EWA (Electricity & Water Authority, Bahrain) government
 * bill PDFs. Field positions below were reverse-engineered from real EWA
 * bills (fixed layout used by ewa.bh) — the PDF text extraction interleaves
 * the electricity/water columns in a specific, consistent order, which the
 * patterns below rely on.
 */
class EwaBillParser
{
    public function parse(string $filePath): array
    {
        $text = $this->extractText($filePath);

        return [
            'ewa_account_number'    => $this->extractAccountNumber($text),
            'issue_date'            => $this->extractIssueDate($text),
            'due_date'              => $this->extractDueDate($text),
            'billing_period'        => $this->extractBillingPeriod($text),
            'reading_type'          => $this->extractReadingTypes($text)[0],
            'current_reading_type'  => $this->extractReadingTypes($text)[0],
            'previous_reading_type' => $this->extractReadingTypes($text)[1],
            'current_reading_date'  => $this->extractReadingDates($text)[0] ?? null,
            'previous_reading_date' => $this->extractReadingDates($text)[1] ?? null,
            'elec_prev_reading'     => $this->extractElecPrevReading($text),
            'elec_curr_reading'     => $this->extractElecCurrReading($text),
            'elec_consumption'      => $this->extractElecConsumption($text),
            'elec_charges'          => $this->extractSubTotals($text)[0] ?? null,
            'water_consumption'     => $this->extractWaterConsumption($text),
            'water_charges'         => $this->extractSubTotals($text)[1] ?? null,
            'municipality_fee'      => $this->extractMunicipalityFee($text),
            'sanitary_fee'          => $this->extractSanitaryFee($text),
            'arrears'               => $this->extractArrears($text),
            'amount_due'            => $this->extractAmountDue($text),
            'account_holder_name'   => $this->extractAccountHolder($text)[0] ?? null,
            'flat_building_raw'     => $this->extractAccountHolder($text)[1] ?? null,
            'address_raw'           => $this->extractAccountHolder($text)[2] ?? null,
            '_raw_text'             => $text, // for debugging
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
        if (preg_match('/Account Number\s*:\s*(\d{6,12})/i', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    // ── Issue Date ────────────────────────────────────────────
    private function extractIssueDate(string $text): ?string
    {
        if (preg_match('/Issue Date\s*:\s*(\d{1,2}\/\d{1,2}\/\d{4})/i', $text, $m)) {
            return $this->parseDate($m[1]);
        }
        return null;
    }

    // ── Due Date ──────────────────────────────────────────────
    private function extractDueDate(string $text): ?string
    {
        if (preg_match('/Due Date\s*[\t\r\n ]*(\d{1,2}\/\d{1,2}\/\d{4})/i', $text, $m)) {
            return $this->parseDate($m[1]);
        }
        return null;
    }

    // ── Billing Period — from the consumption date range on the ────
    // water line, which is present even when electricity usage is 0.
    private function extractBillingPeriod(string $text): ?string
    {
        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4})\s+\d{1,2}\/\d{1,2}\/\d{4}(?:Non )?Domestic/i', $text, $m)) {
            $date = $this->parseDate($m[1]);
            return $date ? date('F Y', strtotime($date)) : null;
        }
        return null;
    }

    // ── Reading Type(s) ───────────────────────────────────────
    // The current and previous readings can each independently be Actual or
    // Estimated — "Actual\tEstimated" is glued directly after the reading
    // dates, in the same current-then-previous order as
    // extractReadingDates() below. Returns [currentType, previousType],
    // defaulting each to 'actual' when the pattern isn't found.
    private function extractReadingTypes(string $text): array
    {
        if (preg_match('/Previous Reading\s*\d{1,2}\/\d{1,2}\/\d{4}[\t\s]+\d{1,2}\/\d{1,2}\/\d{4}\s*[\r\n]+\s*(Actual|Estimated)[\t\s]+(Actual|Estimated)/i', $text, $m)) {
            return [strtolower($m[1]), strtolower($m[2])];
        }
        return ['actual', 'actual'];
    }

    // ── Current / Previous meter reading dates ─────────────────
    // Both dates are glued onto the "Previous Reading" label:
    // "Previous Reading30/06/2026\t31/05/2026" — first is the current
    // reading's date (end of period), second is the previous one.
    private function extractReadingDates(string $text): array
    {
        if (preg_match('/Previous Reading\s*(\d{1,2}\/\d{1,2}\/\d{4})[\t\s]+(\d{1,2}\/\d{1,2}\/\d{4})/i', $text, $m)) {
            return [$this->parseDate($m[1]), $this->parseDate($m[2])];
        }
        return [null, null];
    }

    // ── Electricity Previous / Current Reading ─────────────────
    // Numbers appear immediately *before* their label in the extracted text.
    private function extractElecCurrReading(string $text): ?string
    {
        if (preg_match('/(\d+)\s*[\r\n]+\s*Current Reading/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    private function extractElecPrevReading(string $text): ?string
    {
        if (preg_match('/(\d+)\s*[\r\n]+\s*Previous Reading/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Consumption ───────────────────────────────────────────
    private function extractElecConsumption(string $text): ?string
    {
        if (preg_match('/(\d+)\s*\(kWh\)/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    private function extractWaterConsumption(string $text): ?string
    {
        if (preg_match('/(\d+(?:\.\d+)?)\s*\(m3\)/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Electricity / Water Charges — the "Sub Total" line lists ───
    // electricity first, then water: "Sub Total 105.350 BD Sub Total 14.600 BD"
    private function extractSubTotals(string $text): array
    {
        preg_match_all('/Sub Total\s*[\t ]*([\d]+\.[\d]{1,3})\s*BD/i', $text, $m);
        return [$m[1][0] ?? null, $m[1][1] ?? null];
    }

    // ── Municipality Fee — appears twice; the first occurrence is ───
    // interleaved with the electricity block, so take the last (clean) one.
    private function extractMunicipalityFee(string $text): ?string
    {
        preg_match_all('/Municipality Fees[\s\S]{0,15}?([\d]+\.[\d]{1,3})\s*BD/i', $text, $m);
        return $m[1] ? end($m[1]) : null;
    }

    // ── Sanitary Fee ──────────────────────────────────────────
    private function extractSanitaryFee(string $text): ?string
    {
        if (preg_match('/Sanitary Fees\s*[\t\r\n]*([\d]+\.[\d]{1,3})\s*BD/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Arrears (Previous Balance carried into this bill) ───────────
    private function extractArrears(string $text): ?string
    {
        if (preg_match('/Your \w+ bill is\s*\.?[\-\d.]+\s*BD\s*[\r\n]+\s*[\r\n]*\s*(-?[\d]+\.[\d]{1,3})\s*BD/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Amount Due (this bill + previous balance, may be negative/credit) ──
    private function extractAmountDue(string $text): ?string
    {
        if (preg_match('/\(\w+ bill \+ Previous Balance\)\s*[\r\n]+\s*(-?[\d]+\.[\d]{1,3})\s*BD/i', $text, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Account holder / address — only used as a last-resort fallback ──
    // when the account number doesn't match any bill/lease already on file.
    // The bill is addressed to the landlord entity, not the tenant, so this
    // is never the preferred source of tenant/property data.
    private function extractAccountHolder(string $text): array
    {
        if (preg_match(
            '/Account Number\s*:\s*\d+\s*[\r\n]+([^\r\n]+)[\r\n]+([^\r\n]+)[\r\n]+([^\r\n]*(?:Flat|Building)[^\r\n]*)[\r\n]+([^\r\n]+)[\r\n]+([^\r\n]+)/i',
            $text,
            $m
        )) {
            $holderName = trim($m[1] . ' ' . $m[2]);
            $flatRaw    = trim($m[3]);
            $address    = trim($m[4] . ', ' . $m[5]);

            return [$holderName, $flatRaw, $address];
        }
        return [null, null, null];
    }

    // ── Date normalizer ───────────────────────────────────────
    private function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y'] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $raw);
            if ($d) return $d->format('Y-m-d');
        }
        try {
            return (new \DateTime($raw))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
