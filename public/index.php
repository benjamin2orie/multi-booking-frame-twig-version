<?php
// ✅ 1. Start session
session_start();

//
// ✅ 2. Persistent user storage
//
$users = $_SESSION['users'] ?? [];

function getUsers() {
  global $users;
  return $users;
}

function saveUser($email, $password, $name) {
  global $users;

  if (isset($users[$email])) {
    return false;
  }

  $users[$email] = ['email' => $email, 'password' => $password, 'name' => $name];
  $_SESSION['users'] = $users;
  return true;
}

function findUser($email, $password) {
  global $users;
  return isset($users[$email]) && $users[$email]['password'] === $password;
}

//
// ✅ 3. Handle login
//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['page'] ?? '') === 'login') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';

  if (findUser($email, $password)) {
    $_SESSION['user'] = ['email' => $email];
    header('Location: /?page=dashboard');
    exit;
  } else {
    $error = 'Invalid email or password';
  }
}

//
// ✅ 4. Handle signup
//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['page'] ?? '') === 'signup') {
  $email = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';
  $name = $_POST['name'] ?? '';

  if (strlen($name) < 4 || strlen($name) > 15) {
    $error = 'Name must be between 4 to 15 characters';
  } elseif (!$email || strlen($password) < 6) {
    $error = 'Password and email must be at least 6 characters';
  } elseif (!saveUser($email, $password, $name)) {
    $error = 'User already exists! Please login';
  } else {
    $_SESSION['user'] = ['email' => $email];
    header('Location: /?page=dashboard');
    exit;
  }
}

//
// ✅ 5. Ticket management
//
function getTickets() {
  return $_SESSION['tickets'] ?? [];
}

function saveTicket($ticket) {
  $_SESSION['tickets'][$ticket['id']] = $ticket;
}

function deleteTicket($id) {
  unset($_SESSION['tickets'][$id]);
}

function generateId() {
  return bin2hex(random_bytes(8));
}

$form = ['id' => '', 'title' => '', 'description' => '', 'priority' => '', 'status' => 'open'];
$editing = false;
$error = $error ?? null;

// ✅ Handle ticket form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['page'] ?? '') === 'tickets') {
  $id = $_POST['id'] ?: generateId();
  $title = $_POST['title'] ?? '';
  $description = $_POST['description'] ?? '';
  $priority = $_POST['priority'] ?? '';
  $status = $_POST['status'] ?? '';

  if (!$title || !$description || !in_array($status, ['open', 'in_progress', 'closed'])) {
    $error = 'All fields are required.';
    $form = compact('id', 'title', 'description', 'priority', 'status');
    $editing = !empty($_POST['id']);
  } else {
    saveTicket(compact('id', 'title', 'description', 'priority', 'status'));
    header('Location: /?page=tickets');
    exit;
  }
}

// ✅ Handle ticket edit
if ($_GET['edit'] ?? false) {
  $id = $_GET['edit'];
  $form = getTickets()[$id] ?? $form;
  $editing = true;
}

// ✅ Handle ticket delete
if ($_GET['delete'] ?? false) {
  deleteTicket($_GET['delete']);
  header('Location: /?page=tickets');
  exit;
}

//
// ✅ 6. Handle logout (preserve users)
//
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/logout') {
  unset($_SESSION['user']); // Only remove user, not all session data
  header('Location: /?page=login');
  exit;
}

//
// ✅ 7. Load Twig
//
require_once __DIR__ . '/../vendor/autoload.php';
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../views');
$twig = new \Twig\Environment($loader);

//
// ✅ 8. Shared variables
//
$shared = [
  'isAuthenticated' => isset($_SESSION['user']),
  'user' => $_SESSION['user'] ?? null,
  'location' => $_SERVER['REQUEST_URI']
];

//
// ✅ 9. Routing
//
$page = $_GET['page'] ?? 'home';

switch ($page) {
  case 'login':
    echo $twig->render('login.twig', array_merge($shared, ['error' => $error]));
    break;

  case 'signup':
    echo $twig->render('signup.twig', array_merge($shared, ['error' => $error]));
    break;

  case 'dashboard':
    if (!isset($_SESSION['user'])) {
      header('Location: /?page=login');
      exit;
    }

    $tickets = getTickets();
    $open = count(array_filter($tickets, fn($t) => $t['status'] === 'open'));
    $inProgress = count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress'));
    $closed = count(array_filter($tickets, fn($t) => $t['status'] === 'closed'));

    echo $twig->render('dashboard.twig', array_merge($shared, [
      'open' => $open,
      'inProgress' => $inProgress,
      'closed' => $closed
    ]));
    break;

  case 'tickets':
    if (!isset($_SESSION['user'])) {
      header('Location: /?page=login');
      exit;
    }

    echo $twig->render('tickets.twig', array_merge($shared, [
      'tickets' => getTickets(),
      'form' => $form,
      'editing' => $editing,
      'error' => $error
    ]));
    break;

  default:
    echo $twig->render('home.twig', $shared);
}
