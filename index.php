<?php
require_once 'config/session.php';
require_once 'config/database.php';

requireLogin();

// Get stats from database
$stats = [];

$result = $conn->query("SELECT COUNT(*) as count FROM projects");
$stats['total_projects'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM projects WHERE status = 'completed'");
$stats['completed'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM projects WHERE status = 'running'");
$stats['running'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM clients");
$stats['total_clients'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT SUM(budget) as total FROM projects");
$stats['total_budget'] = $result->fetch_assoc()['total'] ?? 0;

$result = $conn->query("SELECT SUM(amount) as total FROM expenses");
$stats['total_expenses'] = $result->fetch_assoc()['total'] ?? 0;

// Recent projects
$recent_projects = [];
$result = $conn->query("SELECT id, name, client_name, status, progress, budget FROM projects ORDER BY created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_projects[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BuildFlow ERP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto;
            background: #F8FAFC;
            color: #333;
            line-height: 1.6;
        }
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: white;
            border-right: 1px solid #E2E8F0;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 0 20px 30px;
            border-bottom: 1px solid #E2E8F0;
            margin-bottom: 20px;
        }
        .sidebar-brand h1 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, #2563EB, #06B6D4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
        }
        .nav-item:hover {
            background: rgba(37,99,235,0.1);
            color: #2563EB;
            padding-left: 25px;
        }
        .nav-item.active {
            background: linear-gradient(90deg, rgba(37,99,235,0.15), transparent);
            color: #2563EB;
            font-weight: 600;
            border-left: 3px solid #2563EB;
            padding-left: 17px;
        }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            margin-bottom: 30px;
        }
        .top-bar h2 { margin: 0; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563EB, #06B6D4);
            color: white;
            box-shadow: 0 4px 12px rgba(37,99,235,0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37,99,235,0.4);
        }
        .btn-secondary {
            background: rgba(37,99,235,0.1);
            color: #2563EB;
            border: 1px solid #2563EB;
        }
        .btn-secondary:hover {
            background: rgba(37,99,235,0.2);
        }
        .btn-danger {
            background: rgba(239,68,68,0.1);
            color: #EF4444;
            border: 1px solid #EF4444;
        }
        .btn-danger:hover {
            background: rgba(239,68,68,0.2);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2563EB;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        .project-item {
            padding: 16px;
            background: rgba(37,99,235,0.05);
            border-radius: 8px;
            margin-bottom: 12px;
            border-left: 4px solid #2563EB;
        }
        .project-name {
            font-weight: 600;
            color: #2563EB;
            margin-bottom: 4px;
        }
        .project-meta {
            font-size: 0.85rem;
            color: #666;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 8px;
        }
        .badge-completed { background: rgba(34,197,94,0.2); color: #22C55E; }
        .badge-running { background: rgba(37,99,235,0.2); color: #2563EB; }
        .badge-upcoming { background: rgba(245,158,11,0.2); color: #F59E0B; }
        @media (max-width: 768px) {
            .sidebar { width: 0; }
            .main-content { margin-left: 0; }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-brand">
                <h1>🏗️ BuildFlow</h1>
            </div>

            <a href="index.php" class="nav-item active">📊 Dashboard</a>
            <a href="projects/" class="nav-item">📁 Projects</a>
            <a href="clients/" class="nav-item">👥 Clients</a>
            <a href="materials/" class="nav-item">🏢 Materials</a>
            <a href="finance/" class="nav-item">💰 Finance</a>
            <a href="reports/" class="nav-item">📈 Reports</a>
            <a href="logout.php" class="nav-item" onclick="return confirm('Logout?');">🚪 Logout</a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div>
                    <h2>Dashboard</h2>
                </div>
                <button class="btn btn-primary" onclick="location.href='projects/'">+ New Project</button>
            </div>

            <!-- Stats Grid -->
            <div class="grid">
                <div class="card">
                    <div style="font-size: 2rem;">📊</div>
                    <div class="stat-value"><?php echo $stats['total_projects']; ?></div>
                    <div class="stat-label">Total Projects</div>
                </div>
                <div class="card">
                    <div style="font-size: 2rem;">✅</div>
                    <div class="stat-value"><?php echo $stats['completed']; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="card">
                    <div style="font-size: 2rem;">⚙️</div>
                    <div class="stat-value"><?php echo $stats['running']; ?></div>
                    <div class="stat-label">Running</div>
                </div>
                <div class="card">
                    <div style="font-size: 2rem;">👥</div>
                    <div class="stat-value"><?php echo $stats['total_clients']; ?></div>
                    <div class="stat-label">Total Clients</div>
                </div>
                <div class="card">
                    <div style="font-size: 2rem;">💰</div>
                    <div class="stat-value">₹<?php echo number_format($stats['total_budget'], 0); ?></div>
                    <div class="stat-label">Total Budget</div>
                </div>
                <div class="card">
                    <div style="font-size: 2rem;">💸</div>
                    <div class="stat-value">₹<?php echo number_format($stats['total_expenses'], 0); ?></div>
                    <div class="stat-label">Total Expenses</div>
                </div>
            </div>

            <!-- Recent Projects -->
            <div class="card">
                <h3 style="margin-bottom: 20px;">📋 Recent Projects</h3>
                <?php if (count($recent_projects) > 0): ?>
                    <?php foreach ($recent_projects as $project): ?>
                        <div class="project-item">
                            <div class="project-name"><?php echo htmlspecialchars($project['name']); ?></div>
                            <div class="project-meta">
                                👤 <?php echo htmlspecialchars($project['client_name']); ?>
                            </div>
                            <div class="project-meta">
                                💰 ₹<?php echo number_format($project['budget'], 0); ?> |
                                📈 <?php echo $project['progress']; ?>%
                            </div>
                            <span class="badge badge-<?php echo $project['status']; ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999; text-align: center; padding: 30px;">
                        No projects yet. <a href="projects/" style="color: #2563EB;">Create one now</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
