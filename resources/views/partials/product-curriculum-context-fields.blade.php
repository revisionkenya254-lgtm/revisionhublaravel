@php
    $curriculumOptions = $curriculumOptions ?? ['profiles' => [], 'assignments' => [], 'units' => [], 'selected' => []];
    $curriculumAdmin = $curriculumAdmin ?? false;
    $selectedProfile = (string) ($curriculumOptions['selected']['profile'] ?? '');
    $selectedAssignment = (string) ($curriculumOptions['selected']['assignment_id'] ?? '');
    $selectedUnit = (string) ($curriculumOptions['selected']['unit_id'] ?? '');
@endphp

@if (!empty($curriculumOptions['profiles']))
    <div class="curriculum-context-panel" data-curriculum-context style="grid-column:1/-1;border:1px solid #dbe4ee;border-radius:12px;padding:18px;background:#f8fafc;margin:10px 0;">
        <div style="margin-bottom:14px;">
            <strong>{{ __('Higher Education Classification') }}</strong>
            <p style="margin:4px 0 0;color:#64748b;">{{ __('Use this for CDACC, TVET, Certificate, Diploma, Undergraduate/University and Professional products. The selected unit is linked by its stable taxonomy ID.') }}</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;">
            <div class="form-grp paper-field note-field">
                <label for="curriculum_profile">{{ __('Education Architecture') }}</label>
                <select id="curriculum_profile" name="curriculum_profile" class="form-control form-select">
                    <option value="">{{ __('School / general product') }}</option>
                    @foreach ($curriculumOptions['profiles'] as $profile)
                        <option value="{{ $profile['key'] }}" data-accent="{{ $profile['accent'] }}" @selected($selectedProfile === $profile['key'])>{{ $profile['label'] }}</option>
                    @endforeach
                </select>
                @error('curriculum_profile')<small class="text-danger paper-error note-error">{{ $message }}</small>@enderror
            </div>

            @if ($curriculumAdmin)
                <div class="form-grp paper-field note-field">
                    <label for="curriculum_unit_id">{{ __('Shared Canonical Unit') }}</label>
                    <select id="curriculum_unit_id" name="curriculum_unit_id" class="form-control form-select">
                        <option value="">{{ __('Programme-specific (recommended)') }}</option>
                        @foreach ($curriculumOptions['units'] as $unit)
                            <option value="{{ $unit['id'] }}" data-profile="{{ $unit['profile'] }}" @selected($selectedUnit === (string) $unit['id'])>{{ $unit['label'] }}</option>
                        @endforeach
                    </select>
                    <small style="color:#64748b;">{{ __('Admin-only: choose this without a programme assignment to publish the same unit resource under all assigned programmes.') }}</small>
                </div>
            @else
                <input type="hidden" id="curriculum_unit_id" name="curriculum_unit_id" value="{{ $selectedUnit }}">
            @endif

            <div class="form-grp paper-field note-field" style="grid-column:1/-1;">
                <label for="programme_unit_assignment_id">{{ __('Programme Unit') }}</label>
                <select id="programme_unit_assignment_id" name="programme_unit_assignment_id" class="form-control form-select">
                    <option value="">{{ $curriculumAdmin ? __('Use shared canonical unit / not classified') : __('Select programme, period, group and unit') }}</option>
                    @foreach ($curriculumOptions['assignments'] as $assignment)
                        <option value="{{ $assignment['id'] }}" data-profile="{{ $assignment['profile'] }}" data-unit-id="{{ $assignment['unit_id'] }}" @selected($selectedAssignment === (string) $assignment['id'])>{{ $assignment['label'] }}</option>
                    @endforeach
                </select>
                @error('programme_unit_assignment_id')<small class="text-danger paper-error note-error">{{ $message }}</small>@enderror
            </div>

            <div class="form-grp paper-field note-field">
                <label for="assessment_series">{{ __('Assessment Series') }}</label>
                <input id="assessment_series" name="assessment_series" class="form-control" value="{{ old('assessment_series', $curriculumOptions['selected']['assessment_series'] ?? '') }}" placeholder="{{ __('e.g. July or November') }}">
            </div>
            <div class="form-grp paper-field note-field">
                <label for="paper_number">{{ __('Paper / Variant') }}</label>
                <input id="paper_number" name="paper_number" class="form-control" value="{{ old('paper_number', $curriculumOptions['selected']['paper_number'] ?? '') }}">
            </div>
        </div>
    </div>

    @once
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-curriculum-context]').forEach(panel => {
                    const profile = panel.querySelector('#curriculum_profile');
                    const assignment = panel.querySelector('#programme_unit_assignment_id');
                    const unit = panel.querySelector('#curriculum_unit_id');
                    const filter = (select, selectedProfile) => {
                        if (!select) return;
                        Array.from(select.options).forEach((option, index) => {
                            if (index === 0) return;
                            option.hidden = !!selectedProfile && option.dataset.profile !== selectedProfile;
                        });
                        if (select.selectedOptions[0]?.hidden) select.value = '';
                    };
                    const refresh = () => {
                        filter(assignment, profile?.value || '');
                        if (unit?.tagName === 'SELECT') filter(unit, profile?.value || '');
                    };
                    const educationLevel = panel.closest('form')?.querySelector('#education_level');
                    const syncProfileFromEducation = () => {
                        if (!profile || profile.value || !educationLevel?.value) return;
                        const level = educationLevel.value.toLowerCase();
                        const aliases = {cdacc:['cdacc'],tvet:['tvet'],certificate:['certificate'],diploma:['diploma'],undergraduate:['undergraduate','university'],professional:['professional']};
                        const match = Object.entries(aliases).find(([, values]) => values.some(value => level.includes(value)));
                        if (match) profile.value = match[0];
                    };
                    syncProfileFromEducation();
                    profile?.addEventListener('change', refresh);
                    educationLevel?.addEventListener('change', () => {
                        profile.value = '';
                        syncProfileFromEducation();
                        refresh();
                    });
                    assignment?.addEventListener('change', () => {
                        const selected = assignment.selectedOptions[0];
                        if (selected?.dataset.unitId && unit) unit.value = selected.dataset.unitId;
                    });
                    refresh();
                });
            });
        </script>
    @endonce
@endif
