<?php

namespace App\Http\Controllers\Api;

use App\Models\Faq;
use App\Policies\FaqPolicy;
use Orion\Http\Controllers\Controller;

class FaqController extends Controller
{
    protected $model = Faq::class;

    protected $policy = FaqPolicy::class;

    public function searchableBy(): array
    {
        return ['question'];
    }

    public function filterableBy(): array
    {
        return ['question', 'created_at'];
    }
}
