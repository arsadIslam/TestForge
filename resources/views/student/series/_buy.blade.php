{{-- Buy button. Expects $type ('series'|'plan'), $id, $label. Only WHAT is posted — never a price. --}}
<form method="POST" action="{{ route('student.checkout.store') }}" onsubmit="this.querySelector('button').disabled = true">
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="hidden" name="id" value="{{ $id }}">
    <x-primary-button>{{ $label }}</x-primary-button>
</form>
