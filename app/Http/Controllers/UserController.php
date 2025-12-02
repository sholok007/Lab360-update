<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index() {
        $authUser = Auth::user();

        if ($authUser->role === 'Admin') {
            $users = User::paginate(10);

        } elseif ($authUser->role === 'Proprietor') {
           
            $users = User::where('id', $authUser->id)
                        ->orWhere('parent_id', $authUser->id)
                        ->paginate(10);

        } else {
          
            $users = User::where('id', $authUser->id)->paginate(10);
        }

        return view('users.index', compact('users'));
    }

    public function create() {
        return view('users.create');
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:Proprietor,Moderator'
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'parent_id' => Auth::id(), 
        ]);

        return redirect()->back()->with('success', 'User created successfully');
    }

   public function edit($id) {
        $currentUser = Auth::user();
        $user = User::findOrFail($id);
        if (
            $currentUser->role === 'Admin' || 
            ($currentUser->role === 'Proprietor' && ($user->parent_id == $currentUser->id || $user->id == $currentUser->id)) || 
            ($currentUser->role === 'Moderator' && $user->id == $currentUser->id) 
        ) {
            return view('users.edit', compact('user'));
        }

abort(403, 'Unauthorized action.');
    }

   public function update(Request $request, $id){
        $currentUser = Auth::user();
        $user = User::findOrFail($id);

        if (!(
            $currentUser->role === 'Admin' ||
            ($currentUser->role === 'Proprietor' && ($user->parent_id == $currentUser->id || $user->id == $currentUser->id)) ||
            ($currentUser->role === 'Moderator' && $user->id == $currentUser->id)
        )) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:Proprietor,Moderator',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->contact_no = $request->contact_no;

        if ($currentUser->role === 'Admin' || ($currentUser->role === 'Proprietor' && $user->parent_id == $currentUser->id)) {
            $user->role = $request->role;
        }

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('profile_pictures', $filename, 'public');

            if ($user->profile_picture && \Storage::disk('public')->exists($user->profile_picture)) {
                \Storage::disk('public')->delete($user->profile_picture);
            }

            $user->profile_picture = $path;
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }



    public function destroy(User $user){
        $currentUser = Auth::user();

        // Authorization
        if ($currentUser->role === 'Admin') {
            // ok
        } elseif ($currentUser->role === 'Proprietor') {
            if ($user->parent_id !== $currentUser->id) {
                abort(403, 'Unauthorized action.');
            }
        } else {
            if ($currentUser->id != $user->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }
}


