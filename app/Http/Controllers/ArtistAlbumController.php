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
    /**
     * Display albums on the welcome page.
     */
    public function index()
    {
        $posts = ArtistAlbum::with('artist_song')->get();
        return Inertia::render('Welcome', ['props' => $posts]);
    }

    /**
     * Retrieve and display albums created by the currently authenticated artist user.
     */
    public function artistDashboardIndex()
    {
        $total_songs = 0;
        $posts = ArtistAlbum::where('album_user_id', Auth::id())->with('artist_song')->get();

        foreach ($posts as $post) {
            $total_songs += count($post->artist_song);
        }

        return Inertia::render('Menus/Artist/Dashboard', ['posts' => $posts, 'songsCount' => $total_songs]);
    }

    /**
     * Retrieve and display information about a specific album.
     */
    public function albumInfoIndex($id)
    {
        $posts  = ArtistAlbum::with('artist_song')->find($id);
        return Inertia::render('Contents/AlbumInfo', [
            'posts' => $posts,
        ]);
    }

    /**
     * Search for albums and songs that match the given keyword.
     */
    public function search($key)
    {
        return ArtistAlbum::with('artist_song')
            ->where('album_title', 'like', "%$key%")
            ->orWhereHas('artist_song', function ($query) use ($key) {
                $query->where('song_title', 'like', "%$key%");
            })
            ->get();
    }

    /**
     * Display the page for adding a new artist album.
     */
    public function create()
    {
        return Inertia::render('Menus/Artist/AddAlbum');
    }

    /**
     * Store a newly created artist album.
     */
    public function store(Request $request)
    {
        // Validator
        Validator::make($request->all(), [
            'album_title' => 'required',
            'album_release_date' => 'required',
            'album_art' => 'required|image|mimes:jpg,jpeg,png,svg|max:2048',
            'album_artist_name' => 'required',
            'album_price' => 'nullable',
            'songs' => 'required|array|min:1',
            'songs.*.song_title' => 'required',
            'songs.*.song_lyric' => 'nullable',
            'songs.*.song_file' => 'nullable|mimes:mp3,wav,aac,flac,ogg,wma|max:1048576',
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

    /**
     * Display the edit form for a specific post.
     */
    public function edit($id)
    {
        $posts  = ArtistAlbum::with('artist_song')->find($id);

        if ($posts->album_user_id == Auth::id()) {
            return Inertia::render('Menus/Artist/EditAlbum', [
                'posts' => $posts,
            ]);
        } else {
            abort(403, 'Unauthorized action.');
        }
    }

    public function update(Request $request, $id)
    {
        // Validator
        dd($request->all());
        $validator = Validator::make($request->all(), [
            'album_title' => 'required',
            'album_release_date' => 'required',
            'album_art' => 'required|max:2048',
            'album_artist_name' => 'required',
            'album_price' => 'nullable',
            'songs' => 'required|array|min:1',
            'songs.*.song_title' => 'required',
            'songs.*.song_lyric' => 'nullable',
            'songs.*.song_file' => 'nullable|mimes:mp3,wav,aac,flac,ogg,wma|max:1048576',
        ]);
        dd($validator);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        $album = ArtistAlbum::findOrFail($id);
        $album->album_title = $request->album_title;
        $album->album_release_date = $request->album_release_date;
        $album->album_artist_name = $request->album_artist_name;
        $album->album_price = $request->album_price;
        $album->save();

        if ($image = $request->file('album_art')) {
            if ($album->album_art) {
                $oldImage = public_path('images/albums/main/' . $album->album_art);
                if (file_exists($oldImage)) {
                    unlink($oldImage);
                }
                $oldThumb = public_path('images/albums/thumbnails/thumb_' . $album->album_art);
                if (file_exists($oldThumb)) {
                    unlink($oldThumb);
                }
            }
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
                if ($song->song_file) {
                    $oldSongFile = public_path('musics/' . $album->album_title . '/' . $song->song_file);
                    if (file_exists($oldSongFile)) {
                        unlink($oldSongFile);
                    }
                }
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
