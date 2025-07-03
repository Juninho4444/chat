<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Client.php';
require_once __DIR__ . '/../classes/MessageTemplate.php';

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Estatísticas
$client = new Client($db);
$clients_stmt = $client->readAll($_SESSION['user_id']);
$total_clients = $clients_stmt->rowCount();

$active_clients = 0;
$inactive_clients = 0;
while ($row = $clients_stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['status'] == 'active') {
        $active_clients++;
    } else {
        $inactive_clients++;
    }
}

// Mensagens enviadas hoje
$query = "SELECT COUNT(*) as total FROM message_history WHERE user_id = :user_id AND DATE(sent_at) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$messages_today = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ClientManager Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="flex h-screen bg-gray-50">
        <div class="hidden md:flex md:w-64 md:flex-col">
            <div class="flex flex-col flex-grow pt-5 overflow-y-auto bg-white border-r">
                <div class="flex items-center flex-shrink-0 px-4">
                    <h1 class="text-xl font-bold text-blue-600">ClientManager Pro</h1>
                </div>
                <div class="mt-5 flex-grow flex flex-col">
                    <nav class="flex-1 px-2 space-y-1">
                        <a href="index.php" class="bg-blue-100 text-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <i class="fas fa-home mr-3"></i>
                            Dashboard
                        </a>
                        <a href="clients.php" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <i class="fas fa-users mr-3"></i>
                            Clientes
                        </a>
                        <a href="messages.php" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <i class="fab fa-whatsapp mr-3"></i>
                            Mensagens
                        </a>
                        <a href="templates.php" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <i class="fas fa-template mr-3"></i>
                            Templates
                        </a>
                        <a href="whatsapp.php" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <i class="fas fa-qrcode mr-3"></i>
                            WhatsApp
                        </a>
                        <a href="reports.php" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <i class="fas fa-chart-bar mr-3"></i>
                            Relatórios
                        </a>
                    </nav>
                </div>
                <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                    <div class="flex-shrink-0 w-full group block">
                        <div class="flex items-center">
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-700"><?php echo $_SESSION['user_name']; ?></p>
                                <a href="../logout.php" class="text-xs font-medium text-gray-500 hover:text-gray-700">Sair</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex flex-col w-0 flex-1 overflow-hidden">
            <main class="flex-1 relative overflow-y-auto focus:outline-none">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
                    </div>
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <!-- Stats -->
                        <div class="mt-8">
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="bg-white overflow-hidden shadow rounded-lg">
                                    <div class="p-5">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-users text-gray-400 text-2xl"></i>
                                            </div>
                                            <div class="ml-5 w-0 flex-1">
                                                <dl>
                                                    <dt class="text-sm font-medium text-gray-500 truncate">Total de Clientes</dt>
                                                    <dd class="text-lg font-medium text-gray-900"><?php echo $total_clients; ?></dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white overflow-hidden shadow rounded-lg">
                                    <div class="p-5">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-user-check text-green-400 text-2xl"></i>
                                            </div>
                                            <div class="ml-5 w-0 flex-1">
                                                <dl>
                                                    <dt class="text-sm font-medium text-gray-500 truncate">Clientes Ativos</dt>
                                                    <dd class="text-lg font-medium text-gray-900"><?php echo $active_clients; ?></dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white overflow-hidden shadow rounded-lg">
                                    <div class="p-5">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fab fa-whatsapp text-green-400 text-2xl"></i>
                                            </div>
                                            <div class="ml-5 w-0 flex-1">
                                                <dl>
                                                    <dt class="text-sm font-medium text-gray-500 truncate">Mensagens Hoje</dt>
                                                    <dd class="text-lg font-medium text-gray-900"><?php echo $messages_today; ?></dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white overflow-hidden shadow rounded-lg">
                                    <div class="p-5">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-wifi text-<?php echo $_SESSION['whatsapp_connected'] ? 'green' : 'red'; ?>-400 text-2xl"></i>
                                            </div>
                                            <div class="ml-5 w-0 flex-1">
                                                <dl>
                                                    <dt class="text-sm font-medium text-gray-500 truncate">WhatsApp</dt>
                                                    <dd class="text-lg font-medium text-gray-900">
                                                        <?php echo $_SESSION['whatsapp_connected'] ? 'Conectado' : 'Desconectado'; ?>
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="mt-8">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Ações Rápidas</h2>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <a href="clients.php?action=add" class="bg-white p-6 rounded-lg shadow hover:shadow-md transition">
                                    <div class="flex items-center">
                                        <i class="fas fa-user-plus text-blue-500 text-2xl mr-4"></i>
                                        <div>
                                            <h3 class="font-medium text-gray-900">Adicionar Cliente</h3>
                                            <p class="text-sm text-gray-500">Cadastrar novo cliente</p>
                                        </div>
                                    </div>
                                </a>

                                <a href="messages.php?action=send" class="bg-white p-6 rounded-lg shadow hover:shadow-md transition">
                                    <div class="flex items-center">
                                        <i class="fab fa-whatsapp text-green-500 text-2xl mr-4"></i>
                                        <div>
                                            <h3 class="font-medium text-gray-900">Enviar Mensagem</h3>
                                            <p class="text-sm text-gray-500">Enviar mensagem via WhatsApp</p>
                                        </div>
                                    </div>
                                </a>

                                <a href="templates.php?action=add" class="bg-white p-6 rounded-lg shadow hover:shadow-md transition">
                                    <div class="flex items-center">
                                        <i class="fas fa-plus text-purple-500 text-2xl mr-4"></i>
                                        <div>
                                            <h3 class="font-medium text-gray-900">Criar Template</h3>
                                            <p class="text-sm text-gray-500">Novo template de mensagem</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <?php if (!$_SESSION['whatsapp_connected']): ?>
                        <!-- WhatsApp Connection Alert -->
                        <div class="mt-8">
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            <strong>WhatsApp não conectado!</strong>
                                            Para enviar mensagens automáticas, você precisa conectar seu WhatsApp.
                                            <a href="whatsapp.php" class="font-medium underline">Conectar agora</a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>