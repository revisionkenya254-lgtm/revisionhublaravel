@extends('frontend.instructor-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap pb-0">
        @include('partials.frontend-product-quiz-builder-form', [
            'action' => $isEditing ? route('instructor.quizzes.update', $product->id) : route('instructor.quizzes.store'),
            'method' => $isEditing ? 'PUT' : 'POST',
            'submitLabel' => $isEditing ? __('Update Quiz') : __('Create Quiz'),
            'backUrl' => route('instructor.products.index', ['type' => 'quiz']),
            'showApprovalStatus' => false,
        ])
    </div>
@endsection
