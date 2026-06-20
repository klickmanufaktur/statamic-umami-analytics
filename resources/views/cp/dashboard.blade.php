@extends('statamic::layout')

@section('title', $title)

@section('content')
    <umami-analytics-dashboard
        title="{{ $title }}"
        overview-url="{{ $overviewUrl }}"
        umami-url="{{ $umamiUrl }}"
        :periods='@json($periods)'
        :initial-days='@json($defaultPeriod)'
        :configured='@json($configured)'
        :missing='@json($missing)'
    ></umami-analytics-dashboard>
@endsection
