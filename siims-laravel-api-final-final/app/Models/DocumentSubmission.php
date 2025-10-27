<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSubmission extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'application_id',
        'doc_type_id',
        'name',
        'doc_status_id',
        'document_status_id',
        'file_path',
        'remarks',
    ];

    public function application() {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function documentType() {
        return $this->belongsTo(DocumentType::class, 'doc_type_id');
    }

    public function status() {
        return $this->belongsTo(Status::class, 'doc_status_id');
    }

    public function documentStatus() {
        return $this->belongsTo(DocumentStatus::class, 'document_status_id');
    }
}
