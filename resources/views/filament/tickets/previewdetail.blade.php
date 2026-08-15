@php
    $path = $getState();
    $url = asset('storage/' . $path);
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
@endphp

@if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']))
    <div class="overflow-hidden rounded-xl border">
        <img
            src="{{ $url }}"
            alt="Ticket attachment"
            class="max-h-[600px] w-auto mx-auto"
        >
    </div>

@elseif ($extension === 'pdf')
    <div class="overflow-hidden rounded-xl border">
        <iframe
            src="{{ $url }}"
            class="w-full h-[700px]"
            title="PDF Preview"
        ></iframe>
    </div>

@else
    <a
        href="{{ $url }}"
        target="_blank"
        class="text-primary-600 hover:underline"
    >
        Open Attachment
    </a>
@endif