@extends('layouts.course-builder')

@section('title', __('Add Video Lesson'))

@section('content')
    <main class="lesson-builder-standalone">
        <x-lesson-builder.editor :course="$course" :chapters="$chapters" :selected-chapter-id="$selectedChapterId" :is-admin="false" />
    </main>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/lesson-builder.css') }}?v={{ config('app.asset_version', '1') }}">
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/lesson-builder.js') }}?v={{ config('app.asset_version', '1') }}" defer></script>
@endpush
