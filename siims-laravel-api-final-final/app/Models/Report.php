<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'file_path',
        'application_id',
        'report_type_id',
    ];

    public function reportType() {
        return $this->belongsTo(ReportType::class, 'report_type_id');
    }
}
