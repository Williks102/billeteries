@extends('layouts.app')

@section('title', 'Questions fréquentes - ClicBillet CI')

@section('content')
<div class="container my-5">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold text-orange mb-4">Questions fréquentes</h1>
            <p class="lead">Trouvez rapidement les réponses à vos questions</p>
        </div>
    </div>

    <!-- Search FAQ -->
    <div class="row mb-5">
        <div class="col-md-8 mx-auto">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Rechercher dans la FAQ..." id="faqSearch">
                <button class="btn btn-orange" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- FAQ Sections -->
    @foreach($faqs as $sectionKey => $section)
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="border-bottom pb-3 mb-4">
                <i class="fas fa-question-circle text-orange me-3"></i>{{ $section['title'] }}
            </h2>
            
            <div class="accordion" id="accordion{{ ucfirst($sectionKey) }}">
                @foreach($section['questions'] as $index => $faq)
                <div class="accordion-item mb-3 border rounded">
                    <h3 class="accordion-header" id="heading{{ $sectionKey }}{{ $index }}">
                        <button class="accordion-button collapsed" type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse{{ $sectionKey }}{{ $index }}" 
                                aria-expanded="false" 
                                aria-controls="collapse{{ $sectionKey }}{{ $index }}">
                            <strong>{{ $faq['question'] }}</strong>
                        </button>
                    </h3>
                    <div id="collapse{{ $sectionKey }}{{ $index }}" 
                         class="accordion-collapse collapse" 
                         aria-labelledby="heading{{ $sectionKey }}{{ $index }}" 
                         data-bs-parent="#accordion{{ ucfirst($sectionKey) }}">
                        <div class="accordion-body">
                            <p>{{ $faq['answer'] }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach

    <!-- Contact pour autres questions -->
    <div class="row">
        <div class="col-12">
            <div class="bg-light p-5 rounded text-center">
                <h3 class="mb-3">Vous n'avez pas trouvé votre réponse ?</h3>
                <p class="mb-4">Notre équipe support est là pour vous aider !</p>
                <a href="{{ route('pages.contact') }}" class="btn btn-orange btn-lg me-3">
                    <i class="fas fa-envelope me-2"></i>Nous contacter
                </a>
                <a href="{{ route('pages.support') }}" class="btn btn-outline-orange btn-lg">
                    <i class="fas fa-life-ring me-2"></i>Support technique
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('faqSearch');
    const accordionItems = document.querySelectorAll('.accordion-item');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        accordionItems.forEach(item => {
            const questionText = item.querySelector('.accordion-button').textContent.toLowerCase();
            const answerText = item.querySelector('.accordion-body').textContent.toLowerCase();
            
            if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                item.style.display = 'block';
                if (searchTerm.length > 2) {
                    // Ouvrir l'accordéon si match
                    const collapse = item.querySelector('.accordion-collapse');
                    const button = item.querySelector('.accordion-button');
                    collapse.classList.add('show');
                    button.classList.remove('collapsed');
                    button.setAttribute('aria-expanded', 'true');
                }
            } else {
                item.style.display = searchTerm === '' ? 'block' : 'none';
            }
        });
    });
});
</script>
@endsection