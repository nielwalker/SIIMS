<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class StudentNumbersImport implements ToCollection
{
    public $studentNumbers = [];
    public $phoneNumbers = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $key => $row) {
            // Skip the header row (usually the first row)
            if ($key === 0) {
                continue;
            }

            // Assuming 'Student No' is at index 3 and 'Phone Number' is at index 23
            $studentNumber = $row[3];
            $contactInfo = $row[23];

            // Skip if the "Contact" column contains the header value ("Contact")
            if (strtolower($contactInfo) === "contact") {
                continue; // Skip this iteration if it's just the column header
            }

            // Check if the Student No is valid and add it
            if (!empty($studentNumber)) {
                $this->studentNumbers[] = (string) $studentNumber;
            }

            // Validate if the value at index 23 looks like a phone number
            $phoneNumber = $this->normalizePhoneNumber($contactInfo);

            if ($phoneNumber) {
                $this->phoneNumbers[] = $phoneNumber;
            } else {
                $this->phoneNumbers[] = null; // Store null if it's not a valid phone number
            }
        }
    }

    /**
     * Normalize and validate the phone number.
     */
    private function normalizePhoneNumber($contactInfo)
    {
        // Remove non-numeric characters except "+"
        $normalized = preg_replace('/[^0-9+]/', '', $contactInfo);

        // Handle cases where the number starts with the country code (+63)
        if (preg_match('/^\+63\d{9}$/', $normalized)) {
            return $normalized; // Valid +63 phone number
        }

        // Handle plain 10-digit numbers (no country code)
        if (preg_match('/^\d{10}$/', $normalized)) {
            return $normalized; // Valid 10-digit phone number
        }

        // Handle scientific notation like "6.39121E+11" by removing it and checking if it's valid
        if (is_numeric($contactInfo)) {
            $numeric = (string) (int) $contactInfo; // Convert scientific notation to plain number
            if (preg_match('/^\d{10}$/', $numeric)) {
                return $numeric; // Valid 10-digit phone number
            }
        }

        // Return null if the phone number is not valid (e.g., "N/A")
        return null;
    }
}
