<div class="modal fade" id="createEnrollmentModal" tabindex="-1" role="dialog"
    aria-labelledby="createEnrollmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header pl-4">
                <h5 class="modal-title" id="createEnrollmentModalLabel">
                    {{ __('Manual Course Enrollment') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.manual-enrollment.store') }}" method="POST">
                @csrf
                <div class="modal-body">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Student --}}
                    <div class="form-group">
                        <label for="student_id">{{ __('Student') }} <code>*</code></label>
                        <select name="user_id" id="user_id" class="form-control select2">
                            <option value="">{{ __('Select Student') }}</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected(old('user_id') == $student->id)>
                                    {{ $student->name }} ({{ $student->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Course --}}
                    <div class="form-group">
                        <label for="course_id">{{ __('Course') }} <code>*</code></label>
                        <select name="course_id" id="course_id" class="form-control select2">
                            <option value="">{{ __('Select Course') }}</option>
                            @foreach ($courses as $course)
                                @php $effectivePrice = $course->discount ?: $course->price; @endphp
                                <option value="{{ $course->id }}" data-price="{{ $effectivePrice }}"
                                    @selected(old('course_id') == $course->id)>
                                    {{ $course->title }}
                                    @if ($course->price == 0)
                                        ({{ __('Free') }})
                                    @elseif ($course->discount)
                                        ({{ currency($course->discount) }} <del>{{ currency($course->price) }}</del>)
                                    @else
                                        ({{ currency($course->price) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Enrollment Type --}}
                    <div class="form-group">
                        <label>{{ __('Enrollment Type') }} <code>*</code></label>
                        <div class="d-flex gap-3">
                            <div class="custom-control custom-radio mr-3">
                                <input type="radio" id="type_free" name="type" value="free" class="custom-control-input"
                                    {{ old('type', 'free') === 'free' ? 'checked' : '' }} required>
                                <label class="custom-control-label" for="type_free">
                                    {{ __('Free (No Charge)') }}
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="type_paid" name="type" value="paid" class="custom-control-input"
                                    {{ old('type') === 'paid' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="type_paid">
                                    {{ __('Paid (Manual Payment)') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Paid fields --}}
                    <div id="paid_fields" class="{{ old('type') === 'paid' ? '' : 'd-none' }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user">{{ __('Payment Method') }} <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment_method" class="form-control select2">
                                        <option value="">{{ __('Select Payment Method') }}</option>
                                        @foreach ($activeGateways as $gatewayKey => $gatewayDetails)
                                            <option value="{{ $gatewayKey }}" @selected(old('payment_method') == $gatewayKey)>
                                                {{ $gatewayDetails['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount">{{ __('Amount Paid') }} <code>*</code></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <select name="currency_id" id="currency_id" class="form-control select2">
                                                <option value="">{{ __('Select Currency') }}</option>
                                                @foreach (allCurrencies() as $currency)
                                                    <option value="{{ $currency->id }}" {{ old('currency_id', getSessionCurrency()) == $currency->id ? 'selected' : '' }}>
                                                        {{ $currency->currency_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <input type="number" id="amount" name="amount" class="form-control" min="0"
                                            step="0.01" placeholder="0.00" value="{{ old('amount') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Note --}}
                    <div class="form-group">
                        <label for="note">{{ __('Note') }} <span
                                class="text-muted">({{ __('optional') }})</span></label>
                        <textarea id="note" name="note" class="form-control" rows="2"
                            placeholder="{{ __('Internal note about this enrollment') }}">{{ old('note') }}</textarea>
                    </div>

                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ __('An order record will be created automatically. The student will immediately gain access to the course.') }}
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-check mr-1"></i> {{ __('Enroll Now') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>