<?php

namespace App\Services;

use App\Models\LabPartnership;
use App\Models\PartnerInterpretation;

/**
 * Parses HL7 v2 ORU^R01 messages (Observation Result) from LIS systems.
 * Extracts patient identifiers and test results in a structured format.
 *
 * HL7 messages are pipe-delimited with segments separated by carriage returns.
 * Key segments:
 *   MSH - Message Header
 *   PID - Patient Identification
 *   OBR - Observation Request (order info)
 *   OBX - Observation Result (the actual test values)
 */
class Hl7Parser
{
    /**
     * Parse an HL7 ORU^R01 message and return structured data.
     */
    public function parse(string $message): array
    {
        $segments = $this->splitSegments($message);

        $results = [
            'message_type' => $this->getField($segments['MSH'][0] ?? '', 8),
            'message_control_id' => $this->getField($segments['MSH'][0] ?? '', 9),
            'patient' => $this->parsePid($segments['PID'][0] ?? ''),
            'orders' => [],
        ];

        // Group OBX segments by OBR
        $currentOrder = null;
        $currentObxs = [];

        foreach ($segments as $segType => $segList) {
            foreach ($segList as $seg) {
                if ($segType === 'OBR') {
                    // Save previous order if exists
                    if ($currentOrder !== null) {
                        $results['orders'][] = [
                            'order' => $currentOrder,
                            'observations' => $this->parseObxBatch($currentObxs),
                        ];
                    }
                    $currentOrder = $this->parseObr($seg);
                    $currentObxs = [];
                } elseif ($segType === 'OBX') {
                    $currentObxs[] = $seg;
                }
            }
        }

        // Don't forget the last order
        if ($currentOrder !== null) {
            $results['orders'][] = [
                'order' => $currentOrder,
                'observations' => $this->parseObxBatch($currentObxs),
            ];
        }

        return $results;
    }

    /**
     * Parse an HL7 message and create PartnerInterpretation records.
     */
    public function parseAndStore(string $message, LabPartnership $partnership, ?string $patientIdentifier = null): array
    {
        $parsed = $this->parse($message);

        $batchId = 'hl7_' . now()->timestamp . '_' . $partnership->id;
        $created = [];
        $patientId = $patientIdentifier ?? $parsed['patient']['id'] ?? $parsed['message_control_id'];

        foreach ($parsed['orders'] as $orderGroup) {
            $orderInfo = $orderGroup['order'];

            foreach ($orderGroup['observations'] as $obs) {
                // Skip if no test name or no value
                if (empty($obs['test_name']) || $obs['value'] === null) continue;

                $interpretation = PartnerInterpretation::create([
                    'partnership_id' => $partnership->id,
                    'patient_identifier' => $patientId,
                    'test_name' => $obs['test_name'],
                    'value' => (string) $obs['value'],
                    'unit' => $obs['unit'],
                    'reference_range_low' => $obs['ref_low'],
                    'reference_range_high' => $obs['ref_high'],
                    'sex' => $parsed['patient']['sex'] ?? null,
                    'age' => $parsed['patient']['age'] ?? null,
                    'status' => 'pending',
                    'cost_to_partner' => $partnership->rate_per_report ?? 0,
                    'external_id' => $batchId,
                ]);

                $created[] = [
                    'id' => $interpretation->id,
                    'test_name' => $interpretation->test_name,
                    'value' => $interpretation->value,
                    'unit' => $interpretation->unit,
                ];
            }
        }

        return [
            'batch_id' => $batchId,
            'count' => count($created),
            'items' => $created,
        ];
    }

    // ── Private Parsers ──────────────────────────────────

    private function splitSegments(string $message): array
    {
        $segments = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($message));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $segType = substr($line, 0, 3);
            if (!isset($segments[$segType])) {
                $segments[$segType] = [];
            }
            $segments[$segType][] = $line;
        }

        return $segments;
    }

    private function getFields(string $segment): array
    {
        return explode('|', $segment);
    }

    private function getField(string $segment, int $index): ?string
    {
        $fields = $this->getFields($segment);
        return $fields[$index] ?? null;
    }

    private function parsePid(string $segment): array
    {
        $fields = $this->getFields($segment);

        // PID-3: Patient Identifier List (may contain multiple IDs separated by ~)
        $patientId = null;
        if (isset($fields[3])) {
            $ids = explode('~', $fields[3]);
            $firstId = explode('^', $ids[0]);
            $patientId = $firstId[0] ?? null;
        }

        // PID-5: Patient Name
        $name = $fields[5] ?? '';
        $nameParts = explode('^', $name);

        // PID-7: Date of Birth
        $dob = $fields[7] ?? null;
        $age = null;
        if ($dob) {
            try {
                $age = \Carbon\Carbon::createFromFormat('Ymd', substr($dob, 0, 8))->age;
            } catch (\Throwable) {}
        }

        // PID-8: Sex
        $sex = $fields[8] ?? null;

        return [
            'id' => $patientId,
            'name' => trim(($nameParts[1] ?? '') . ' ' . ($nameParts[0] ?? '')),
            'dob' => $dob,
            'age' => $age,
            'sex' => $sex,
        ];
    }

    private function parseObr(string $segment): array
    {
        $fields = $this->getFields($segment);

        // OBR-4: Universal Service ID (test name/code)
        $serviceId = $fields[4] ?? '';
        $serviceParts = explode('^', $serviceId);

        return [
            'set_id' => $fields[1] ?? '1',
            'test_code' => $serviceParts[0] ?? null,
            'test_name' => $serviceParts[1] ?? ($serviceParts[0] ?? 'Unknown'),
            'requested_date' => $fields[7] ?? null,
        ];
    }

    private function parseObxBatch(array $segments): array
    {
        $observations = [];
        foreach ($segments as $seg) {
            $observations[] = $this->parseObx($seg);
        }
        return $observations;
    }

    private function parseObx(string $segment): array
    {
        $fields = $this->getFields($segment);

        // OBX-2: Value Type (NM=numeric, ST=string, etc.)
        $valueType = $fields[2] ?? 'ST';

        // OBX-3: Observation Identifier (test name/code)
        $obsId = $fields[3] ?? '';
        $obsParts = explode('^', $obsId);

        // OBX-5: Observation Value
        $value = $fields[5] ?? null;

        // OBX-6: Units
        $unit = $fields[6] ?? null;

        // OBX-7: Reference Range
        $refRange = $fields[7] ?? '';
        $refParts = explode('-', $refRange);

        return [
            'set_id' => $fields[1] ?? '1',
            'value_type' => $valueType,
            'test_code' => $obsParts[0] ?? null,
            'test_name' => $obsParts[1] ?? ($obsParts[0] ?? 'Unknown'),
            'value' => $value,
            'unit' => $unit,
            'ref_low' => $refParts[0] ?? null,
            'ref_high' => $refParts[1] ?? null,
            'abnormal_flags' => $fields[8] ?? null,
        ];
    }
}