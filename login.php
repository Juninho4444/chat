<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/User.php';

$message = '';
$error = '';

if ($_POST) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            throw new Exception("Erro na conexão com o banco de dados");
        }
        
        $user = new User($db);
        
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        if (empty($email) || empty($password)) {
            throw new Exception("Email e senha são obrigatórios");
        }
        
        if ($user->login($email, $password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['plan_id'] = $user->plan_id;
            $_SESSION['whatsapp_instance'] = $user->whatsapp_instance;
            $_SESSION['whatsapp_connected'] = $user->whatsapp_connected;
            
            redirect("dashboard/index.php");
        } else {
            $error = "Email ou senha incorretos!";
        }
    } catch (Exception $e) {
        $error = "Erro: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ClientManager Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <div class="mx-auto h-12 w-12 bg-blue-600 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Faça login na sua conta
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Ou
                    <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                        crie uma nova conta
                    </a>
                </p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle mr-2 mt-0.5"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="bg-white py-8 px-6 shadow-xl rounded-lg">
                <form class="space-y-6" method="POST">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <div class="mt-1 relative">
                            <input id="email" name="email" type="email" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="Seu email">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
                        <div class="mt-1 relative">
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="Sua senha">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Entrar
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <a href="index.php" class="text-blue-600 hover:text-blue-500 text-sm">
                            ← Voltar para o site
                        </a>
                    </div>
                </form>
                
                <!-- Credenciais de teste -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Credenciais de teste:</h4>
                    <p class="text-xs text-gray-600">Email: admin@clientmanager.com</p>
                    <p class="text-xs text-gray-600">Senha: 102030</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>