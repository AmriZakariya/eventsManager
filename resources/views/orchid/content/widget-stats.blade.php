<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary bg-gradient text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ $stats['total'] }}</h3>
                        <small>Total Sections</small>
                    </div>
                    <div>
                        <i class="bi bi-grid-3x3" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success bg-gradient text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ $stats['active'] }}</h3>
                        <small>Active & Visible</small>
                    </div>
                    <div>
                        <i class="bi bi-eye-fill" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning bg-gradient text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ $stats['hidden'] }}</h3>
                        <small>Hidden Sections</small>
                    </div>
                    <div>
                        <i class="bi bi-eye-slash-fill" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-info bg-gradient text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">{{ $stats['dynamic'] }}</h3>
                        <small>Dynamic Data</small>
                    </div>
                    <div>
                        <i class="bi bi-lightning-charge-fill" style="font-size: 2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
