@php
    $path = $getState();
    $url = asset('storage/' . $path);
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
@endphp

@if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']))
    <img
        src="{{ $url }}"
        alt="Ticket attachment"
        class="w-full max-h-[700px] object-contain rounded-lg"
    >

@elseif ($extension === 'pdf')
    <iframe
        src="{{ $url }}"
        class="w-full h-[800px] rounded-lg"
        title="PDF Preview"
    ></iframe>

@else
    <a
        href="{{ $url }}"
        target="_blank"
        class="text-primary-600 hover:underline"
    >
        Open Attachment
    </a>
@endif
