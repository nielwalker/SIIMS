<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChairpersonProfileResource extends ProfileResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseData = parent::toArray($request);


 
         // Add or modify fields specific to the dean
         return array_merge($baseData, [
             "program" => $this->program ? $this->program->name : "No Program",
             "college" => $this->program->college ? $this->program->college->name : "No College",
         ]);
    }
}
