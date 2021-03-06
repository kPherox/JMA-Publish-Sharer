@extends ('layouts.default')
@section ('title', $eventId)

@section ('content')
<div class="container">
    <div class="row justify-content-center">
        <header class="d-sm-flex col-lg-9 col-xl-8 px-3 mb-3">
            <h5 class="text-truncate d-block my-2 mr-auto px-2">Event ID: @yield('title')</h5>
        </header>

        <main class="col-lg-9 col-xl-8">
            {{ $entries->links('components.index-pagination') }}

            @foreach ($entries as $entry)
            <div class="card">
                <h5 class="card-header bg-transparent d-flex">
                    <span class="text-truncate">
                        {{ $entry->children_kinds->implode(' / ') }}
                    </span>
                </h5>

                <div class="card-body">
                    <h5 class="card-title">{{ $entry->parsed_headline['title'] }}</h5>
                    <h6 class="card-subtitle mb-2 text-muted">@lang('feedtypes.name'): {{ $entry->feed->transed_type }}</h6>
                    <h6 class="card-subtitle mb-2 text-muted">発信時刻: @datetime($entry->updated)</h6>
                    <h6 class="card-subtitle mb-2 text-muted">
                        発表機関:
                            @foreach (preg_split( "/( |　)/", $entry->observatory_name) as $observatoryName)
                                @if ($loop->index > 0) > @endif
                                @if ($__env->yieldContent('observatory') !== $observatoryName)
                                    <a href="{{ route('observatory', ['observatory' => $observatoryName]) }}">{{ $observatoryName }}</a>
                                @else
                                    {{ $observatoryName }}
                                @endif
                            @endforeach
                    </h6>

                    @if ($entry->parsed_headline->has('headline'))
                        @formatToHTML ($entry->parsed_headline['headline'])
                    @endif
                </div>

                <div class="card-footer bg-transparent d-flex border-light">
                    <span class="mr-auto"></span>
                    @if ($entry->entryDetails()->count() === 1)
                    <a class="card-link text-nowrap" href="{{ $entry->entryDetails()->first()->entry_page_url }}">More detail</a>
                    @else
                    <div class="dropdown">
                        <a class="card-link text-nowrap dropdown-toggle" href="#" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
                            More details
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            @foreach ($entry->entryDetails->sortByKind() as $detail)
                            <a class="dropdown-item" href="{{ $detail->entry_page_url }}">
                                {{ $detail->kind_of_info }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach

            {{ $entries->links('components.index-pagination') }}
        </main>
    </div>
</div>
@endsection
