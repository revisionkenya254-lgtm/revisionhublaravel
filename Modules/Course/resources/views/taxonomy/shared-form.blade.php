@extends('admin.master_layout')

@php($editing = isset($leaf))
@section('title')<title>{{ $editing ? __('Edit Shared Subject') : __('Add Shared Subject') }}</title>@endsection

@section('admin-content')
    <div class="main-content">
        <section class="section">
            <div class="section-header"><h1>{{ $editing ? __('Edit Shared Subject / Pathway') : __('Add Shared Subject / Pathway') }}</h1></div>
            <div class="section-body">
                <div class="alert alert-info">
                    {{ __('This change applies uniformly to every grade, form, and national assessment under :level.', ['level' => $educationLevel->name]) }}
                </div>
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h4>{{ $educationLevel->name }}</h4>
                        <a href="{{ route('admin.taxonomy.index') }}" class="btn btn-primary"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ $editing ? route('admin.taxonomy.shared.update', [$educationLevel, $slug]) : route('admin.taxonomy.shared.store', $educationLevel) }}">
                            @csrf
                            @if ($editing) @method('PUT') @endif
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>{{ __('Name') }} *</label>
                                    <input name="name" id="taxonomy-name" class="form-control" value="{{ old('name', $leaf->name ?? '') }}" required>
                                    @error('name')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('Slug') }} *</label>
                                    <input name="slug" id="taxonomy-slug" class="form-control" value="{{ old('slug', $leaf->slug ?? '') }}" required>
                                    @error('slug')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('Node Type') }} *</label>
                                    <select name="node_type" class="form-control">
                                        <option value="subject" @selected(old('node_type', $leaf->node_type ?? 'subject') === 'subject')>{{ __('Subject') }}</option>
                                        <option value="pathway" @selected(old('node_type', $leaf->node_type ?? '') === 'pathway')>{{ __('Pathway') }}</option>
                                    </select>
                                    @error('node_type')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label>{{ __('Display Order') }} *</label>
                                    <input type="number" min="0" max="65535" name="order" class="form-control" value="{{ old('order', $leaf->order ?? 0) }}" required>
                                    @error('order')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <button class="btn btn-success" type="submit"><i class="fas fa-save"></i> {{ __('Apply Everywhere') }}</button>
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
