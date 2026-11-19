@extends('admin.master_layout')

@php
    $editing = isset($node);
    $formProfile = $parent?->taxonomy_profile ?? ($node->taxonomy_profile ?? request('profile'));
    $profileConfig = config("higher_education_taxonomy.profiles.{$formProfile}", []);
@endphp
@section('title')<title>{{ $editing ? __('Edit Taxonomy Node') : __('Add Taxonomy Node') }}</title>@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header"><h1>{{ $editing ? __('Edit Taxonomy Node') : __('Add Taxonomy Node') }}</h1></div>
            <div class="section-body">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h4>{{ $parent ? __('Parent: :name', ['name' => $parent->name]) : __('Education Level') }}</h4>
                        <a href="{{ request('profile') ? route('admin.taxonomy.profiles.show', request('profile')) : route('admin.taxonomy.index') }}" class="btn btn-primary"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ $editing ? route('admin.taxonomy.nodes.update', $node) : route('admin.taxonomy.nodes.store') }}">
                            @csrf
                            @if ($editing) @method('PUT') @endif
                            <input type="hidden" name="parent_id" value="{{ $parent?->id }}">
                            <input type="hidden" name="return_profile" value="{{ request('profile') }}">
                            @if ($parent)
                                <input type="hidden" name="taxonomy_profile" value="{{ old('taxonomy_profile', $node->taxonomy_profile ?? $parent->taxonomy_profile) }}">
                            @endif
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>{{ count($allowedTypes) === 1 ? ($profileConfig['labels'][$allowedTypes[0]] ?? __('Name')) : __('Name') }} *</label>
                                    <input name="name" id="taxonomy-name" class="form-control" value="{{ old('name', $node->name ?? '') }}" required>
                                    @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                                @if (!$parent)
                                    <div class="form-group col-md-6">
                                        <label>{{ __('Taxonomy Profile') }}</label>
                                        <select name="taxonomy_profile" class="form-control">
                                            <option value="">{{ __('School / standard hierarchy') }}</option>
                                            @foreach(config('higher_education_taxonomy.profiles', []) as $profileSlug => $profileConfig)
                                                <option value="{{ $profileSlug }}" @selected(old('taxonomy_profile', $node->taxonomy_profile ?? '') === $profileSlug)>{{ $profileConfig['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                @if (($node->node_type ?? null) === 'programme' || in_array('programme', $allowedTypes, true))
                                    <div class="form-group col-md-6">
                                        <label>{{ __('Qualification Level') }}</label>
                                        <input name="taxonomy_metadata[qualification_level]" class="form-control" value="{{ old('taxonomy_metadata.qualification_level', data_get($node->taxonomy_metadata ?? [], 'qualification_level')) }}" placeholder="{{ __('e.g. Level 6') }}">
                                    </div>
                                @endif
                                <div class="form-group col-md-6">
                                    <label>{{ __('Slug') }} *</label>
                                    <input name="slug" id="taxonomy-slug" class="form-control" value="{{ old('slug', $node->slug ?? '') }}" required>
                                    @error('slug')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('Node Type') }} *</label>
                                    <select name="node_type" class="form-control" required>
                                        @foreach ($allowedTypes as $type)
                                            <option value="{{ $type }}" @selected(old('node_type', $node->node_type ?? '') === $type)>{{ $profileConfig['labels'][$type] ?? ucwords(str_replace('_', ' ', $type)) }}</option>
                                        @endforeach
                                    </select>
                                    @error('node_type')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('Display Order') }} *</label>
                                    <input type="number" min="0" max="65535" name="order" class="form-control" value="{{ old('order', $node->order ?? 0) }}" required>
                                    @error('order')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <button class="btn btn-success" type="submit"><i class="fas fa-save"></i> {{ __('Save Taxonomy Node') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('js')
    <script>
        const nameInput = document.getElementById('taxonomy-name');
        const slugInput = document.getElementById('taxonomy-slug');
        let slugEdited = {{ $editing ? 'true' : 'false' }};
        slugInput.addEventListener('input', () => slugEdited = true);
        nameInput.addEventListener('input', function () {
            if (!slugEdited) slugInput.value = this.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        });
    </script>
@endpush
