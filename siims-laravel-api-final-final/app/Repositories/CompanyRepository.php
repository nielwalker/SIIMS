<?php

namespace App\Repositories;

use App\Models\Company;

class CompanyRepository implements CompanyRepositoryInterface
{

    // Model variable
    private $company;

    /**
     * Create a new class instance.
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Summary of getTotalCompanies: A public function that gets the total of companies.
     * @return int
     */
    public function getTotalCompanies()
    {
        return $this->company->count();
    }
}
