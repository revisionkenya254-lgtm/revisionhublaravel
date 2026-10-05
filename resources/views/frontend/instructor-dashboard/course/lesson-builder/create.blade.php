@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    <x-lesson-builder.editor :course="$course" :chapters="$chapters" :selected-chapter-id="$selectedChapterId" :is-admin="false" />
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/lesson-builder.css') }}?v={{ $setting?->version }}">
@endpush

@push('scripts')
    <script src="{{ asset('frontend/js/lesson-builder.js') }}?v={{ $setting?->version }}" defer></script>
@endpush
