<?php

namespace App\Http\Controllers;

use App\Models\ArtistAlbum;
use App\Models\ArtistSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Intervention\Image\Facades\Image;

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
        // Validator
        Validator::make($request->all(), [
            'album_title' => 'required',
            'album_release_date' => 'required',
            'album_art' => 'required|image|mimes:jpg,jpeg,png,svg|max:2048',
            'album_artist_name' => 'required',
            'album_price' => 'required',
            'songs' => 'required|array|min:1',
            'songs.*.song_title' => 'required',
            'songs.*.song_lyric' => 'nullable',
            'songs.*.song_file' => 'nullable|mimes:mp3,wav,aac,flac,ogg,wma',
        ])->validate();

        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()]);
        // }

        $album = new ArtistAlbum([
            'album_title' => $request->album_title,
            'album_release_date' => $request->album_release_date,
            'album_artist_name' => $request->album_artist_name,
            'album_price' => $request->album_price,
            'album_user_id' => $request->user()->id,
        ]);

        if ($image = $request->file('album_art')) {
            $destinationPath = public_path('images/albums/thumbnails');
            $albumArtName =  $album->album_title . $album->id . "." . $image->getClientOriginalExtension();
            $img = Image::make($image->path());
            $img->fit(280, 280, function ($const) {
                $const->aspectRatio();
            })->save($destinationPath . '/thumb_' . $albumArtName, 90);

            $destinationPath = 'images/albums/main';
            $image->move($destinationPath, $albumArtName);
            $album->album_art = "$albumArtName";
            $album->save();
        }

        foreach ($request->input('songs') as $index => $songData) {
            $song = new ArtistSong([
                'song_title' => $songData['song_title'],
                'song_lyric' => $songData['song_lyric'],
                'album_id' => $album->id,
            ]);

            $song->save();

            if ($request->hasFile("songs.{$index}.song_file") && $request->file("songs.{$index}.song_file")->isValid()) {
                $songFile = $request->file("songs.{$index}.song_file");
                $destinationPath = 'musics/' . $album->album_title;
                $songName = $album->id . $song->id . '_' . $song->song_title . "." . $songFile->getClientOriginalExtension();
                $songFile->move($destinationPath, $songName);
                $song->song_file = $songName;
                $song->save();
            }
        }

        return redirect()->route('artistDashboard');
    }
}
