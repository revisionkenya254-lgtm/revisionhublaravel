@php
    Vite::useBuildDirectory('build-pagebuilder')->useManifestFilename('.vite/manifest.json');
@endphp
<!DOCTYPE html>
<html>

<head>
    <title>{{ __('Builder') }} - {{ $page->name }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="url" content="{{ url()->full() }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset($setting->favicon) }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/fontawesome-all.min.css') }}">
    @vite('resources/assets/css/app.css')
</head>

<body>
    <div id="gjs" style="height:100vh;"></div>
    <script>
        window.pageJson = @json(json_decode($page_json) ?? []);
        window.baseUrl = "{{ url('/') }}";
        window.basicErrorMessage = "{{ __('Something went wrong') }}";
        window.textDirection = "{{ $language?->direction }}";
        window.themeRootStyle = `
            <style>
                :root {
                    --tg-theme-primary: {{ $setting->primary_color }};
                    --tg-theme-secondary: {{ $setting->secondary_color }};
                    --tg-common-color-blue: {{ $setting->common_color_one }};
                    --tg-common-color-blue-2: {{ $setting->common_color_two }};
                    --tg-common-color-dark: {{ $setting->common_color_three }};
                    --tg-common-color-black: {{ $setting->common_color_four }};
                    --tg-common-color-dark-2: {{ $setting->common_color_five }};
                }
                .editor-preview-helper{border: 1px dashed #7F7E97;padding:10px;}
            </style>
        `;
    </script>
    @vite('resources/assets/js/app.js')
</body>

</html>