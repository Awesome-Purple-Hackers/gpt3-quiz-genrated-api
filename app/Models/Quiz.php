<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    protected $fillable = [
        'question', 'options', 'correct_answer'
    ];

    protected $casts = [
        'options' => 'json'
    ];
}