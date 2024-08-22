<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;

class HomeController extends Controller
{
    // This method will show home page

    public function index()
    {

        $books = Book::orderBy('created_at', 'DESC')->where('status', 1)->paginate(8);
        return view(
            'home',
            [
                'books' => $books
            ]
        );
    }
}
