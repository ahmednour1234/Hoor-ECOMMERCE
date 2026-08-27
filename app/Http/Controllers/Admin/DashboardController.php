<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Support\DatePeriodFilter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Admin dashboard overview.
 *
 * The period is resolved once and handed to the service, so every figure on
 * the page reports the same window.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function __invoke(Request $request): View
    {
        $period = DatePeriodFilter::fromRequest($request->only(['period', 'from', 'to']));

        return view('admin.dashboard', $this->dashboard->overview($period));
    }
}
