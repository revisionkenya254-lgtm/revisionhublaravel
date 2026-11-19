@extends('admin.master_layout')

@section('title')<title>{{ $profileConfig['label'] }} {{ __('Taxonomy') }}</title>@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header d-flex justify-content-between">
            <div><h1>{{ $profileConfig['label'] }} {{ __('Curriculum') }}</h1><small>{{ __('Taxonomy-driven programme structures and reusable canonical units') }}</small></div>
            <a href="{{ route('admin.taxonomy.index') }}" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> {{ __('All taxonomy') }}</a>
        </div>
        <div class="section-body">
            <div class="rh-profile-tabs mb-4">
                @foreach (config('higher_education_taxonomy.profiles', []) as $profileSlug => $item)
                    @php($available = \Modules\Course\app\Models\CourseCategory::whereNull('parent_id')->where('taxonomy_profile', $profileSlug)->exists())
                    @if ($available)<a class="rh-profile-tab {{ $profileSlug === $profile ? 'is-active' : '' }}" style="--profile-color: {{ $item['accent'] }}" href="{{ route('admin.taxonomy.profiles.show', $profileSlug) }}"><i class="{{ $item['icon'] }}"></i> {{ $item['label'] }}</a>@endif
                @endforeach
            </div>
            <div class="alert alert-light border mb-4">
                <strong>{{ __('Profile architecture:') }}</strong> {{ $profileConfig['label'] }}
                @foreach(($profileConfig['hierarchy'] ?? ['awarding_body', 'department', 'programme']) as $nodeType)<i class="fas fa-angle-right mx-1"></i> {{ $profileConfig['labels'][$nodeType] ?? ucwords(str_replace('_', ' ', $nodeType)) }}@endforeach
                @foreach(($profileConfig['period_fields'] ?? []) as $periodField)<i class="fas fa-angle-right mx-1"></i> {{ ucwords(str_replace('_', ' ', $periodField)) }}@endforeach
                <i class="fas fa-angle-right mx-1"></i> {{ __('Unit Group') }} <i class="fas fa-angle-right mx-1"></i> {{ $profileConfig['labels']['unit'] ?? __('Unit') }} <i class="fas fa-angle-right mx-1"></i> {{ __('Resources') }}
            </div>

            <div class="row">
                <div class="col-xl-8"><div class="card rh-profile-card" style="--profile-color: {{ $profileConfig['accent'] }}"><div class="card-header"><h4><i class="fas fa-sitemap"></i> {{ __('Programme structure') }}</h4></div><div class="card-body"><ul class="rh-taxonomy-tree">@include('course::taxonomy._profile-tree-node', ['node' => $tree, 'profile' => $profile])</ul></div></div></div>
                <div class="col-xl-4">
                    <div class="card"><div class="card-header"><h4>{{ __('Create canonical unit') }}</h4></div><div class="card-body">
                        <form method="POST" action="{{ route('admin.taxonomy.units.store', $profile) }}">@csrf
                            <div class="form-group"><label>{{ $profileConfig['labels']['awarding_body'] ?? __('Curriculum Owner') }} *</label><select name="awarding_body_id" class="form-control" required>@foreach($awardingBodies as $body)<option value="{{ $body->id }}">{{ $body->id === $root->id ? __('Shared across :profile', ['profile' => $profileConfig['label']]) : $body->name }}</option>@endforeach</select></div>
                            <div class="form-group"><label>{{ $profileConfig['labels']['unit'] ?? __('Unit') }} {{ __('name') }} *</label><input name="name" class="form-control" required value="{{ old('name') }}"></div>
                            <div class="form-group"><label>{{ __('Official code') }}</label><input name="code" class="form-control" value="{{ old('code') }}"></div>
                            <div class="form-group"><label>{{ __('Curriculum version') }} *</label><input name="curriculum_version" class="form-control" required value="{{ old('curriculum_version', 'current') }}"></div>
                            <div class="form-group"><label>{{ __('Description') }}</label><textarea name="description" class="form-control">{{ old('description') }}</textarea></div>
                            <button class="btn btn-success btn-block"><i class="fas fa-plus"></i> {{ __('Create unit') }}</button>
                        </form>
                    </div></div>
                    <div class="card"><div class="card-header d-flex justify-content-between"><h4>{{ $editingAssignment ? __('Edit programme unit') : __('Assign existing unit') }}</h4>@if($editingAssignment)<a href="{{ route('admin.taxonomy.profiles.show', $profile) }}" class="btn btn-sm btn-outline-secondary">{{ __('Cancel') }}</a>@endif</div><div class="card-body">
                        @if ($programmes->isEmpty())<p class="text-muted">{{ __('Create the required programme or qualification structure first.') }}</p>@else
                            <form method="POST" id="unit-assignment-form" action="{{ $editingAssignment ? route('admin.taxonomy.assignments.update', [$profile, $editingAssignment]) : '' }}" data-action-template="{{ route('admin.taxonomy.assignments.store', [$profile, '__PROGRAMME__']) }}" data-editing="{{ $editingAssignment ? '1' : '0' }}">@csrf
                                @if($editingAssignment) @method('PUT') @endif
                                <div class="form-group"><label>{{ $profileConfig['labels']['programme'] ?? __('Programme') }} *</label><select id="assignment-programme" name="programme_node_id" class="form-control" required>@foreach($programmes as $programme)<option value="{{ $programme->id }}" data-owner-id="{{ $programmeOwnerIds[$programme->id] ?? $root->id }}" @selected((int)old('programme_node_id', $editingAssignment?->programme_node_id) === $programme->id)>{{ $programme->name }}</option>@endforeach</select></div>
                                <div class="form-group"><label>{{ $profileConfig['labels']['unit'] ?? __('Canonical unit') }} *</label><select id="assignment-unit" name="curriculum_unit_id" class="form-control" required>@foreach($units->filter(fn($item) => $item->status || $editingAssignment?->curriculum_unit_id === $item->id) as $unit)<option value="{{ $unit->id }}" data-owner-id="{{ $unit->awarding_body_id }}" @selected((int)old('curriculum_unit_id', $editingAssignment?->curriculum_unit_id) === $unit->id)>{{ $unit->name }} · {{ $unit->awardingBody?->name }} ({{ __('used by :count', ['count' => $unit->assignments_count]) }})</option>@endforeach</select></div>
                                <div class="form-group"><label>{{ __('Unit group') }} *</label><select name="group_type" class="form-control">@foreach(($profileConfig['group_types'] ?? []) as $groupValue => $groupLabel)<option value="{{ $groupValue }}" @selected(old('group_type', $editingAssignment?->group_type) === $groupValue)>{{ $groupLabel }}</option>@endforeach</select></div>
                                @foreach(($profileConfig['period_fields'] ?? []) as $periodField)<div class="form-group"><label>{{ ucwords(str_replace('_', ' ', $periodField)) }}</label><input name="{{ $periodField }}" class="form-control" value="{{ old($periodField, $editingAssignment?->{$periodField}) }}" placeholder="{{ match($periodField) { 'study_year' => __('e.g. Year 1'), 'semester' => __('e.g. Semester 1'), 'module' => __('e.g. Module 1'), 'stage' => __('e.g. Intermediate Level'), 'section' => __('e.g. Section 2'), default => '' } }}"></div>@endforeach
                                <div class="row"><div class="form-group col-6"><label>{{ __('Display order') }}</label><input type="number" min="0" max="65535" name="display_order" class="form-control" value="{{ old('display_order', $editingAssignment?->display_order ?? 0) }}"></div><div class="form-group col-6"><label>{{ __('Credit hours') }}</label><input type="number" min="0" max="999" step="0.01" name="credit_hours" class="form-control" value="{{ old('credit_hours', $editingAssignment?->credit_hours) }}"></div></div>
                                <input type="hidden" name="is_mandatory" value="0"><div class="form-check mb-3"><input type="checkbox" class="form-check-input" id="assignment-mandatory" name="is_mandatory" value="1" @checked((bool)old('is_mandatory', $editingAssignment?->is_mandatory ?? true))><label class="form-check-label" for="assignment-mandatory">{{ __('Mandatory unit') }}</label></div>
                                <button class="btn btn-primary btn-block" @disabled($units->where('status', true)->isEmpty())><i class="fas fa-link"></i> {{ $editingAssignment ? __('Update assignment') : __('Assign unit') }}</button>
                            </form>
                        @endif
                    </div></div>
                </div>
            </div>

            <div class="card mt-4"><div class="card-header"><h4><i class="fas fa-book"></i> {{ __('Canonical Unit Library') }}</h4></div><div class="card-body table-responsive"><table class="table table-sm table-bordered">
                <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Owner') }}</th><th>{{ __('Code') }}</th><th>{{ __('Version') }}</th><th>{{ __('Usage') }}</th><th>{{ __('Actions') }}</th></tr></thead>
                <tbody>@forelse($units as $unit)<tr class="{{ $unit->status ? '' : 'table-secondary' }}">
                    <td><input form="unit-form-{{ $unit->id }}" class="form-control form-control-sm" name="name" value="{{ $unit->name }}" required><input form="unit-form-{{ $unit->id }}" type="hidden" name="slug" value="{{ $unit->slug }}"></td><td>{{ $unit->awardingBody?->name }}</td><td><input form="unit-form-{{ $unit->id }}" class="form-control form-control-sm" name="code" value="{{ $unit->code }}"></td><td><input form="unit-form-{{ $unit->id }}" class="form-control form-control-sm" name="curriculum_version" value="{{ $unit->curriculum_version }}" required></td><td><span class="badge badge-success"><i class="fas fa-link"></i> {{ trans_choice(':count programme|:count programmes', $unit->assignments_count, ['count' => $unit->assignments_count]) }}</span></td>
                    <td class="text-nowrap"><form id="unit-form-{{ $unit->id }}" method="POST" action="{{ route('admin.taxonomy.units.update', [$profile, $unit]) }}" class="d-inline">@csrf @method('PUT')<button class="btn btn-sm btn-warning" @disabled(!$unit->status)><i class="fas fa-save"></i> {{ __('Update') }}</button></form> @if($unit->status)<form method="POST" action="{{ route('admin.taxonomy.units.archive', [$profile, $unit]) }}" class="taxonomy-archive-unit-form d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-archive"></i> {{ __('Archive') }}</button></form>@else<form method="POST" action="{{ route('admin.taxonomy.units.restore', [$profile, $unit]) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success"><i class="fas fa-undo"></i> {{ __('Restore') }}</button></form>@endif</td>
                </tr>@empty<tr><td colspan="6" class="text-center text-muted">{{ __('No canonical units yet.') }}</td></tr>@endforelse</tbody>
            </table></div></div>
            @if($archivedNodes->isNotEmpty())
                <div class="card mt-4"><div class="card-header"><h4><i class="fas fa-archive"></i> {{ __('Archived profile nodes') }}</h4></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Parent') }}</th><th></th></tr></thead><tbody>
                    @foreach($archivedNodes as $archived)<tr><td>{{ $archived->name }}</td><td>{{ $profileConfig['labels'][$archived->node_type] ?? ucwords(str_replace('_', ' ', $archived->node_type)) }}</td><td>{{ $archived->parentCategory?->name }}</td><td><form method="POST" action="{{ route('admin.taxonomy.nodes.restore', $archived->id) }}">@csrf<input type="hidden" name="return_profile" value="{{ $profile }}"><button class="btn btn-sm btn-outline-success"><i class="fas fa-undo"></i> {{ __('Restore') }}</button></form></td></tr>@endforeach
                </tbody></table></div></div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('css')
