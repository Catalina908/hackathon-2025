<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Slim\Psr7\UploadedFile;
use DateTimeImmutable;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
          private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the expenses page

        // Hints:
        // - use the session to get the current user ID
        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user

        // parse request parameters
        $userId = 1; // TODO: obtain logged-in user ID from session
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);

        $expenses = $this->expenseService->list($userId, $page, $pageSize);

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'page'     => $page,
            'pageSize' => $pageSize,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view

        return $this->render($response, 'expenses/create.twig', ['categories' => []]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        return $response;
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

        $expense = ['id' => 1];

        return $this->render($response, 'expenses/edit.twig', ['expense' => $expense, 'categories' => []]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        return $response;
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

        return $response;
    }
    public function import(Request $request, Response $response): Response
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $uploadedFiles = $request->getUploadedFiles();
    $csvFile = $uploadedFiles['csv'] ?? null;

    if (!$csvFile instanceof UploadedFile || $csvFile->getError() !== UPLOAD_ERR_OK) {
        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    $stream = $csvFile->getStream()->detach();
    $handle = fopen($stream, 'r');
    if (!$handle) {
        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    $user = new \App\Domain\Entity\User($userId, $_SESSION['username'], '', new \DateTimeImmutable());

    $imported = 0;
    $skipped = [];

    $validCategories = ['groceries', 'utilities', 'transport', 'entertainment', 'housing', 'health', 'other'];
    $seen = [];

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 4) continue;

        [$dateStr, $desc, $amountStr, $category] = $row;
        $key = md5("$dateStr|$desc|$amountStr|$category");

        // Duplicate or invalid category
        if (isset($seen[$key]) || !in_array(strtolower($category), $validCategories)) {
            $skipped[] = implode(',', $row);
            continue;
        }

        try {
            $date = new DateTimeImmutable($dateStr);
            $amount = (float)$amountStr;

            $this->expenseService->create($user, $amount, $desc, $date, $category);
            $imported++;
            $seen[$key] = true;

        } catch (\Exception $e) {
            $skipped[] = implode(',', $row);
        }
    }

    fclose($handle);

    // Logging
    $this->logger->info("CSV import completed. Imported: $imported");
    foreach ($skipped as $line) {
        $this->logger->warning("Skipped CSV line: $line");
    }

    return $response->withHeader('Location', '/expenses')->withStatus(302);
}
}
