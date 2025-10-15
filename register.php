<?php
session_start();
require_once 'config/database.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $message = '<div class="alert alert-danger">Por favor complete todos los campos obligatorios</div>';
    } elseif ($password !== $confirm_password) {
        $message = '<div class="alert alert-danger">Las contraseñas no coinciden</div>';
    } elseif (strlen($password) < 6) {
        $message = '<div class="alert alert-danger">La contraseña debe tener al menos 6 caracteres</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Correo electrónico no válido</div>';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Check if email already exists
            $checkQuery = "SELECT id FROM users WHERE email = :email";
            $stmt = $db->prepare($checkQuery);
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $message = '<div class="alert alert-danger">Este correo electrónico ya está registrado</div>';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO users (name, email, password, phone, role) VALUES (:name, :email, :password, :phone, 'customer')";

                $stmt = $db->prepare($query);
                $stmt->bindParam(":name", $name);
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":password", $hashed_password);
                $stmt->bindParam(":phone", $phone);

                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Cuenta creada exitosamente. <a href="login.php">Iniciar sesión</a></div>';
                } else {
                    $message = '<div class="alert alert-danger">Error al crear la cuenta. Intente nuevamente.</div>';
                }
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
    <title>Registro - BarbeX</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/all.css">
    <link rel="stylesheet" href="assets/sass/style.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .register-header {
            background: #333;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-body {
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
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            color: white;
            font-weight: bold;
            width: 100%;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .register-links {
            text-align: center;
            margin-top: 20px;
        }
        .register-links a {
            color: #667eea;
            text-decoration: none;
        }
        .register-links a:hover {
            text-decoration: underline;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        @media (max-width: 576px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h3><i class="fas fa-cut"></i> BarbeX</h3>
                <p>Crear Cuenta</p>
            </div>
            <div class="register-body">
                <?php echo $message; ?>

                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" class="form-control" name="name" placeholder="Nombre completo *" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" class="form-control" name="phone" placeholder="Teléfono">
                        </div>
                    </div>

                    <div class="form-group">
                        <input type="email" class="form-control" name="email" placeholder="Correo electrónico *" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <input type="password" class="form-control" name="password" placeholder="Contraseña *" required>
                        </div>
                        <div class="form-group">
                            <input type="password" class="form-control" name="confirm_password" placeholder="Confirmar contraseña *" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-user-plus"></i> Crear Cuenta
                    </button>
                </form>

                <div class="register-links">
                    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
                    <p><a href="index.html">← Volver al inicio</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</body>
</html>