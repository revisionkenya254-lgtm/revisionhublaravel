@extends('admin.master_layout')

@section('title')<title>{{ $profileConfig['label'] }} {{ __('Taxonomy') }}</title>@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header d-flex justify-content-between">
            <div><h1>{{ $profileConfig['label'] }} {{ __('Curriculum') }}</h1><small>{{ __('Canonical units with reusable programme assignments') }}</small></div>
            <a href="{{ route('admin.taxonomy.index') }}" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> {{ __('All taxonomy') }}</a>
        </div>
        <div class="section-body">
            <div class="rh-profile-tabs mb-4">
                @foreach (config('higher_education_taxonomy.profiles', []) as $profileSlug => $item)
                    @php($available = \Modules\Course\app\Models\CourseCategory::whereNull('parent_id')->where('taxonomy_profile', $profileSlug)->exists())
                    @if ($available)
                        <a class="rh-profile-tab {{ $profileSlug === $profile ? 'is-active' : '' }}" style="--profile-color: {{ $item['accent'] }}" href="{{ route('admin.taxonomy.profiles.show', $profileSlug) }}">
                            <i class="{{ $item['icon'] }}"></i> {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="row">
                <div class="col-xl-8">
                    <div class="card rh-profile-card" style="--profile-color: {{ $profileConfig['accent'] }}">
                        <div class="card-header"><h4><i class="fas fa-sitemap"></i> {{ __('Programme structure') }}</h4></div>
                        <div class="card-body">
                            <ul class="rh-taxonomy-tree">@include('course::taxonomy._profile-tree-node', ['node' => $tree, 'profile' => $profile])</ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header"><h4>{{ __('Create canonical unit') }}</h4></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.taxonomy.units.store', $profile) }}">@csrf
                                <div class="form-group"><label>{{ __('Unit name') }} *</label><input name="name" class="form-control" required value="{{ old('name') }}"></div>
                                <div class="form-group"><label>{{ __('Official code') }}</label><input name="code" class="form-control" value="{{ old('code') }}"></div>
                                <div class="form-group"><label>{{ __('Curriculum version') }} *</label><input name="curriculum_version" class="form-control" required value="{{ old('curriculum_version', 'current') }}"></div>
                                <div class="form-group"><label>{{ __('Description') }}</label><textarea name="description" class="form-control">{{ old('description') }}</textarea></div>
                                <button class="btn btn-success btn-block"><i class="fas fa-plus"></i> {{ __('Create unit') }}</button>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><h4>{{ __('Assign existing unit') }}</h4></div>
                        <div class="card-body">
                            @if ($programmes->isEmpty())
                                <p class="text-muted">{{ __('Create a department and programme in this profile first.') }}</p>
                            @else
                                <form method="POST" id="unit-assignment-form" data-action-template="{{ route('admin.taxonomy.assignments.store', [$profile, '__PROGRAMME__']) }}">@csrf
                                    <div class="form-group"><label>{{ __('Programme') }} *</label><select id="assignment-programme" class="form-control" required>@foreach($programmes as $programme)<option value="{{ $programme->id }}">{{ $programme->name }}</option>@endforeach</select></div>
                                    <div class="form-group"><label>{{ __('Canonical unit') }} *</label><select name="curriculum_unit_id" class="form-control" required>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }} ({{ __('used by :count', ['count' => $unit->assignments_count]) }})</option>@endforeach</select></div>
                                    <div class="form-group"><label>{{ __('Unit group') }} *</label><select name="group_type" class="form-control"><option value="common">{{ __('Common Units') }}</option><option value="major">{{ __('Major Units') }}</option><option value="elective">{{ __('Elective Units') }}</option></select></div>
                                    <div class="form-group"><label>{{ __('Semester / module') }}</label><input name="semester" class="form-control"></div>
                                    <input type="hidden" name="is_mandatory" value="1"><input type="hidden" name="display_order" value="0">
                                    <button class="btn btn-primary btn-block" @disabled($units->isEmpty())><i class="fas fa-link"></i> {{ __('Assign unit') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mt-4">
                <div class="card-header"><h4><i class="fas fa-book"></i> {{ __('Canonical Unit Library') }}</h4></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Code') }}</th><th>{{ __('Version') }}</th><th>{{ __('Usage') }}</th><th>{{ __('Actions') }}</th></tr></thead>
                        <tbody>
                        @forelse($units as $unit)
                            <tr class="{{ $unit->status ? '' : 'table-secondary' }}">
                                    <td><input form="unit-form-{{ $unit->id }}" class="form-control form-control-sm" name="name" value="{{ $unit->name }}" required><input form="unit-form-{{ $unit->id }}" type="hidden" name="slug" value="{{ $unit->slug }}"></td>
                                    <td><input form="unit-form-{{ $unit->id }}" class="form-control form-control-sm" name="code" value="{{ $unit->code }}"></td>
                                    <td><input form="unit-form-{{ $unit->id }}" class="form-control form-control-sm" name="curriculum_version" value="{{ $unit->curriculum_version }}" required></td>
                                    <td><span class="badge badge-success"><i class="fas fa-link"></i> {{ trans_choice(':count programme|:count programmes', $unit->assignments_count, ['count' => $unit->assignments_count]) }}</span></td>
                                    <td class="text-nowrap"><form id="unit-form-{{ $unit->id }}" method="POST" action="{{ route('admin.taxonomy.units.update', [$profile, $unit]) }}">@csrf @method('PUT')<button class="btn btn-sm btn-warning"><i class="fas fa-save"></i> {{ __('Update') }}</button></form></td>
                            </tr>
                            @if($unit->status)
                                <tr><td colspan="4" class="border-0 p-0"></td><td class="border-0 pt-0"><form method="POST" action="{{ route('admin.taxonomy.units.archive', [$profile, $unit]) }}" class="taxonomy-archive-unit-form">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-archive"></i> {{ __('Archive') }}</button></form></td></tr>
                            @endif
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">{{ __('No canonical units yet.') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('css')
<style>
.rh-profile-tabs{display:flex;gap:.65rem;flex-wrap:wrap}.rh-profile-tab{background:#fff;border:1px solid #e5e7eb;border-radius:10px;color:#374151;padding:.7rem 1rem}.rh-profile-tab.is-active{border-color:var(--profile-color);box-shadow:0 0 0 2px color-mix(in srgb,var(--profile-color) 18%,transparent);color:var(--profile-color);font-weight:700}.rh-profile-card{border-top:4px solid var(--profile-color)}.rh-taxonomy-tree,.rh-taxonomy-tree__children{list-style:none;margin:0;padding-left:0}.rh-taxonomy-tree__children{border-left:2px solid #e5e7eb;margin-left:.8rem;padding-left:1.4rem}.rh-taxonomy-tree__row{align-items:center;display:flex;gap:.55rem;min-height:42px;position:relative}.rh-taxonomy-tree__marker{background:#cbd5e1;border-radius:50%;height:8px;width:8px}.rh-taxonomy-tree__label{font-weight:600}.rh-taxonomy-tree__item--unit .rh-taxonomy-tree__label{font-weight:500}.rh-taxonomy-tree__item--resource .rh-taxonomy-tree__label{font-weight:400;color:#64748b}
</style>
@endpush

@push('js')
<script>
const assignmentForm=document.getElementById('unit-assignment-form');
if(assignmentForm){const sync=()=>assignmentForm.action=assignmentForm.dataset.actionTemplate.replace('__PROGRAMME__',document.getElementById('assignment-programme').value);document.getElementById('assignment-programme').addEventListener('change',sync);sync();}
document.querySelectorAll('.taxonomy-unassign-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(@json(__('Remove this unit from the programme?'))))event.preventDefault();}));
document.querySelectorAll('.taxonomy-archive-unit-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(@json(__('Archive this canonical unit? Existing assignments and resources will be preserved but hidden from the public catalog.'))))event.preventDefault();}));
</script>
@endpush
