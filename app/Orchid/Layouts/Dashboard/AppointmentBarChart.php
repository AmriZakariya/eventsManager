<?php

namespace App\Orchid\Layouts\Dashboard;

use Orchid\Screen\Layouts\Chart;

class AppointmentBarChart extends Chart
{
    /**
     * The key from the screen's query() that contains the chart data.
     */
    protected $target = 'appointment_trend';

    /**
     * Chart title shown above the graph.
     */
    protected $title = 'B2B Appointments Scheduled (Last 14 Days)';

    /**
     * Chart height in pixels.
     */
    protected $height = 250;

    /**
     * Bar chart to contrast with the line chart beside it.
     */
    protected $type = 'bar';

    /**
     * Export options.
     */
    protected $export = true;
}
