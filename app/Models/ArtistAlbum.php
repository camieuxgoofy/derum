<?php

namespace App\Models;

use Conner\Likeable\Likeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtistAlbum extends Model
{
    use HasFactory, Likeable;

    public $table = "artist_album";
    
    public $timestamps = false;

    protected $fillable = [
        'album_title',
        'album_release_date',
        'album_art',
        'album_artist_name',
        'album_price',
        'album_user_id'
    ];

    public function artist_song(): HasMany
    {
        return $this->hasMany(ArtistSong::class, 'album_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
