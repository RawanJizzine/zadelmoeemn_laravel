<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users',
            'phone_number' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        // Create a new user instance
        $user = new User();
        $user->full_name = $request->full_name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->phone_number=$request->phone_number;
        $user->password = Hash::make($request->password);
    
        // Check if an image was uploaded
        if ($request->hasFile('image')) {
            // Generate a unique name for the image
            $imageName = time() . '.' . $request->image->extension();
    
            // Move the image to the public directory
            $request->image->move(public_path('images'), $imageName);
    
            // Save the image name to the user record
            $user->image = $imageName;
        }
    
        // Save the user to the database
        $user->save();
    
        // Return a success response
        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }
    public function index(Request $request)
    {
        $query = User::query();
    
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('full_name', 'like', "%{$search}%");
        }
    
        return response()->json($query->get());
    }
    public function getUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }
    public function updateUser(Request $request, $id)
{
   
    $user = User::find($id);

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $request->validate([
        'username' => 'required|string|max:255',
        'phone_number' => 'required|string|max:255',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $user->username = $request->username;
    $user->phone_number = $request->phone_number;

    if ($request->hasFile('image')) {
        // Generate a unique name for the image
        $imageName = time() . '.' . $request->image->extension();

        // Move the image to the public directory
        $request->image->move(public_path('images'), $imageName);

        // Save the image name to the user record
        $user->image = $imageName;
    }

    $user->save();

    return response()->json($user);
}
}
