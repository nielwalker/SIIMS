<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProgramNotFoundException extends Exception
{
   
    public function render()
    {
        return response()->json(['message' => 'Program not found.'], 404);
    }
}
