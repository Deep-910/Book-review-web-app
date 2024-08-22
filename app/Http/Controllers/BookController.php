<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


class BookController extends Controller
{
    // This method will show books listing page
    public function index(Request $request)
    {

        $books = Book::orderBy('created_at', 'DESC');

        if (!empty($request->keyword)) {
            $books->where('title', 'like', '%' . $request->keyword . '%');
        }
        $books = $books->paginate(4); {
            return view('books.list', [
                'books' => $books
            ]);
        }
    }

    /// This method will create book page
    public function create()
    {
        return view('books.create');
    }


    // This method will a  book in database
    public function store(Request $request)
    {

        $rules = [
            'title' => 'required|min:5',
            'author' => 'required|min:3',
            'status' => 'required',
        ];

        if (!empty($request->image)) {
            $rules['image'] = 'image';
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            return redirect()->route('books.create')->withInput()->withErrors($validator);
        }

        // Save book in DB

        $book = new Book();
        $book->title = $request->title;
        $book->description = $request->description;
        $book->author = $request->author;
        $book->status = $request->status;
        $book->save();

        // upload book image here

        if (!empty($request->image)) {
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time() . '.' . $ext;
            $image->move(public_path('uploads/books/'), $imageName);
            $book->image = $imageName;
            $book->save();

            // create new image instance
            $manager = new ImageManager(Driver::class);
            $img = $manager->read(public_path('uploads/books/' . $imageName)); // 800 x 600

            $img->resize(990);
            $img->save(public_path('uploads/books/thumb/' . $imageName));
        }

        return redirect()->route('books.index')->with('success', 'Book added Succesfully.');
    }
    // This method will a edit a book
    public function edit($id)
    {
        $book = Book::findOrFail($id);
        // dd($book);
        return view(
            'books.edit',
            [
                'book' => $book
            ]
        );
    }
    // This method will update books
    public function update($id, Request $request)
    {
        $book = Book::findOrFail($id);

        $rules = [
            'title' => 'required|min:5',
            'author' => 'required|min:3',
            'status' => 'required',
        ];

        if (!empty($request->image)) {
            $rules['image'] = 'image';
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {

            return redirect()->route('books.edit', $book->id)->withInput()->withErrors($validator);
        }

        // update book in DB

        $book = new Book();
        $book->title = $request->title;
        $book->description = $request->description;
        $book->author = $request->author;
        $book->status = $request->status;
        $book->save();

        // upload book image here

        if (!empty($request->image)) {

            //This will deleted old book imae from diretory
            File::delete(public_path('uploads/books' . $book->image));
            File::delete(public_path('uploads/books/thumb/' . $book->image));

            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time() . '.' . $ext;
            $image->move(public_path('uploads/books/'), $imageName);
            $book->image = $imageName;
            $book->save();

            // creare thumbnail of   image instance
            $manager = new ImageManager(Driver::class);
            $img = $manager->read(public_path('uploads/books/' . $imageName)); // 800 x 600
            $img->resize(990);
            $img->save(public_path('uploads/books/thumb/' . $imageName));
        }

        return redirect()->route('books.index')->with('success', 'Book updated Succesfully.');
    }


    // This method will  delte books 
    public function destroy(Request $request)
    {

        $book = Book::find($request->id);
        if ($book == null) {
            session()->flash('error', 'Book not found');
            return response()->json([
                'status' => false,
                'message' => 'Book not found'
            ]);
        } else {
            File::delete(public_path('uploads/books/'), $book->image);
            File::delete(public_path('uploads/books/thumb.'), $book->image);
            $book->delete();

            session()->flash('success', 'Book deleted sucessfully');

            return  response()->json(
                [
                    'status' => true,
                    'message' => 'Book delted successfully'
                ]
            );
        }
    }
}
