<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/WhatsAppAPI.php';
require_once '../classes/User.php';

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    redirect("../login.php");
}

$whatsapp = new WhatsAppAPI();
$message = '';
$error = '';
$qr_code = '';
$instance_name = $_SESSION['whatsapp_instance'] ?? 'instance_' . $_SESSION['user_id'];

// Processar ações
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_instance':
                    $result = $whatsapp->createInstance($instance_name);
                    if ($result['status_code'] == 201 || $result['status_code'] == 200) {
                        $message = "Instância criada com sucesso! Agora você pode gerar o QR Code.";
                        
                        // Atualizar usuário
                        $database = new Database();
                        $db = $database->getConnection();
                        $user = new User($db);
                        $user->id = $_SESSION['user_id'];
                        $user->updateWhatsAppInstance($instance_name);
                        $_SESSION['whatsapp_instance'] = $instance_name;
                    } else {
                        $error = "Erro ao criar instância. Código: " . $result['status_code'];
                        if (isset($result['data']['message'])) {
                            $error .= " - " . $result['data']['message'];
                        }
                    }
                    break;
                    
                case 'get_qr':
                    $result = $whatsapp->getQRCode($instance_name);
                    if ($result['status_code'] == 200 && isset($result['data']['base64'])) {
                        $qr_code = $result['data']['base64'];
                        $message = "QR Code gerado! Escaneie com seu WhatsApp.";
                    } else {
                        $error = "Erro ao obter QR Code. Tente criar uma nova instância.";
                    }
                    break;
                    
                case 'test_message':
                    if (!empty($_POST['test_phone']) && !empty($_POST['test_message'])) {
                        $result = $whatsapp->sendMessage($instance_name, $_POST['test_phone'], $_POST['test_message']);
                        if ($result['status_code'] == 200) {
                            $message = "Mensagem de teste enviada com sucesso!";
                        } else {
                            $error = "Erro ao enviar mensagem de teste.";
                        }
                    } else {
                        $error = "Preencha o número e a mensagem de teste.";
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Erro: " . $e->getMessage();
    }
}

