<div class="row mb-4 g-3">
    <div class="col-sm-6 col-md-3">
        <div class="dashboard-stat-card bg-primary bg-gradient text-white rounded-3 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="stat-label">Total Users</span>
                    <h2 class="stat-value mb-0">{{ number_format($stats['total']) }}</h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-people fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="stat-footer mt-2">
                <small class="opacity-75">
                    <i class="bi bi-arrow-up"></i> {{ $stats['new_today'] }} today
                </small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="dashboard-stat-card bg-success bg-gradient text-white rounded-3 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="stat-label">Active Now</span>
                    <h2 class="stat-value mb-0">{{ $stats['visible'] }}</h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-eye fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="stat-footer mt-2">
                <small class="opacity-75">Visible users</small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="dashboard-stat-card bg-info bg-gradient text-white rounded-3 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="stat-label">Exhibitors</span>
                    <h2 class="stat-value mb-0">{{ number_format($stats['exhibitors']) }}</h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-building fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="stat-footer mt-2">
                <small class="opacity-75">
                    {{ $stats['with_company'] }} with companies
                </small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-md-3">
        <div class="dashboard-stat-card bg-warning bg-gradient text-white rounded-3 p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="stat-label">Visitors</span>
                    <h2 class="stat-value mb-0">{{ number_format($stats['visitors']) }}</h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-person fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="stat-footer mt-2">
                <small class="opacity-75">
                    {{ $stats['new_this_week'] }} new this week
                </small>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-stat-card {
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
        min-height: 100px;
    }

    .dashboard-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .stat-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.9;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .stat-icon i {
        opacity: 0.5;
        transition: opacity 0.2s;
    }

    .dashboard-stat-card:hover .stat-icon i {
        opacity: 0.8;
    }
</style>
