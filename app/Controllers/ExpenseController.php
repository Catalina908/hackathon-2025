<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
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
        // Start session if not active
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // Get user ID from session
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $query = $request->getQueryParams();

    $year = isset($query['year']) ? (int)$query['year'] : (int)date('Y');
    $month = isset($query['month']) ? (int)$query['month'] : (int)date('n');
    $page = isset($query['page']) ? max((int)$query['page'], 1) : 1;
    $pageSize = self::PAGE_SIZE;
    $from = ($page - 1) * $pageSize;

    $user = new \App\Domain\Entity\User($userId, $_SESSION['username'] ?? '', '', new \DateTimeImmutable());

    $expenses = $this->expenseService->list($user, $year, $month, $page, $pageSize);
    $total = $this->expenseService->count($user, $year, $month);
    $years = $this->expenseService->years($user);

    return $this->render($response, 'expenses/index.twig', [
        'expenses' => $expenses,
        'year' => $year,
        'month' => $month,
        'years' => $years,
        'page' => $page,
        'pageSize' => $pageSize,
        'total' => $total,
        'hasPrev' => $page > 1,
        'hasNext' => $page * $pageSize < $total
    ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view

     
     if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $categories = ['groceries', 'utilities', 'transport', 'entertainment', 'housing', 'health', 'other'];

    $formValues = $_SESSION['form_values'] ?? [];
    $formErrors = $_SESSION['form_errors'] ?? [];

    // Clear flash data right after reading
    unset($_SESSION['form_values'], $_SESSION['form_errors']);

    return $this->render($response, 'expenses/create.twig', [
        'categories' => $categories,
        'values' => $formValues,
        'errors' => $formErrors,
    ]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    $data = $request->getParsedBody();

    $values = [
        'date' => $data['date'] ?? '',
        'category' => $data['category'] ?? '',
        'amount' => $data['amount'] ?? '',
        'description' => $data['description'] ?? '',
    ];

    $errors = [];

    // ✅ Validation
    $today = new \DateTimeImmutable('today');
    $expenseDate = \DateTimeImmutable::createFromFormat('Y-m-d', $values['date']);

    if (!$expenseDate || $expenseDate > $today) {
        $errors['date'] = 'Date cannot be in the future.';
    }

    if (empty($values['category'])) {
        $errors['category'] = 'Category must be selected.';
    }

    if (!is_numeric($values['amount']) || (float)$values['amount'] <= 0) {
        $errors['amount'] = 'Amount must be greater than 0.';
    }

    if (empty(trim($values['description']))) {
        $errors['description'] = 'Description cannot be empty.';
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_values'] = $values;

        return $response
            ->withHeader('Location', '/expenses/create')
            ->withStatus(302);
    }

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $user = new \App\Domain\Entity\User($userId, $_SESSION['username'], '', new \DateTimeImmutable());

    $this->expenseService->create(
        $user,
        (float)$values['amount'],
        $values['description'],
        $expenseDate,
        $values['category']
    );

    return $response->withHeader('Location', '/expenses')->withStatus(302);
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
}