// Verificar status da instância
$status = null;
if ($_SESSION['whatsapp_instance']) {
    try {
        $status_result = $whatsapp->getInstanceStatus($_SESSION['whatsapp_instance']);
        if ($status_result['status_code'] == 200) {
            $status = $status_result['data'];
        }
    } catch (Exception $e) {
        // Ignorar erro de status
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp - ClientManager Pro</title>
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
                        <a href="index.php" class="text-gray-600 hover:bg-gray-50 hover:text-gray-900 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
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
                        <a href="whatsapp.php" class="bg-blue-100 text-blue-700 group flex items-center px-2 py-2 text-sm font-medium rounded-md">
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
                                <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
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
                        <h1 class="text-2xl font-semibold text-gray-900">Configuração do WhatsApp</h1>
                    </div>
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        
                        <!-- Mensagens de feedback -->
                        <?php if ($message): ?>
                            <div class="mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                                <div class="flex">
                                    <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                                    <span><?php echo htmlspecialchars($message); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                                <div class="flex">
                                    <i class="fas fa-exclamation-circle mr-2 mt-0.5"></i>
                                    <span><?php echo htmlspecialchars($error); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Status da Conexão -->
                        <div class="mt-8 bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Status da Conexão</h3>
                                <div class="mt-2 max-w-xl text-sm text-gray-500">
                                    <p>Status atual da sua conexão com o WhatsApp</p>
                                </div>
                                <div class="mt-5">
                                    <?php if ($status): ?>
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <?php if (isset($status['state']) && $status['state'] == 'open'): ?>
                                                    <i class="fas fa-check-circle text-green-400 text-2xl"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle text-red-400 text-2xl"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900">
                                                    Status: <?php echo isset($status['state']) ? ucfirst($status['state']) : 'Desconhecido'; ?>
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    Instância: <?php echo htmlspecialchars($_SESSION['whatsapp_instance'] ?? 'Não criada'); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-exclamation-triangle text-yellow-400 text-2xl"></i>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-medium text-gray-900">
                                                    WhatsApp não configurado
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    Você precisa criar uma instância e conectar seu WhatsApp
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Configuração -->
                        <div class="mt-8 bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Conectar WhatsApp</h3>
                                <div class="mt-2 max-w-xl text-sm text-gray-500">
                                    <p>Siga os passos abaixo para conectar seu WhatsApp à plataforma</p>
                                </div>

                                <div class="mt-6 space-y-6">
                                    <!-- Passo 1: Criar Instância -->
                                    <div class="border rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-100 text-blue-600 rounded-full text-sm font-medium mr-2">1</span>
                                            Criar Instância
                                        </h4>
                                        <p class="text-sm text-gray-600 mb-4">
                                            Primeiro, você precisa criar uma instância do WhatsApp para sua conta.
                                        </p>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="create_instance">
                                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150">
                                                <i class="fas fa-plus mr-2"></i>
                                                Criar Instância
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Passo 2: Obter QR Code -->
                                    <?php if ($_SESSION['whatsapp_instance']): ?>
                                    <div class="border rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <span class="inline-flex items-center justify-center w-6 h-6 bg-green-100 text-green-600 rounded-full text-sm font-medium mr-2">2</span>
                                            Escanear QR Code
                                        </h4>
                                        <p class="text-sm text-gray-600 mb-4">
                                            Clique no botão abaixo para gerar o QR Code e escaneie com seu WhatsApp.
                                        </p>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="get_qr">
                                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-150">
                                                <i class="fab fa-whatsapp mr-2"></i>
                                                Gerar QR Code
                                            </button>
                                        </form>

                                        <?php if ($qr_code): ?>
                                        <div class="mt-4">
                                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                                <img src="data:image/png;base64,<?php echo $qr_code; ?>" 
                                                     alt="QR Code WhatsApp" 
                                                     class="mx-auto max-w-xs border rounded-lg shadow-sm">
                                                <p class="mt-2 text-sm text-gray-600">
                                                    Escaneie este QR Code com seu WhatsApp
                                                </p>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    O QR Code expira em alguns minutos. Se não funcionar, gere um novo.
                                                </p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Instruções -->
                                    <div class="border rounded-lg p-4 bg-blue-50">
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                            Como escanear o QR Code:
                                        </h4>
                                        <ol class="list-decimal list-inside text-sm text-gray-600 space-y-1">
                                            <li>Abra o WhatsApp no seu celular</li>
                                            <li>Toque nos três pontos (menu) no canto superior direito</li>
                                            <li>Selecione "Dispositivos conectados"</li>
                                            <li>Toque em "Conectar um dispositivo"</li>
                                            <li>Aponte a câmera para o QR Code acima</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Teste de Mensagem -->
                        <?php if ($status && isset($status['state']) && $status['state'] == 'open'): ?>
                        <div class="mt-8 bg-white shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    <i class="fas fa-paper-plane text-green-500 mr-2"></i>
                                    Teste de Mensagem
                                </h3>
                                <div class="mt-2 max-w-xl text-sm text-gray-500">
                                    <p>Envie uma mensagem de teste para verificar se tudo está funcionando</p>
                                </div>
                                <form method="POST" class="mt-5">
                                    <input type="hidden" name="action" value="test_message">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="test_phone" class="block text-sm font-medium text-gray-700">
                                                Número de teste (com código do país)
                                            </label>
                                            <input type="tel" name="test_phone" id="test_phone" 
                                                   placeholder="5511999999999"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        <div>
                                            <label for="test_message" class="block text-sm font-medium text-gray-700">
                                                Mensagem de teste
                                            </label>
                                            <input type="text" name="test_message" id="test_message" 
                                                   value="Teste de conexão do ClientManager Pro!"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-150">
                                            <i class="fab fa-whatsapp mr-2"></i>
                                            Enviar Teste
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Auto-refresh para verificar status da conexão a cada 30 segundos
        setInterval(function() {
            // Só recarrega se não estiver conectado
            if (document.querySelector('.fa-times-circle') || document.querySelector('.fa-exclamation-triangle')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>