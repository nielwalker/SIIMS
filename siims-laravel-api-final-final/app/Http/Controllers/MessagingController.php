<?php

namespace App\Http\Controllers;

use App\Http\Requests\GroupRequest;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessagingController extends Controller
{
    /**
     * The searchTerm
     *
     */
    private $searchTerm;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    private $user;

    /**
     * DocumentTypeController constructor.
     */
    public function __construct(Request $request)
    {
        $this->searchTerm = $request['search']; 
        $this->user = Auth::user(); // Initialize the authenticated user
    }

    /**
     * Summary of getMyGroups: A public function that gets all groups.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getMyGroups() {

        // Find Groups
        $groups = Group::where('owner_id', $this->user->id)->get();

        // Return Groups
        return response()->json($groups, 200);

    }

    public function getGroupDetails() {
        return [];
    }

    /**
     * Summary of findUsers: Finds the Users base on the search term.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findUsers()
    {
        $users = User::where('first_name', 'like', "%{$this->searchTerm}%")
            ->orWhere('middle_name', 'like', "%{$this->searchTerm}%")
            ->orWhere('last_name', 'like', "%{$this->searchTerm}%")
            ->orWhere('email', 'like', "%{$this->searchTerm}%")
            ->get();

        // Transform User data to include full_name
        $users = $users->map(function ($user) {
            return $this->transformUser($user);
        });

        // Return Users
        return $users;
    }

    public function getGroupMessages() {
        return [];
    }

    /**
     * Summary of searchUsers: A public function that search the Users.
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function searchUsers()
    {

        // Search users by first_name, middle_name, last_name, or email
        $users = $this->findUsers();

        // Return response with status 200
        return response()->json($users, 200);
    }

    /**
     * Summary of transformGroup: A private function that transforms the Group's attributes.
     * @param \App\Models\Group $group
     * @return array
     */
    private function transformGroup(Group $group)
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
        ];
    }

    /**
     * Summary of transformUser: A private function that transforms the User's attributes.
     * @param \App\Models\User $user
     * @return array
     */
    private function transformUser(User $user)
    {
        return [
            'id' => $user->id,
            'full_name' => trim("{$user->first_name} {$user->middle_name} {$user->last_name}"),
            'email' => $user->email,
            // You can add other fields like avatar if necessary
        ];
    }

    /**
     * Summary of searchUser: A public function to get a searched User or Group
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function searchUsersAndGroups()
    {
        // Find All Users
        $users = $this->findUsers();

        // Search group conversations by name
        $groups = Group::where('name', 'like', "%{$this->searchTerm}%")
            ->get();

        // Transform Group data to include name and ID
        $groups = $groups->map(function ($group) {
            return $this->transformGroup($group);
        });

        // Combine both results
        $results = [
            'users' => $users,
            'groups' => $groups,
        ];

        // Return response with status 200;
        return response()->json($results);
    }

    /**
     * Summary of createGroup: A public function that creates a new group.
     * @param \App\Http\Requests\GroupRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function createGroup(GroupRequest $request)
    {

        // Get Auth User
        $user = Auth::user();

        // Get validated
        $validated = $request->validated();

        // Create Group
        $group = Group::create([
            "name" => $validated['name'],
            // "description" => $validated['description'],
            "owner_id" => $user->id,
        ]);

        // Loop each Members
        foreach ($validated['members'] as $memberId) {
            // Attach members to the group
            GroupUser::create([
                "group_id" => $group->id,
                "user_id" => $memberId,
            ]);
        }

        // Return response with status 201
        return response()->json(['message' => "A group is created."], 201);
    }
}
