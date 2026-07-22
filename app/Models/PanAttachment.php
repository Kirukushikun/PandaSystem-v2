<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One supporting document on a PAN — up to 3. `path` is a randomized storage
 * key (private disk, never shown); `original_name` is what the uploader named
 * it and is what downloads/lists actually display.
 */
class PanAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['pan_request_id', 'path', 'original_name', 'size'];

    public function panRequest(): BelongsTo
    {
        return $this->belongsTo(PanRequest::class);
    }
}
