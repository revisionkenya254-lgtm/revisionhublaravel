@extends('frontend.student-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="dashboard__content-title">
            <h4 class="title">{{ __('Create a Request') }}</h4>
        </div>
        <div class="instructor__profile-form-wrap">
            <form action="{{ route('refund-request.store', $order->invoice_id) }}" method="POST"
                class="instructor__profile-form">
                @csrf
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-grp">
                            <label for="reasone">{{ __('Reason') }} <code>*</code></label>
                            <input id="reasone" name="reasone" />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-grp">
                            <label for="method_id">{{ __('Method') }} <code>*</code></label>
                            <select name="payment_method" id="method_id" class="form-select">
                                <option value="">{{ __('Select') }}</option>
                                @foreach ($withdrawMethods as $method)
                                    <option value="{{ $method?->name }}" data-info="{!! clean($method->description) !!}">
                                        {{ $method?->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12 d-none" id="method_des_box">
                        <div class="form-grp">
                            <div class="alert alert-light mb-0" role="alert" id="method_des">
                                {!! $method?->description !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-grp">
                            <label for="account_information">{{ __('Account Information') }} <code>*</code></label>
                            <textarea id="account_information" name="account_information"></textarea>
                        </div>
                    </div>
                </div>

                <div class="submit-btn mt-25">
                    <button type="submit" class="btn">{{ __('Submit') }}</button>
                </div>
            </form>
        </div>

    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $("#method_id").on('change', function() {
                    var methodId = $(this).val();
                    if (!methodId) {
                        $("#method_des_box").addClass('d-none');
                        return;
                    }
                    // Get the selected option's data-info
                    var description = $(this).find('option:selected').data('info');

                    // Show the description
                    $("#method_des").html(description);
                    $("#method_des_box").removeClass('d-none');
                    submitBtn.attr('disabled', false);
                });
            })
        </script>
    @endpush
@endsection
