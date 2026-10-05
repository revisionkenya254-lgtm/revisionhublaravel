@extends('admin.master_layout')
@section('title')
    <title>{{ __('SMS Template') }}</title>
@endsection
@section('admin-content')
    <!-- Main Content -->
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ __('SMS Template') }}</h1>
            </div>

            @if (isset($variables) && count($variables ?? 0) > 0)
                <div class="section-body">
                    <div class="row mt-4">
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <th>{{ __('Variable') }}</th>
                                            <th>{{ __('Meaning') }}</th>
                                        </thead>
                                        <tbody>
                                            @foreach ($variables as $variable)
                                                @php
                                                    $placeholder = '{{ ' . $variable . ' }}';
                                                    $name = (string) str($variable)->replace('_', ' ')->title();
                                                @endphp
                                                <tr>
                                                    <td>{{ (string) str($placeholder)->replace(' ', '') ?? '' }}</td>
                                                    <td>{{ __($name) ?? '' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('admin.update-sms-template', $template->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-group">
                                        <label for="">{{ __('Message') }} <span class="text-danger">*</span></label>
                                        <textarea name="message" cols="30" rows="10" class="form-control summernote">{!! $template->message !!}</textarea>
                                    </div>
                                    <button class="btn btn-success" type="submit">{{ __('Update') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>
@endsection
