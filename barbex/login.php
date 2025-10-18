<?php
session_start();
require_once 'config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = '<div class="alert alert-danger">Por favor complete todos los campos</div>';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();

            $query = "SELECT id, name, email, password, role, status FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (password_verify($password, $user['password'])) {
                    if ($user['status'] == 'active') {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];

                        // Redirect based on role
                        if ($user['role'] == 'admin') {
                            header("Location: admin/dashboard.php");
                        } else {
                            header("Location: index.html");
                        }
                        exit();
                    } else {
                        $message = '<div class="alert alert-danger">Cuenta inactiva. Contacte al administrador.</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">Contraseña incorrecta</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">Usuario no encontrado</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Error del sistema. Intente nuevamente.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BarbeX</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/all.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: #333;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border: 2px solid #eee;
            margin-bottom: 20px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: none;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            color: white;
            font-weight: bold;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .login-links {
            text-align: center;
            margin-top: 20px;
        }
        .login-links a {
            color: #667eea;
            text-decoration: none;
        }
        .login-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h3><i class="fas fa-cut"></i> BarbeX</h3>
                <p>Iniciar Sesión</p>
            </div>
            <div class="login-body">
                <?php echo $message; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <input type="email" class="form-control" name="email" placeholder="Correo electrónico" required>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" name="password" placeholder="Contraseña" required>
                    </div>
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </button>
                </form>

                <div class="login-links">
                    <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
                    <p><a href="index.html">← Volver al inicio</a></p>
                </div>

                <hr>
                <div class="text-center">
                    <small class="text-muted">
                        <strong>Cuenta de prueba:</strong><br>
                        Admin: admin@barbex.com / password<br>
                        Cliente: cliente@barbex.com / password
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</body>
</html>