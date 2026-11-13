@php
use Illuminate\Support\Facades\Storage;
@endphp

@php
    $testEvent = \App\Models\Event::whereNotNull('image')->first();
@endphp

<div style="background: yellow; padding: 20px; margin: 20px;">
    <h3>TEST IMAGE</h3>
    <p><strong>Chemin BDD :</strong> {{ $testEvent->image }}</p>
    <p><strong>Media::url() :</strong> {{ \App\Support\Media::url($testEvent->image) }}</p>
    <p><strong>asset() :</strong> {{ \App\Support\Media::url($testEvent->image) }}</p>
    
    <h4>Avec Media::url() :</h4>
    <img src="{{ \App\Support\Media::url($testEvent->image) }}" style="max-width: 200px; border: 2px solid green;">
    
    <h4>Avec asset() :</h4>
    <img src="{{ \App\Support\Media::url($testEvent->image) }}" style="max-width: 200px; border: 2px solid blue;">
</div>