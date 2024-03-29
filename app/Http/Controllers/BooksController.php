<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Session;
use Yajra\Datatables\Html\Builder;
use Yajra\Datatables\Datatables;
use App\Book;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\BorrowLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;

class BooksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Builder $htmlBuilder)
    {
        if ($request->ajax()) {
            $books = Book::with('author');
            return Datatables::of($books)
                ->addColumn('action', function($book) {
                    return view('datatable._action', [
                        'model'           => $book,
                        'form_url'        => route('books.destroy', $book->id),
                        'edit_url'        => route('books.edit', $book->id),
                        'confirm_message' => 'Yakin menghapus' .$book->title. '?'
                    ]);
                })->make(true);
        }

        $html = $htmlBuilder
            ->addColumn(['data' => 'title', 'name'=>'title', 'title'=>'Judul'])
            ->addColumn(['data' => 'amount', 'name'=>'amount', 'title'=>'Jumlah'])
            ->addColumn(['data' => 'author.name', 'name'=>'author.name', 'title'=>'Penulis'])
            ->addColumn(['data' => 'action', 'name'=>'action', 'title'=>'', 'orderable' => false, 'searchable'=> false]);

            return view('books.index')->with(compact('html'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('books.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBookRequest $request)
    {

        $book = Book::create($request->except('cover'));

        if($request->hasFile('cover')) {
            $uploaded_cover = $request->file('cover');
            $extension = $uploaded_cover->getClientOriginalExtension();
            $filename = md5(time()) . '.' . $extension;
            $destinationPath = public_path() . DIRECTORY_SEPARATOR . 'img';
            $uploaded_cover->move($destinationPath, $filename);

            $book->cover = $filename;
            $book->save();
        }

        Session::flash("flash_notification", [
            "level" => "success",
            "message" => "Berhasil menyimpan $book->title"
        ]);

        return redirect()->route('books.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $book = Book::find($id);
        return view('books.edit')->with(compact('book'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title'     => 'required|unique:books,title,' . $id,
            'author_id' => 'required|exists:authors,id',
            'amount'    => 'required|numeric',
            'cover'     => 'image|max:2048'
        ]);

        $book = Book::find($id);
        $book->update($request->all());

        if($request->hasFile('cover')) {
            $filename = null;
            $uploaded_cover = $request->file('cover');
            $extension = $uploaded_cover->getClientOriginalExtension();
            $filename = md5(time()) . '.' . $extension;
            $destinationPath = public_path() . DIRECTORY_SEPARATOR . 'img';
            $uploaded_cover->move($destinationPath, $filename);

            $book->cover = $filename;
            $book->save();
        }

        Session::flash("flash_notification", [
            "level" => "success",
            "message" => "Berhasil menyimpan $book->title"
        ]);

        return redirect()->route('books.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $book = Book::find($id);

        if ($book->cover) {
            $old_cover = $book->cover;
            $filepath = public_path() . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $book->cover;
            try {
                File::delete($filepath);
            } catch (FileNotFoundException $e) {
                
            }
        }

        $book->delete();

        Session::flash("flash_notification", [
            "level" => "success",
            "message" => "Berhasil dihapus"
        ]);

        return redirect()->route('books.index');
    }

    public function borrow($id) 
    {
        try {
            $book = Book::findOrFail($id);
            Auth::user()->borrow($book);
            Session::flash("flash_notification", [
                "level"     => "success",
                "message"   => "Berhasil meminjam $book->title"
            ]);
        } catch (BookException $e) {
            Session::flash("flash_notification",
                "level" => "danger",
                "message" => $e->getMessage()

        } catch (ModelNotFoundException $e) {
            Session::flash("flash_notification", [
                "level"     => "danger",
                "message"   => "Buku tidak ditemukan."
            ]);
        } 
        return redirect ('/');
    }
}
