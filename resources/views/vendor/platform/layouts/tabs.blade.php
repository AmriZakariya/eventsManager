@php
    $formsForActiveTab = $manyForms instanceof \Illuminate\Support\Collection ? $manyForms->all() : (array) $manyForms;
    $resolvedActiveTab = $activeTab ?? array_key_first($formsForActiveTab);
@endphp

<div
    class="mb-3"
    data-controller="tabs"
    data-tabs-slug="{{$templateSlug}}"
    data-tabs-active-tab="{{$resolvedActiveTab}}"
>
    <nav class="d-flex justify-content-center text-nowrap mb-3">
        <div class="bg-body-tertiary rounded overflow-hidden">
            <ul class="nav nav-pills nav-justified d-inline-flex mx-auto px-3 py-2 nav-scroll-bar gap-2" role="tablist">
                @foreach($manyForms as $name => $tab)
                    <li class="nav-item" role="presentation">
                        <a
                            @class([
                                'nav-link',
                                'active' => $resolvedActiveTab === $name
                            ])
                            data-action="tabs#setActiveTab"
                            href="#tab-{{sha1($templateSlug.$name)}}"
                            data-bs-target="#tab-{{sha1($templateSlug.$name)}}"
                            id="button-tab-{{sha1($templateSlug.$name)}}"
                            aria-selected="false"
                            role="tab"
                            data-bs-toggle="tab">
                            {!! $name !!}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>


    <section class="tab-content">
        @foreach($manyForms as $name => $forms)
            <div role="tabpanel"
                 id="tab-{{sha1($templateSlug.$name)}}"
                 @class([
                    'tab-pane',
                    'active' => $resolvedActiveTab === $name
                 ])
            >
                @foreach($forms as $form)
                    {!! $form !!}
                @endforeach
            </div>
        @endforeach
    </section>
</div>
