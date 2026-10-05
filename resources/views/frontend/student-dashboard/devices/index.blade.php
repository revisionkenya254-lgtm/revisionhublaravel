@extends('frontend.student-dashboard.layouts.master')

@section('dashboard-contents')
    <div class="dashboard__content-wrap">
        <div class="dashboard__content-title d-flex flex-wrap justify-content-between">
            <h4 class="title">{{ __('Active Devices') }} ({{ __('limit') }} {{ config('auth.max_devices') }}) </h4>
            @if (count($devices) > 1)
                <a href="javascript:;" class="delete-item btn btn-primary btn-hight-basic">{{ __('Logout All Device') }}
                    <form action="{{ route('student.devices.destroyAll') }}" method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </a>
            @endif
        </div>
        <div class="row">
            <div class="col-12">
                <div class="dashboard__review-table table-responsive">
                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th>{{ __('No') }}</th>
                                <th>{{ __('IP Address') }}</th>
                                <th>{{ __('Logged in') }}</th>
                                <th>{{ __('Device Type') }}</th>
                                <th>{{ __('User Agent') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>

                            @foreach ($devices as $index => $device)
                                <tr>
                                    <td>{{ ++$index }}</td>
                                    <td>{{ $device->ip_address }}</td>
                                    <td>{{ formattedDateTime($device?->created_at) }}</td>
                                    <td>{{ getDeviceType($device->user_agent) }}</td>
                                    <td>
                                        {{ getBrowserName($device->user_agent) }}
                                        @if (session()->getId() === $device->session_id)
                                            <div class="badge bg-success">{{ __('Current Device') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if (session()->getId() !== $device->session_id)
                                            <a href="javascript:;" class="delete-item text-danger "><i
                                                    class="fas fa-trash-alt"></i>
                                                <form action="{{ route('student.devices.destroy', $device->session_id) }}"
                                                    method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $devices->links() }}
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $(document).on("click", '.delete-item', function(e) {
                e.preventDefault();
                let form = $(this).find('form');
                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: "{{ __('You will not be able to revert this!') }}",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "{{ __('Yes, Logout!') }}",
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
