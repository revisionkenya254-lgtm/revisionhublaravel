@extends('admin.master_layout')

@section('title')
    <title>{{ $isEditing ? __('Edit Quiz') : __('Create Quiz') }}</title>
@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <div class="section-header-back">
                    <a href="{{ route('admin.quizzes.index') }}" class="btn btn-icon">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
                <h1>{{ $isEditing ? __('Edit Quiz') : __('Create Quiz') }}</h1>
            </div>

            <div class="section-body">
                @include('partials.product-quiz-builder-form', [
                    'action' => $isEditing ? route('admin.quizzes.update', $product->id) : route('admin.quizzes.store'),
                    'method' => $isEditing ? 'PUT' : 'POST',
                    'submitLabel' => $isEditing ? __('Update Quiz') : __('Create Quiz'),
                    'backUrl' => route('admin.quizzes.index'),
                    'showApprovalStatus' => true,
                ])
            </div>
        </section>
    </div>
@endsection
