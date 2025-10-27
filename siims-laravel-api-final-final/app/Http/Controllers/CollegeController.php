<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollegeRequest;
use App\Http\Resources\CollegeResource;
use App\Models\College;
use App\Models\Log;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollegeController extends Controller
{
  /**
   * The authenticated user.
   *
   * @var \Illuminate\Contracts\Auth\Authenticatable|null
   */
  private $user;

  /**
   * CollegeController constructor.
   */
  public function __construct()
  {
    $this->user = Auth::user(); // Initialize the authenticated user
  }

  /**
   * Summary of findCollegeIncludingDean: A private function that finds a College by ID.
   * Include attributes: Dean
   * @param string $college_id
   * @return CollegeResource
   */
  private function findCollegeIncludingDean(String $college_id)
  {

    $college = College::with(['dean'])->find($college_id);

    // Return a College Resource
    return new CollegeResource($college);
  }

  /**
   * Summary of findCollege: A private function that finds a College.
   * @param int $college_id
   * @return TModel|\Illuminate\Database\Eloquent\Collection|null
   */
  private function findCollege(int $college_id) {

    $college = College::find($college_id);

    return $college;

  }

  /**
   * Summary of deleteCollegeById: A function 
   * @param string $college_id
   * @return \Illuminate\Http\JsonResponse
   */
  public function deleteCollegeById(String $college_id)
  {
    // Call the base method to find the college or log failure
    $college = $this->findModelOrFailAndLog(modelClass: College::class, id: $college_id, entity: 'College', actionType: 'Attempted to access a college');

    if ($college instanceof JsonResponse) {
      // Return early if the model wasn't found
      return $college;
    }

    // Delete College
    $college->delete();

    // Return response
    return $this->jsonResponse(['message' => 'A college is deleted.'], 201);
  }
  
  /**
   * Summary of updateCollegeDeanID: A public function that updates a college dean_id.
   * @param int $dean_id
   * @param int $college_id
   * @return void
   */
  public function updateCollegeDeanID(int $dean_id, int $college_id) {

    // Find College
    $college =$this->findCollege($college_id);

    // Update and Save college
    $college->dean_id = $dean_id;
    $college->save();

  }

  /**
   *
   * Summary of getAllListsOfColleges: A public function that gets all list of colleges
   * Usage: This is for selecting a college.
   * @return JsonResponse
   */
  public function getAllListsOfColleges() {

    // Declare variable college
    $collegeLists =  College::all();

    // Transform Colleges
    $transformedCollegeLists = $collegeLists->map(function ($college) {
      return [
        "id" => $college->id,
        "name" => $college->name,
        "dean_id" => $college->dean_id ? "Occupied" : null
      ];
    });

    // Return Colleges
    return $this->jsonResponse($transformedCollegeLists);

  }

  /**
   * Summary of updateCollegeById: A public function that updates a College by ID.
   * @param \App\Http\Requests\CollegeRequest $request
   * @param string $college_id
   * @return mixed
   */
  public function updateCollegeById(CollegeRequest $request, String $college_id)
  {

    // Get validated
    $validated = $request->validated();

    // Find College
    $college = $this->findCollege($college_id);

    /**
     * Search if there is an existing Dean in the College
     */
    if (isset($validated['dean_id'])) {
      $dean = User::find($validated['dean_id']);
      if (!$dean) {
        return response()->json(['message' => "The specified dean does not exist."], 400);
      }

      $existingDeanCollege = College::where('dean_id', $validated['dean_id'])
        ->where('id', '!=', value: $college_id)
        ->first();

      if ($existingDeanCollege) {
        return response()->json(['message' => "This dean is already assigned to another college."], 400);
      }

      $college->dean_id = $validated['dean_id'];
    }

    // Update College
    $college->name = $validated['name'];
    $college->save();

    // Store the login success log in the database
    Log::create([
      'user_id' => $this->user->id,
      'action_type' => 'Update',
      'entity' => 'College',
      'entity_id' => $college->id,
      'description' => "User {$this->user->id} updates a college successfully.",
      'status' => 'Success',
      'http_code' => 201,
      'ip_address' => request()->ip(),
    ]);

    // Return response with status 201
    return $this->jsonResponse(['message' => 'College is updated', 'data' => $this->findCollegeIncludingDean($college_id)], 201);
  }

  /**
   * Summary of create: A public function that creates a new record of college.
   * @param \App\Http\Requests\CollegeRequest $request
   * @return mixed|\Illuminate\Http\JsonResponse
   */
  public function create(CollegeRequest $request)
  {

    // Get validated data
    $validated = $request->validated();

    // Create a new College record
    $college = College::create([
      'name' => $validated['name'],
    ]);

    // Store the login success log in the database
    Log::create([
      'user_id' => $this->user->id,
      'action_type' => 'Create new College',
      'entity' => 'College',
      'entity_id' => $college->id,
      'description' => "User {$this->user->id} creates a new college successfully.",
      'status' => 'Success',
      'http_code' => 201,
      'ip_address' => request()->ip(),
    ]);

    // Return response with status 201
    return $this->jsonResponse([
      'message' => 'A new college is added.',
      'data' => $this->findCollegeIncludingDean($college->id),
    ], 201);
  }

  /**
   * Summary of getAllColleges: A public function that gets all Colleges.
   * Included attributes: Dean
   * @return mixed|\Illuminate\Http\JsonResponse
   */
  public function getAllColleges(Request $request)
  {
    // Define the number of items per page (default to 5)
    $perPage = (int) $request->input('perPage', 5);

    // Get and sanitize the search term
    $searchTerm = $this->sanitizeAndGet($request);

    // Get all Colleges including the soft deleted.
    $query = College::withTrashed()->with(['dean']);

    // Apply the search filter if search term is provided
    if (!empty($searchTerm)) {
      $query->where('name', 'LIKE', '%' . strtolower($searchTerm) . '%');
    }

    // Paginate the results
    $colleges = $query->paginate($perPage);

    // Return College collection
    return CollegeResource::collection($colleges);
  }
}
