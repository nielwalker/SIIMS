<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentStep extends Model
{
    use HasFactory;

    public function documentTypes() {
        return $this->hasMany(DocumentType::class, 'document_step_id');
    }

}
