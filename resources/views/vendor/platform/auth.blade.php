@extends('platform::app')

@section('body')
    <div class="min-vh-100 d-flex flex-column justify-content-center" style="background-color: #f8fafc;">
        <div class="container-md">

            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-12 col-md-8 col-lg-5 col-xxl-4 px-md-4 py-5">

                    <div class="d-flex justify-content-center mb-4">
                        <a class="text-decoration-none" href="{{ Dashboard::prefix() }}">
                            @includeFirst([config('platform.template.header'), 'platform::header'])
                        </a>
                    </div>

                    <div class="bg-white rounded-4 shadow-sm p-4 p-sm-5 border" style="border-color: #e2e8f0 !important;">
                        @yield('content')
                    </div>

                    <div class="mt-4">
                        @includeFirst([config('platform.template.footer'), 'platform::footer'])
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection
