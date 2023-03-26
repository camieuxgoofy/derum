<?php

namespace App\Http\Controllers;

use App\Models\ArtistAlbum;
use App\Models\ArtistSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ArtistAlbumController extends Controller
{
    public function index()
    {
        $posts = ArtistAlbum::all();
        // dd($posts);
        return Inertia::render('Welcome', ['posts' => $posts]);
    }

    public function artistIndex()
    {
        $posts = ArtistAlbum::where('album_user_id', Auth::id())->get();

        return Inertia::render('Menus/Artist/Dashboard', ['posts' => $posts]);
    }

    public function create()
    {
        return Inertia::render('Menus/Artist/AddAlbum');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'album_title' => 'required',
            'album_release_date' => 'required',
            'album_art' => 'required|mimes:jpeg,jpg,png',
            'album_artist_name' => 'required',
            'album_price' => 'required',
            'songs' => 'required|array|min:1',
            'songs.*.song_title' => 'required',
            'songs.*.song_lyric' => 'required',
            'songs.*.song_file' => 'required|mimes:mp3,wav',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        $albumArt = '';
        if ($image = $request->file('album_art')) {
            $destinationPath = 'images/content/albums';
            $albumArtName = date('YmdHis') . "." . $image->getClientOriginalName();
            $image->move($destinationPath, $albumArtName);
            $albumArt = "$albumArtName";
        }

        $album = ArtistAlbum::create([
            'album_title' => $request->album_title,
            'album_release_date' => $request->album_release_date,
            'album_art' => $albumArt,
            'album_artist_name' => $request->album_artist_name,
            'album_price' => $request->album_price,
            'album_user_id' => $request->user()->id,
        ]);

        foreach ($request->input('songs') as $index => $songData) {
            $songFileName = '';
            if ($request->hasFile("songs.{$index}.song_file") && $request->file("songs.{$index}.song_file")->isValid()) {
                $songFile = $request->file("songs.{$index}.song_file");
                $destinationPath = 'musics';
                $songName = date('YmdHis') . "." . $songFile->getClientOriginalName();
                $songFile->move($destinationPath, $songName);
                $songFileName = $songName;
            }

            $song = new ArtistSong([
                'song_title' => $songData['song_title'],
                'song_lyric' => $songData['song_lyric'],
                'album_id' => $album->id,
                'song_file' => $songFileName,
            ]);
            $song->save();
        }

        // return response()->json(['success' => true]);
        return redirect()->route('artistDashboard');

    }
}
