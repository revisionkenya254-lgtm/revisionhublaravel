@extends('admin.master_layout')

@section('title')
    <title>{{ __('Add Video Lesson') }}</title>
@endsection

@section('admin-content')
    <main class="main-content lesson-builder-admin-shell">
        <section class="section">
            <x-lesson-builder.editor :course="$course" :chapters="$chapters" :selected-chapter-id="$selectedChapterId" :is-admin="true" />
        </section>
    </main>
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('frontend/css/lesson-builder.css') }}?v={{ $setting?->version }}">
@endpush

@push('js')
    <script src="{{ asset('frontend/js/lesson-builder.js') }}?v={{ $setting?->version }}" defer></script>
@endpush
