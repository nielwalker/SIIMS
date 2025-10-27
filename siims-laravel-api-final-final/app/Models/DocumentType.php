<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Summary of documentSubmissions: Each Document Type has one or many Document Submissions.
     * @return \App\Models\HasMany
     */
    public function documentSubmissions(): HasMany {
        return $this->hasMany(DocumentSubmission::class, 'doc_type_id');
    }

    public function documentStep() {
        return $this->belongsTo(DocumentStep::class);
    }

}
