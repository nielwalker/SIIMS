<?php

namespace App\Services;

class HelperService
{
  /**
     * Get the full name from first name, middle name, and last name.
     *
     * @param string $firstName
     * @param string $middleName
     * @param string $lastName
     * @return string
     */
  public function getFullName($firstName = "", $middleName = '', $lastName = "")
  {

    // Use getSafeName for each name part to ensure non-null values.
    $firstName = $this->getSafeName($firstName ?? "");
    $middleName = $this->getSafeName($middleName ?? "");
    $lastName = $this->getSafeName($lastName ?? "");

    // Combine first, middle, and last name and return a trimmed full name.
    return trim("{$firstName} {$middleName} {$lastName}");
  }

  /**
     * Ensure the value is not null, return an empty string if null.
     *
     * @param string|null $name
     * @return string
     */
  private function getSafeName(String $name = "")
  {

    return $name ?? "";
  }
}
