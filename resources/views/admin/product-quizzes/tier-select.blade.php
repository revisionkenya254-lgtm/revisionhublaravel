@extends('admin.master_layout')

@section('title')
    <title>{{ __('Create Quiz') }}</title>
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
                <h1>{{ __('Create Quiz') }}</h1>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5>{{ __('Short Quiz') }}</h5>
                                <p>{{ __('Fewer than 10 questions. Sale price must not exceed KES 50.') }}</p>
                                <a href="{{ route('admin.quizzes.create-tier', 'short') }}" class="btn btn-primary">{{ __('Create Short Quiz') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5>{{ __('Long Quiz') }}</h5>
                                <p>{{ __('More than 10 questions. Sale price must be at least KES 20.') }}</p>
                                <a href="{{ route('admin.quizzes.create-tier', 'long') }}" class="btn btn-primary">{{ __('Create Long Quiz') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
