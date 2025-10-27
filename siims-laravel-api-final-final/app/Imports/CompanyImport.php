<?php

namespace App\Imports;

use App\Models\User;
use App\Models\UserRole;
use App\Models\Company;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompanyImport implements ToModel, WithValidation
{
    // Function to generate the ID
    protected function generateId()
    {
        $year = date('Y'); // Get the current year
        $randomPart = rand(1000000, 9999999); // Generate a random 7-digit number
        return $year . $randomPart; // Concatenate the year and the random part to make the ID 11 digits
    }

    // Function to generate a secure password
    protected function generatePassword()
    {
        return Str::random(12); // Generate a random 12-character password
    }

    public function model(array $row)
    {
        // Validation rules for each row
        $validator = Validator::make($row, [
            'id' => 'nullable|integer|min:11', // ID is nullable, as we'll generate it if missing
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:100',
            'gender' => 'nullable|string|max:10',
            'phone_number' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'city_municipality' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'company_name' => 'required|string|max:255',
            'website_url' => 'nullable|string|max:255',
        ]);
    
        // If validation fails, skip the row
        if ($validator->fails()) {
            Log::error("Validation failed for row: " . implode(", ", $row));
            return null;
        }

        // Start a database transaction
        DB::beginTransaction();
        try {
            // If the ID is not provided, generate it
            $companyId = isset($row['id']) ? $row['id'] : $this->generateId();

            // Generate a secure password
            $password = $this->generatePassword();
    
            // Fill User Credentials
            $userCredentials = [
                'id' => $companyId,
                'first_name' => $row['first_name'],
                'middle_name' => $row['middle_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'password' => bcrypt($password), // Encrypt the generated password
                'gender' => $row['gender'],
                'phone_number' => $row['phone_number'],
                'street' => $row['street'],
                'barangay' => $row['barangay'],
                'city_municipality' => $row['city_municipality'],
                'province' => $row['province'],
                'postal_code' => $row['postal_code'],
            ];
    
            // Insert User Query
            $user = User::create($userCredentials);
            if (!$user) {
                throw new \Exception("Failed to create user for email {$row['email']}");
            }

            // Assign Role to User
            UserRole::create([
                'user_id' => $user['id'],
                'role_id' => 4, // Assuming 4 is for Company
            ]);

            // Create the Company Record
            Company::create([
                'id' => $user['id'],
                'user_id' => $user['id'],
                'name' => $row['company_name'],
                'website_url' => $row['website_url'],
            ]);
    
            // Commit the transaction
            DB::commit();

            return null;
        } catch (\Exception $e) {
            // Log the error and rollback the transaction
            Log::error("Failed to import row: " . implode(", ", $row) . " Error: " . $e->getMessage());
            DB::rollBack();
            return null; // Skip the row on error
        }
    }

    // The rules method is empty because we handle validation in the model method
    public function rules(): array
    {
        return [];
    }
}