<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    {{-- Base Meta Tags --}}
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Custom Meta Tags --}}
    @yield('meta_tags')

    {{-- Title --}}
    <title>
        @yield('title_prefix', config('adminlte.title_prefix', ''))
        @yield('title', config('adminlte.title', 'AdminLTE 3'))
        @yield('title_postfix', config('adminlte.title_postfix', ''))
    </title>

    {{-- PortoAdmin --}}
    <!-- Vendor CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/vendor/bootstrap/css/bootstrap.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/vendor/animate/animate.css') }}">

    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/vendor/font-awesome/css/font-awesome.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/vendor/magnific-popup/magnific-popup.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}" />

    <!-- Specific Page Vendor CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/vendor/jquery-ui/jquery-ui.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/vendor/jquery-ui/jquery-ui.theme.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/vendor/bootstrap-multiselect/bootstrap-multiselect.css') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/vendor/morris/morris.css') }}" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/css/theme.css') }}" />

    <!-- Skin CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/css/skins/default.css') }}" />

    <!-- Theme Custom CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/porto-admin/css/custom.css') }}">

    <!-- Head Libs -->
    <script src="{{ asset('vendor/porto-admin/vendor/modernizr/modernizr.js') }}"></script>

    {{-- Custom stylesheets (pre AdminLTE) --}}
    @yield('adminlte_css_pre')

    {{-- Custom Stylesheets (post AdminLTE) --}}
    @yield('adminlte_css')


</head>

<body class="@yield('classes_body')" @yield('body_data')>

    {{-- Body Content --}}
    @yield('body')

   <!-- Vendor -->
        <script src="{{ asset('vendor/porto-admin/vendor/jquery/jquery.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jquery-browser-mobile/jquery.browser.mobile.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/popper/umd/popper.min.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/bootstrap/js/bootstrap.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/common/common.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/nanoscroller/nanoscroller.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/magnific-popup/jquery.magnific-popup.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jquery-placeholder/jquery-placeholder.js') }}"></script>
        
        <!-- Specific Page Vendor -->
        <script src="{{ asset('vendor/porto-admin/vendor/jquery-ui/jquery-ui.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jqueryui-touch-punch/jqueryui-touch-punch.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jquery-appear/jquery-appear.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/bootstrap-multiselect/bootstrap-multiselect.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jquery.easy-pie-chart/jquery.easy-pie-chart.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/flot/jquery.flot.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/flot.tooltip/flot.tooltip.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/flot/jquery.flot.pie.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/flot/jquery.flot.categories.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/flot/jquery.flot.resize.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jquery-sparkline/jquery-sparkline.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/raphael/raphael.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/morris/morris.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/gauge/gauge.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/snap.svg/snap.svg.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/liquid-meter/liquid.meter.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jqvmap/jquery.vmap.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jqvmap/data/jquery.vmap.sampledata.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jqvmap/maps/jquery.vmap.world.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jqvmap/maps/continents/jquery.vmap.africa.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jqvmap/maps/continents/jquery.vmap.asia.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jqvmap/maps/continents/jquery.vmap.australia.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jqvmap/maps/continents/jquery.vmap.europe.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jqvmap/maps/continents/jquery.vmap.north-america.js') }}"></script>
        <script src="{{ asset('vendor/porto-admin/vendor/jqvmap/maps/continents/jquery.vmap.south-america.js') }}"></script>
        
        <!-- Theme Base, Components and Settings -->
        <script src="{{ asset('vendor/porto-admin/js/theme.js') }}"></script>
        
        <!-- Theme Custom -->
        <script src="{{ asset('vendor/porto-admin/js/custom.js') }}"></script>
        
        <!-- Theme Initialization Files -->
        <script src="{{ asset('vendor/porto-admin/js/theme.init.js') }}"></script>


    {{-- Custom Scripts --}}
    @yield('adminlte_js')

</body>

</html>
