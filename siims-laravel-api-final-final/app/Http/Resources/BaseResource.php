<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{

    /**
     * Summary of getFullName: Returns a Full name (Combination of first, middle, and last name)
     * @param string $first_name
     * @param string $middle_name
     * @param string $last_name
     * @return string
     */
    protected function getFullName(String $firstName = "", String $middleName = "", String $lastName = "")
    {

         // Ensure that any null values are treated as empty strings
    $firstName = (string) $firstName;
    $middleName = (string) $middleName;
    $lastName = (string) $lastName;

        return trim(implode(' ', array_filter([$firstName ?? "", $middleName ?? "", $lastName ?? ""])));
    }

    /**
     * Concatenate address fields into a full address.
     *
     * @param string $street
     * @param string $barangay
     * @param string $city
     * @param string $province
     * @param string $postalCode
     * @return string
     */
    protected function getFullAddress($street, $barangay, $city, $province, $postalCode)
    {
        $addressParts = array_filter([$street, $barangay, $city, $province, $postalCode]);
        return implode(', ', $addressParts);
    }

    /**
     * Summary of formatDate: Returns a Month Day Year date format
     * @param string $date
     * @return string
     */
    protected function formatDate($date)
    {
        return Carbon::parse($date)->format('F j, Y, g:i a');
    }

    /**
     * Summary of formatDateOnlyDate: A protected function that returns only date
     * @param mixed $date
     * @return string
     */
    protected function formatDateOnlyDate($date)
    {
        // If the date is already a Carbon instance, format it
        if ($date instanceof Carbon) {
            return $date->format('F j, Y');  // e.g., "November 19, 2024"
        }

        // Otherwise, parse and format the date
        return Carbon::parse($date)->format('F j, Y');  // e.g., "November 19, 2024"
    }
}
