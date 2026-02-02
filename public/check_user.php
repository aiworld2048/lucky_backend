<?php
/**
 * User Information Checker
 * Display all users with their details
 */

// Load Laravel bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Enums\UserType;

// Check if running in CLI or browser
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // Set headers for browser
    header('Content-Type: text/html; charset=utf-8');
}

// Get all users with their roles
$users = User::with('roles')->orderBy('type')->orderBy('user_name')->get();

// Group users by type
$usersByType = [
    'Owner' => [],
    'Agent' => [],
    'Player' => [],
];

foreach ($users as $user) {
    $typeName = match($user->type) {
        10 => 'Owner',
        20 => 'Agent',
        40 => 'Player',
        default => 'Unknown'
    };
    
    if (isset($usersByType[$typeName])) {
        $usersByType[$typeName][] = $user;
    }
}

// Display HTML if in browser
if (!$isCLI) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Information - Buffalo Game</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px;
                min-height: 100vh;
            }
            
            .container {
                max-width: 1400px;
                margin: 0 auto;
            }
            
            .header {
                background: white;
                padding: 30px;
                border-radius: 15px;
                margin-bottom: 20px;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            }
            
            .header h1 {
                color: #333;
                margin-bottom: 10px;
            }
            
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 20px;
            }
            
            .stat-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
            }
            
            .stat-card h3 {
                font-size: 14px;
                opacity: 0.9;
                margin-bottom: 10px;
            }
            
            .stat-card .number {
                font-size: 32px;
                font-weight: bold;
            }
            
            .section {
                background: white;
                border-radius: 15px;
                padding: 25px;
                margin-bottom: 20px;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            }
            
            .section h2 {
                color: #333;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #667eea;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            
            th {
                background: #667eea;
                color: white;
                padding: 12px;
                text-align: left;
                font-weight: 600;
            }
            
            td {
                padding: 12px;
                border-bottom: 1px solid #e0e0e0;
            }
            
            tr:hover {
                background: #f5f5f5;
            }
            
            .badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }
            
            .badge.active {
                background: #e8f5e9;
                color: #2e7d32;
            }
            
            .badge.inactive {
                background: #ffebee;
                color: #c62828;
            }
            
            .badge.role-owner {
                background: #f3e5f5;
                color: #7b1fa2;
            }
            
            .badge.role-agent {
                background: #e3f2fd;
                color: #1565c0;
            }
            
            .badge.role-player {
                background: #e8f5e9;
                color: #2e7d32;
            }
            
            .balance {
                font-weight: bold;
                color: #27ae60;
            }
            
            .copy-btn {
                background: #667eea;
                color: white;
                border: none;
                padding: 6px 12px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 12px;
            }
            
            .copy-btn:hover {
                background: #5568d3;
            }
            
            .password-info {
                background: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .password-info strong {
                color: #856404;
            }
            
            @media (max-width: 768px) {
                table {
                    font-size: 12px;
                }
                
                th, td {
                    padding: 8px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎰 Buffalo Game - User Information</h1>
                <p>Database: <?php echo config('database.connections.pgsql.database'); ?></p>
                
                <div class="stats">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <div class="number"><?php echo $users->count(); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Owners</h3>
                        <div class="number"><?php echo count($usersByType['Owner']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Agents</h3>
                        <div class="number"><?php echo count($usersByType['Agent']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Players</h3>
                        <div class="number"><?php echo count($usersByType['Player']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="password-info">
                <strong>🔒 Default Password:</strong> buffalovip
            </div>
            
            <?php foreach ($usersByType as $type => $users): ?>
                <?php if (count($users) > 0): ?>
                    <div class="section">
                        <h2><?php echo $type; ?> Users (<?php echo count($users); ?>)</h2>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Type</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Balance</th>
                                    <th>Agent ID</th>
                                    <th>Referral Code</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user->id; ?></td>
                                        <td>
                                            <strong><?php echo $user->user_name; ?></strong>
                                            <button class="copy-btn" onclick="copyToClipboard('<?php echo $user->user_name; ?>')">Copy</button>
                                        </td>
                                        <td><?php echo $user->name; ?></td>
                                        <td><?php echo $user->phone; ?></td>
                                        <td><?php echo $user->type; ?></td>
                                        <td>
                                            <?php if ($user->roles->isNotEmpty()): ?>
                                                <span class="badge role-<?php echo strtolower($user->roles[0]->title); ?>">
                                                    <?php echo $user->roles[0]->title; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge">No Role</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $user->status == 1 ? 'active' : 'inactive'; ?>">
                                                <?php echo $user->status == 1 ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="balance"><?php echo number_format($user->balanceFloat, 2); ?> MMK</td>
                                        <td><?php echo $user->agent_id ?? '-'; ?></td>
                                        <td><?php echo $user->referral_code ?? '-'; ?></td>
                                        <td><?php echo $user->created_at->format('Y-m-d H:i'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <script>
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('Username copied: ' + text);
                }, function(err) {
                    alert('Failed to copy');
                });
            }
        </script>
    </body>
    </html>
    <?php
} else {
    // CLI output
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════════\n";
    echo "                    BUFFALO GAME - USER INFORMATION                    \n";
    echo "═══════════════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Total Users: " . $users->count() . "\n";
    echo "Owners: " . count($usersByType['Owner']) . "\n";
    echo "Agents: " . count($usersByType['Agent']) . "\n";
    echo "Players: " . count($usersByType['Player']) . "\n";
    echo "\n";
    echo "Default Password: buffalovip\n";
    echo "\n";
    
    foreach ($usersByType as $type => $users) {
        if (count($users) > 0) {
            echo "───────────────────────────────────────────────────────────────────────\n";
            echo strtoupper($type) . " USERS (" . count($users) . ")\n";
            echo "───────────────────────────────────────────────────────────────────────\n";
            
            foreach ($users as $user) {
                echo sprintf(
                    "ID: %-4s | Username: %-15s | Name: %-20s | Balance: %12s MMK | Status: %s\n",
                    $user->id,
                    $user->user_name,
                    $user->name,
                    number_format($user->balanceFloat, 2),
                    $user->status == 1 ? 'Active' : 'Inactive'
                );
            }
            echo "\n";
        }
    }
    
    echo "═══════════════════════════════════════════════════════════════════════\n";
}

