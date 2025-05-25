<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

$budgets = require __DIR__ . '/../config/categories.php';

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        // TODO: add necessary services here and have them injected by the DI container
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: parse the request parameters
        // TODO: load the currently logged-in user
        // TODO: get the list of available years for the year-month selector
        // TODO: call service to generate the overspending alerts for current month
        // TODO: call service to compute total expenditure per selected year/month
        // TODO: call service to compute category totals per selected year/month
        // TODO: call service to compute category averages per selected year/month

         if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $query = $request->getQueryParams();
    $year = isset($query['year']) ? (int)$query['year'] : (int)date('Y');
    $month = isset($query['month']) ? (int)$query['month'] : (int)date('n');

    // Load user and budget config
    $user = new \App\Domain\Entity\User($userId, $_SESSION['username'], '', new \DateTimeImmutable());
    $budgets = require __DIR__ . '/../../config/categories.php';

    // Service calls
    $total = $this->expenseService->getTotalForMonth($user, $year, $month);
    $totals = $this->expenseService->getTotalsPerCategory($user, $year, $month);
    $averages = $this->expenseService->getAveragesPerCategory($user, $year, $month);
    $availableYears = $this->expenseService->getYearsWithExpenses($user);

    // Alerts (only for current month)
    $alerts = [];
    if ($year === (int)date('Y') && $month === (int)date('n')) {
        foreach ($totals as $category => $amount) {
            if (isset($budgets[$category]) && $amount > $budgets[$category]) {
                $excess = number_format($amount - $budgets[$category], 2);
                $alerts[] = "⚠ " . ucfirst($category) . " budget exceeded by $excess €";
            }
        }
    }

    return $this->render($response, 'dashboard.twig', [
           'alerts' => $alerts,
    'years' => $availableYears,
    'year' => $selectedYear,
    'month' => $selectedMonth,
    'totalForMonth' => $total,
    'totalsForCategories' => $totals,
    'averagesForCategories' => $averages,
    ]);
    }
}
