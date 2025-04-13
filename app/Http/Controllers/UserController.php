<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'full_name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        return User::create($request->all());
    }

    public function show($id)
    {
        return User::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => 'sometimes|required',
            'full_name' => 'sometimes|required',
            'email' => 'sometimes|required|email',
            'password' => 'sometimes|required',
        ]);

        $user->update($request->all());

        return $user;
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->noContent();
    }
}