<style>.rh-profile-tabs{display:flex;gap:.65rem;flex-wrap:wrap}.rh-profile-tab{background:#fff;border:1px solid #e5e7eb;border-radius:10px;color:#374151;padding:.7rem 1rem}.rh-profile-tab.is-active{border-color:var(--profile-color);box-shadow:0 0 0 2px color-mix(in srgb,var(--profile-color) 18%,transparent);color:var(--profile-color);font-weight:700}.rh-profile-card{border-top:4px solid var(--profile-color)}.rh-taxonomy-tree,.rh-taxonomy-tree__children{list-style:none;margin:0;padding-left:0}.rh-taxonomy-tree__children{border-left:2px solid #e5e7eb;margin-left:.8rem;padding-left:1.4rem}.rh-taxonomy-tree__row{align-items:center;display:flex;gap:.55rem;min-height:42px;position:relative}.rh-taxonomy-tree__marker{background:#cbd5e1;border-radius:50%;height:8px;width:8px}.rh-taxonomy-tree__label{font-weight:600}.rh-taxonomy-tree__item--unit .rh-taxonomy-tree__label{font-weight:500}.rh-taxonomy-tree__item--resource .rh-taxonomy-tree__label{font-weight:400;color:#64748b}</style>
@endpush

@push('js')
<script>
const assignmentForm=document.getElementById('unit-assignment-form');
if(assignmentForm){const programme=document.getElementById('assignment-programme');const unit=document.getElementById('assignment-unit');const sync=()=>{if(assignmentForm.dataset.editing!=='1')assignmentForm.action=assignmentForm.dataset.actionTemplate.replace('__PROGRAMME__',programme.value);const owner=programme.options[programme.selectedIndex]?.dataset.ownerId;Array.from(unit.options).forEach(option=>{option.hidden=option.dataset.ownerId!==owner&&option.dataset.ownerId!==@json((string)$root->id);option.disabled=option.hidden;});if(unit.options[unit.selectedIndex]?.disabled){unit.value=Array.from(unit.options).find(option=>!option.disabled)?.value||'';}};programme.addEventListener('change',sync);sync();}
document.querySelectorAll('.taxonomy-unassign-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(@json(__('Deactivate this unit assignment?'))))event.preventDefault();}));
document.querySelectorAll('.taxonomy-archive-unit-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(@json(__('Archive this canonical unit? Existing assignments and resources will be preserved but hidden from product forms.'))))event.preventDefault();}));
document.querySelectorAll('.taxonomy-archive-node-form').forEach(form=>form.addEventListener('submit',event=>{if(!confirm(@json(__('Archive this taxonomy branch?'))))event.preventDefault();}));
</script>
@endpush
