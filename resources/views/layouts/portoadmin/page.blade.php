@extends('layouts.portoadmin.master')

@section('adminlte_css')
    @stack('css')
    @yield('css')
@stop


@section('body')
    @include('layouts.portoadmin.partials.header')

    <div class="inner-wrapper">
        @include('layouts.portoadmin.partials.sidebar-left')
        
        <section role="main" class="content-body">
            <header class="page-header">
                @yield('page_title')
                        
                <div class="right-wrapper text-right">
                    <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fa fa-chevron-left"></i></a>
                </div>
            </header>

            {{-- Start Page --}}
            <div class="row">
                @yield('content_header')
            </div>
            <div class="row">
                @yield('content')
            </div>
        </section>
    </div>

    @include('layouts.portoadmin.partials.sidebar-right')

@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop
