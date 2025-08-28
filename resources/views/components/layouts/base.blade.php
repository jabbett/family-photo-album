<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $htmlClass ?? 'dark' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="{{ $bodyClass ?? 'min-h-screen bg-white dark:bg-zinc-800' }}">
        {{ $slot }}

        @stack('scripts')
    </body>
</html>


