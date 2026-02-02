<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$isCli = PHP_SAPI === 'cli';
$userName = null;
$result = null;
$error = null;

if ($isCli) {
    $argument = $argv[1] ?? null;

    if ($argument === null || $argument === '--all') {
        try {
            $users = User::query()->orderBy('user_name')->get();

            if ($users->isEmpty()) {
                fwrite(STDOUT, "No users found.\n");
                exit(0);
            }

            $header = sprintf("%-20s %-25s %15s\n", 'USER NAME', 'NAME', 'BALANCE');
            fwrite(STDOUT, $header);
            fwrite(STDOUT, str_repeat('-', strlen($header) - 1) . "\n");

            foreach ($users as $user) {
                $balance = number_format($user->balanceFloat, 2);
                $line = sprintf("%-20s %-25s %15s\n", $user->user_name, $user->name ?? '-', $balance);
                fwrite(STDOUT, $line);
            }

            exit(0);
        } catch (\Throwable $exception) {
            fwrite(STDERR, "Unable to fetch user information.\n");
            exit(1);
        }
    }

    $userName = $argument;

    if ($userName === '') {
        fwrite(STDERR, "Usage: php user_name_balance_check.php [--all|<user_name>]\n");
        exit(1);
    }

    try {
        $user = User::where('user_name', $userName)->first();

        if ($user) {
            $balance = number_format($user->balanceFloat, 2);
            fwrite(STDOUT, "User: {$user->user_name}\nName: " . ($user->name ?? '-') . "\nBalance: {$balance}\n");
            exit(0);
        }

        fwrite(STDERR, "User not found.\n");
        exit(1);
    } catch (\Throwable $exception) {
        fwrite(STDERR, "Unable to fetch user information.\n");
        exit(1);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $userName = trim($_POST['user_name'] ?? '');

    if ($userName === '') {
        $error = 'Please provide a user name.';
    } else {
        try {
            $user = User::where('user_name', $userName)->first();

            if ($user) {
                $result = [
                    'user_name' => $user->user_name,
                    'name' => $user->name,
                    'balance' => number_format($user->balanceFloat, 2),
                ];
            } else {
                $error = 'User not found.';
            }
        } catch (\Throwable $exception) {
            $error = 'Unable to fetch user information.';
        }
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Balance Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #333;
            margin: 0;
            padding: 40px 16px;
        }
        .container {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
        }
        h1 {
            margin-top: 0;
            font-size: 24px;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        label {
            font-weight: 600;
        }
        input[type="text"] {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
        }
        button {
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }
        .alert {
            margin-top: 18px;
            padding: 14px;
            border-radius: 8px;
            background: #fef2f2;
            color: #b91c1c;
        }
        .result {
            margin-top: 18px;
            padding: 16px;
            border-radius: 8px;
            background: #f0fdf4;
            color: #166534;
        }
        .result dt {
            font-weight: 700;
        }
        .result dd {
            margin: 0 0 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>User Balance Checker</h1>
        <form method="POST" action="">
            <label for="user_name">User Name</label>
            <input id="user_name" type="text" name="user_name" value="<?= e($userName ?? '') ?>" placeholder="Enter user name" required>
            <button type="submit">Check Balance</button>
        </form>

        <?php if ($error): ?>
            <div class="alert"><?= e($error) ?></div>
        <?php elseif ($result): ?>
            <dl class="result">
                <dt>User Name</dt>
                <dd><?= e($result['user_name']) ?></dd>
                <dt>Name</dt>
                <dd><?= e($result['name'] ?? '-') ?></dd>
                <dt>Balance</dt>
                <dd><?= e($result['balance']) ?></dd>
            </dl>
        <?php endif; ?>
    </div>
</body>
</html>
