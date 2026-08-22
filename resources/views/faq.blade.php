<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FAQ - {{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        .faq-content :is(h1, h2, h3) { font-weight: 600; margin: 0.75rem 0 0.25rem; }
        .faq-content p { margin: 0.5rem 0; }
        .faq-content ul, .faq-content ol { margin: 0.5rem 0 0.5rem 1.25rem; }
        .faq-content ul { list-style: disc; }
        .faq-content ol { list-style: decimal; }
        .faq-content a { color: #2563eb; text-decoration: underline; }
        .faq-content blockquote { border-left: 3px solid #d1d5db; padding-left: 0.75rem; color: #4b5563; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen py-12 px-4">
    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-semibold text-gray-900 mb-8">Frequently Asked Questions</h1>

        @forelse ($faqs as $faq)
            <details class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
                <summary class="cursor-pointer font-medium text-gray-900">
                    {{ $faq->question }}
                </summary>
                <div class="faq-content mt-3 text-gray-700">
                    {!! $faq->answer !!}
                </div>
            </details>
        @empty
            <p class="text-gray-500">Belum ada pertanyaan yang tersedia.</p>
        @endforelse
    </div>
</body>
</html>