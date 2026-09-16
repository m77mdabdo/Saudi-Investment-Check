@props(['code' => '500', 'title' => null, 'message' => null])

<x-layouts.public :seo-title="$title" robots="noindex,nofollow">
    <section class="mx-auto flex w-full max-w-2xl flex-col items-center px-4 py-16 text-center sm:px-5 sm:py-24">
        <p class="text-6xl font-black tracking-tight text-gold-300 sm:text-7xl" dir="ltr">{{ $code }}</p>
        <h1 class="cm-h3 mt-4 font-black">{{ $title }}</h1>
        <p class="mt-3 max-w-md text-base leading-relaxed text-cream/70">{{ $message }}</p>

        <div class="mt-8 flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
            <a href="{{ lroute('landing') }}" class="cm-btn cm-btn-primary">{{ __('errors.back_home') }}</a>
            <a href="{{ lroute('quiz') }}" class="cm-btn cm-btn-ghost">{{ __('errors.start_check') }}</a>
        </div>
    </section>
</x-layouts.public>
