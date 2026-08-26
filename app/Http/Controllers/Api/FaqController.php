<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FaqResource as FaqApiResource;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        return FaqApiResource::collection(Faq::latest()->paginate(15));
    }

    public function show(Faq $faq)
    {
        return new FaqApiResource($faq);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Faq::class);

        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
        ]);

        $faq = Faq::create($data);

        return (new FaqApiResource($faq))->response()->setStatusCode(201);
    }

    public function update(Request $request, Faq $faq)
    {
        $this->authorize('update', $faq);

        $data = $request->validate([
            'question' => ['sometimes', 'string', 'max:255'],
            'answer' => ['sometimes', 'string'],
        ]);

        $faq->update($data);

        return new FaqApiResource($faq);
    }

    public function destroy(Faq $faq)
    {
        $this->authorize('delete', $faq);

        $faq->delete();

        return response()->json(null, 204);
    }
}
