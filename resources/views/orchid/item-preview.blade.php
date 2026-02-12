<div class="p-4 border rounded bg-light">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">📱 Preview</h6>
                </div>
                <div class="card-body text-center">
                    @if($widgetType === 'menu_grid')
                        <div class="d-flex flex-column align-items-center p-4">
                            @if($item->icon)
                                <div class="bg-primary rounded-circle p-3 mb-3 shadow-sm">
                                    <i class="material-icons text-white" style="font-size: 36px;">
                                        {{ $item->icon }}
                                    </i>
                                </div>
                            @else
                                <div class="bg-light rounded-circle p-3 mb-3 border">
                                    <i class="material-icons text-muted" style="font-size: 36px;">
                                        help_outline
                                    </i>
                                </div>
                            @endif
                            @if($item->title)
                                <h6 class="fw-bold mb-2">{{ $item->title }}</h6>
                            @endif
                            @if($item->subtitle)
                                <small class="text-muted">{{ $item->subtitle }}</small>
                            @endif
                        </div>
                    @elseif($widgetType === 'slider')
                        <div class="border rounded overflow-hidden shadow-sm">
                            @if($item->image)
                                <div class="bg-light" style="height: 150px; display: flex; align-items: center; justify-content: center;">
                                    <div class="text-center">
                                        <i class="bs.image text-muted" style="font-size: 48px;"></i>
                                        <div class="mt-2 text-muted small">Image preview</div>
                                    </div>
                                </div>
                            @else
                                <div class="bg-warning bg-opacity-10" style="height: 150px; display: flex; align-items: center; justify-content: center;">
                                    <div class="text-center">
                                        <i class="bs.image text-warning" style="font-size: 48px;"></i>
                                        <div class="mt-2 text-warning">Image required</div>
                                    </div>
                                </div>
                            @endif
                            <div class="p-3">
                                @if($item->title)
                                    <h6 class="fw-bold mb-2">{{ $item->title }}</h6>
                                @endif
                                @if($item->subtitle)
                                    <p class="small text-muted mb-0">{{ $item->subtitle }}</p>
                                @endif
                            </div>
                        </div>
                    @elseif($widgetType === 'logo_cloud')
                        <div class="d-flex align-items-center justify-content-center p-4" style="height: 200px;">
                            @if($item->image)
                                <div class="bg-white p-4 rounded border shadow-sm" style="max-width: 150px;">
                                    <div class="text-center">
                                        <i class="bs.building text-primary" style="font-size: 36px;"></i>
                                        <div class="mt-2 fw-bold small">Logo</div>
                                    </div>
                                </div>
                            @else
                                <div class="bg-warning bg-opacity-10 p-4 rounded border" style="max-width: 150px;">
                                    <div class="text-center text-warning">
                                        <i class="bs.building" style="font-size: 36px;"></i>
                                        <div class="mt-2 fw-bold">Logo required</div>
                                    </div>
                                </div>
                            @endif
                            @if($item->title)
                                <div class="ms-4">
                                    <div class="fw-bold">{{ $item->title }}</div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="d-flex align-items-start p-4">
                            @if($item->icon)
                                <div class="me-3">
                                    <i class="material-icons text-primary" style="font-size: 32px;">{{ $item->icon }}</i>
                                </div>
                            @endif
                            <div class="flex-grow-1">
                                @if($item->title)
                                    <h6 class="fw-bold mb-2">{{ $item->title }}</h6>
                                @endif
                                @if($item->subtitle)
                                    <p class="text-muted mb-3">{{ $item->subtitle }}</p>
                                @endif
                                @if($item->action_url)
                                    <div class="d-flex align-items-center">
                                        <i class="bs.link-45deg text-primary me-2"></i>
                                        <small class="text-truncate">{{ $item->action_url }}</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">📊 Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Widget Type:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-info text-capitalize">
                                {{ str_replace('_', ' ', $widgetType) }}
                            </span>
                        </dd>

                        <dt class="col-sm-5">Icon Status:</dt>
                        <dd class="col-sm-7">
                            @if($item->icon)
                                <span class="badge bg-success">
                                    <i class="material-icons me-1" style="font-size: 14px;">{{ $item->icon }}</i>
                                    {{ $item->icon }}
                                </span>
                            @else
                                <span class="badge bg-secondary">Not set</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Image Status:</dt>
                        <dd class="col-sm-7">
                            @if($item->image)
                                <span class="badge bg-success">✓ Uploaded</span>
                            @else
                                @if(in_array($widgetType, ['slider', 'logo_cloud', 'single_banner']))
                                    <span class="badge bg-danger">⚠ Required</span>
                                @else
                                    <span class="badge bg-secondary">Optional</span>
                                @endif
                            @endif
                        </dd>

                        <dt class="col-sm-5">Link URL:</dt>
                        <dd class="col-sm-7">
                            @if($item->action_url)
                                <div class="text-truncate" style="max-width: 150px;" title="{{ $item->action_url }}">
                                    <i class="bs.link-45deg text-primary me-1"></i>
                                    {{ $item->action_url }}
                                </div>
                            @else
                                <span class="badge bg-secondary">No link</span>
                            @endif
                        </dd>

                        <dt class="col-sm-5">Display Order:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-dark">#{{ $item->order ?? '0' }}</span>
                        </dd>

                        <dt class="col-sm-5">Section:</dt>
                        <dd class="col-sm-7">
                            <span class="fw-bold">{{ $widget->title ?? 'Unknown' }}</span>
                        </dd>
                    </dl>

                    @php
                        $requirements = [
                            'slider' => ['image'],
                            'menu_grid' => ['icon'],
                            'logo_cloud' => ['image'],
                            'single_banner' => ['image'],
                        ];

                        $req = $requirements[$widgetType] ?? [];
                        $missing = [];

                        foreach ($req as $field) {
                            if (empty($item->$field)) {
                                $missing[] = $field;
                            }
                        }
                    @endphp

                    @if(!empty($missing))
                        <div class="alert alert-warning mt-3 mb-0">
                            <div class="d-flex">
                                <i class="bs.exclamation-triangle me-2"></i>
                                <div>
                                    <strong>Requirements missing:</strong>
                                    <ul class="mb-0 mt-1">
                                        @foreach($missing as $field)
                                            <li>{{ ucfirst($field) }} is required for {{ $widgetType }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
