<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\GD\Driver;

use function Laravel\Prompts\password;

class AccountController extends Controller
{
    // This method will show the register page
    public function register()
    {
        return view('account.register');
    }

    // This method will register a user.
    public function processRegister(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users', //email is unique in the 'users' table
            'password' => 'required|confirmed|min:5',
            'password_confirmation' => 'required',
        ]);

        // Redirect back if validation fails
        if ($validator->fails()) {
            return redirect()->route('account.register')->withInput()->withErrors($validator);
        }

        // If validation passes, proceed to create and store the user in the database
        $user = new User();
        $user->name = $request->input('name'); // Assign the 'name' field from the request
        $user->email = $request->input('email'); // Assign the 'email' field from the request
        $user->password = Hash::make($request->input('password')); // Hash the password before saving
        $user->save();

        // Redirect to the login page with a success message
        return redirect()->route('account.login')->with('success', 'Registration successful! Please log in.');
    }
    public function login()
    {
        return view('account.login');
    }

    public function authenticate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',

        ]);

        if ($validator->fails()) {
            return redirect()->route('account.login')->withInput()->withErrors($validator);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return redirect()->route('account.profile');
        } else {
            return redirect()->route('account.login')->with('error', 'Either email/password is incorrect.');
        }
    }


    // This method will show user profile page
    public function profile()
    {
        $user = User::find(Auth::user()->id);
        //  dd($user);

        return view('account.profile', [
            'user' => $user
        ]);
    }

    // This method will update user profile 
    public function updateProfile(Request $request)
    {
        $rules = [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,' . Auth::user()->id . ',id',
        ];
        if (!empty($request->image)) {
            $rules['image'] = 'image';
        }



        $validator = Validator::make(
            $request->all(),
            $rules
        );

        if ($validator->fails()) {
            return redirect()->route('account.profile')->withInput()->withErrors($validator);
        }

        $user = User::find(Auth::user()->id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        // Here we will upload image
        if (!empty($request->image)) {
            // Delete old image here

            File::delete(public_path('uploads/profile/' . $user->image));
            File::delete(public_path('uploads/profile/thumb/' . $user->image));

            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time() . '.' . $ext;  // 3452232211332.jpg
            $image->move(public_path('uploads/profile/'), $imageName);

            $user->image = $imageName;
            $user->save();

            // create new image instance
            $manager = new ImageManager(Driver::class);
            $img = $manager->read(public_path('uploads/profile/' . $imageName)); // 800 x 600

            $img->cover(150, 150);
            $img->save(public_path('uploads/profile/thumb/' . $imageName));
        }
        return redirect()->route('account.profile')->with('success', 'Profile updte succesfuly');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('account.login');
    }
}
