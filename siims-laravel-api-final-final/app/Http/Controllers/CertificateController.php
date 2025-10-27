<?php

namespace App\Http\Controllers;

use App\Http\Requests\CertificateRequest;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    

    // Cloud controller
    private $cloudController;

    public function __construct(CloudController $cloudController) {
        $this->cloudController = $cloudController;
    }


    /**
     * Summary of getAllCertificates: A public function that gets all certificates
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllCertificates() {

        // Get authenticated user
        $authUser = Auth::user();

        // Find All Certificates
        $certificates = Certificate::where('student_id', $authUser->id)->get();

        // Return
        return $this->jsonResponse($certificates, 200);


    }

    /**
     * Summary of deleteCertificateByID: A public function that deletes a certificate by ID.z
     * @param string $certificate_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCertificateByID(String $certificate_id) {

        // Get auth user
        $authUser = Auth::user();

        // Find certificate by student ID
        $certificate = Certificate::where('student_id', $authUser->id)->find($certificate_id);

        // Delete certificate
        $certificate->delete();

        // Return
        return $this->jsonResponse(['message' => 'Certificate is deleted.'], 201);

    }

    /**
     * Summary of addNewCertificate: A public function that creates a new certificate.
     * @param \App\Http\Requests\CertificateRequest $certificateRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function addNewCertificate(CertificateRequest $certificateRequest) {
        // Get authenticated user
        $authUser = Auth::user();

        // Store the certificates to the cloud
        $fileUrl = $this->cloudController->storeFileToCloud($certificateRequest, "public/uploads/users/{$authUser->id}/certificates", "file");
        
        // Get validated
        $validated = $certificateRequest->validated();

        // Check if file url does not exist
        if(!$fileUrl) {
            return $this->jsonResponse(['message' => 'Something is wrong.'], 400);
        }

        // Create a new certificate
        $certificate = Certificate::create([
            'name' => $validated['name'],
            'file_path' => $fileUrl,
            "issued_date" => $validated['issued_date'],
            "student_id" => $authUser->student->id, 
        ]);

        // Return created new certificate
        return $this->jsonResponse(['message' => 'Certificate is created', 'data' => $certificate], 201);

    }

}
