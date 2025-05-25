<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user registration

       $data = (array) $request->getParsedBody();
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';

    $errors = [];

    // ✅ Validation rules
    if (strlen($username) < 4) {
        $errors['username'] = 'Username must be at least 4 characters long.';
    }

    if (!preg_match('/^(?=.*\d).{8,}$/', $password)) {
        $errors['password'] = 'Password must be at least 8 characters long and include at least one number.';
    }

    if (!empty($errors)) {
        $this->logger->warning('Registration validation failed', $errors);
        return $this->render($response, 'auth/register.twig', [
            'errors' => $errors,
            'username' => $username,
            'password' => $password
        ]);
    }

    // ✅ Try to register using AuthService
    try {
        $this->authService->register($username, $password);
        $this->logger->info("New user registered: $username");

        return $response->withHeader('Location', '/login')->withStatus(302);
    } catch (\RuntimeException $e) {
        $errors['username'] = $e->getMessage();
        $this->logger->error('Registration failed: ' . $e->getMessage());

        return $this->render($response, 'auth/register.twig', [
            'errors' => $errors,
            'username' => $username,
            'password' => $password
        ]);
    }
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user login, handle login failures
       $data = (array) $request->getParsedBody();
       $username = trim($data['username'] ?? '');
       $password = $data['password'] ?? '';

    if ($this->authService->attempt($username, $password)) {
        $this->logger->info("User $username successfully logged in.");
        return $response->withHeader('Location', '/')->withStatus(302); // Redirect to dashboard
    }

    $this->logger->warning("Failed login attempt for username: $username");

    return $this->render($response, 'auth/login.twig', [
        'error' => 'Invalid username or password.',
        'username' => $username
    ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        // TODO: handle logout by clearing session data and destroying session

         // ✅ Start session if needed
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    // ✅ Clear session variables and destroy
    $_SESSION = [];
    session_destroy();

    $this->logger->info('User logged out.');

    // ✅ Redirect to login
    return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
