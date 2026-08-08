<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestHistory extends Model
{
    protected $fillable = ['internal_request_id', 'from_status', 'to_status', 'reason', 'changed_by'];

    public function internalRequest(): BelongsTo
    {
        return $this->belongsTo(InternalRequest::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
