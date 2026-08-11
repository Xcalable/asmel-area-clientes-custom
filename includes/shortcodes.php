<?php
// Todos los Shortcodes del Frontend

// Registrar todos los shortcodes
add_action('init', 'asmel_register_all_shortcodes');
/**
 * Ejecuta register all shortcodes.
 * @return mixed Resultado de la funcion.
 */
function asmel_register_all_shortcodes() {
    add_shortcode('asmel_welcome_message', 'asmel_welcome_message_shortcode');
    add_shortcode('asmel_logout_button', 'asmel_logout_button_shortcode');
    add_shortcode('asmel_cliente_info', 'asmel_cliente_info_shortcode');
}

// Shortcode para mensaje de bienvenida
/**
 * Ejecuta welcome message shortcode.
 *
 * @param mixed $atts Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_welcome_message_shortcode($atts) {
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        return '';
    }
    
    $current_user = wp_get_current_user();
    $empresa = get_user_meta($current_user->ID, 'empresa', true);
    
    if (!$empresa) {
        $empresa = $current_user->display_name;
    }
    
    return '<div class="welcome-message">Bienvenido <strong>' . esc_html($empresa) . '</strong> al area de clientes de Clinica Asmel</div>';
}

// Shortcode para botón de cerrar sesión
/**
 * Ejecuta logout button shortcode.
 *
 * @param mixed $atts Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_logout_button_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '';
    }
    
    $logout_url = wp_logout_url(home_url('/'));
    
    return '<a href="' . esc_url($logout_url) . '" class="logout-button">Cerrar Sesión</a>';
}

// Shortcode para información del cliente logueado
/**
 * Ejecuta cliente info shortcode.
 *
 * @param mixed $atts Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_cliente_info_shortcode($atts) {
    $atts = shortcode_atts(array(
        'field' => 'empresa' // empresa, cuit, email, numero_cliente
    ), $atts);
    
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        return '';
    }
    
    $current_user = wp_get_current_user();
    
    switch($atts['field']) {
        case 'empresa':
            return get_user_meta($current_user->ID, 'empresa', true);
        case 'cuit':
            return get_user_meta($current_user->ID, 'cuit', true);
        case 'email':
            return $current_user->user_email;
        case 'numero_cliente':
            return $current_user->user_login; // El user_login es el número de cliente
        case 'pacientes_count':
            // Aquí conectaríamos con la base de datos externa
            return asmel_get_pacientes_count($current_user->user_login);
    }
    
    return '';
}

// Función para obtener número de pacientes (conexión externa)
/**
 * Ejecuta get pacientes count.
 *
 * @param mixed $numero_cliente Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_get_pacientes_count($numero_cliente) {
    // Esta función se implementará cuando conectemos con la base de datos externa
    // Retorna un valor de respaldo cuando no hay conexion disponible.
    return '0'; // Valor de respaldo
}

// Shortcode para formulario de login
/**
 * Ejecuta login form shortcode.
 *
 * @param mixed $atts Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_login_form_shortcode($atts) {
    // Verificar si ya está logueado
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (current_user_can('cliente')) {
            // Verificar si necesita primer login
            if (get_user_meta($current_user->ID, 'force_password_change', true) || 
                get_user_meta($current_user->ID, 'needs_email_update', true)) {
                // Usar JavaScript para redirección para evitar headers already sent
                echo '<script>window.location.href = "' . home_url('/primer-login/') . '";</script>';
                return '<p>Redirigiendo...</p>';
            } else {
                echo '<script>window.location.href = "' . home_url('/dashboard-clientes/') . '";</script>';
                return '<p>Redirigiendo...</p>';
            }
        }
        return '<p>Ya estás logueado.</p>';
    }

    $output = '';
    
    // Mostrar mensajes de error
    if (isset($_GET['login']) && $_GET['login'] === 'failed') {
        $output .= '<div class="asmel-error">Usuario o contraseña incorrectos.</div>';
    }
    
    if (isset($_GET['login']) && $_GET['login'] === 'empty') {
        $output .= '<div class="asmel-error">Por favor completa todos los campos.</div>';
    }
    
    if (isset($_GET['login']) && $_GET['login'] === 'pending') {
        $output .= '<div class="asmel-error">Tu cuenta está pendiente de aprobación.</div>';
    }

    ob_start();
    ?>
    <div class="asmel-login-form">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="asmel_login">
            <?php wp_nonce_field('asmel_login_nonce', 'login_nonce'); ?>
            
            <div class="form-group">
                <input type="text" name="username" id="username" placeholder="Número de Cliente o Usuario:" required>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="Contraseña:" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Acceder" class="button">
            </div>
        </form>
        
        <div class="login-links">
            <a href="<?php echo home_url('/registro-clientes/'); ?>">Crear una cuenta cliente</a> | 
            <a href="<?php echo home_url('/olvide-contrasena/'); ?>">¿Olvidaste contraseña?</a>
        </div>
    </div>
    <?php
    $output .= ob_get_clean();
    
    return $output;
}

// Shortcode para formulario de primer login
/**
 * Ejecuta primer login form shortcode.
 *
 * @param mixed $atts Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_primer_login_form_shortcode($atts) {
    // Verificar si está logueado
    if (!is_user_logged_in()) {
        echo '<script>window.location.href = "' . home_url('/') . '";</script>';
        return '<p>Redirigiendo...</p>';
    }
    
    $current_user = wp_get_current_user();
    
    // Verificar si es cliente, administrador o desarrollador
    if (!current_user_can('cliente') && !current_user_can('administrator') && !current_user_can('desarrollador')) {
        echo '<script>window.location.href = "' . home_url('/') . '";</script>';
        return '<p>Redirigiendo...</p>';
    }
    
    // Verificar si necesita primer login
    if (!get_user_meta($current_user->ID, 'force_password_change', true) && 
        !get_user_meta($current_user->ID, 'needs_email_update', true)) {
        if (current_user_can('cliente')) {
            echo '<script>window.location.href = "' . home_url('/dashboard-clientes/') . '";</script>';
        } else {
            echo '<script>window.location.href = "' . admin_url() . '";</script>';
        }
        return '<p>Redirigiendo...</p>';
    }
    
    $output = '';
    
    // Mostrar mensajes de error
    if (isset($_GET['error']) && $_GET['error'] === 'empty') {
        $output .= '<div class="asmel-error">Por favor completa todos los campos.</div>';
    }
    
    if (isset($_GET['error']) && $_GET['error'] === 'password') {
        $output .= '<div class="asmel-error">La contraseña no cumple con los requisitos de seguridad.</div>';
    }
    
    if (isset($_GET['error']) && $_GET['error'] === 'email') {
        $output .= '<div class="asmel-error">El email no es válido.</div>';
    }
    
    if (isset($_GET['success']) && $_GET['success'] === 'true') {
        $output .= '<div class="asmel-success">Datos actualizados correctamente. Redirigiendo...</div>';
        if (current_user_can('cliente')) {
            $output .= '<script>setTimeout(function(){ window.location.href = "' . home_url('/dashboard-clientes/') . '"; }, 2000);</script>';
        } else {
            $output .= '<script>setTimeout(function(){ window.location.href = "' . admin_url() . '"; }, 2000);</script>';
        }
        return $output; // Retornar aquí para no mostrar el formulario
    }

    ob_start();
    ?>
    <div class="asmel-primer-login-form">
        <h3>Actualización de Datos</h3>
        <p>Hola <strong><?php echo esc_html($current_user->display_name); ?></strong>. Por favor actualiza tu email y contraseña para continuar.</p>
        
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="asmel_primer_login">
            <?php wp_nonce_field('asmel_primer_login_nonce', 'primer_login_nonce'); ?>
            
            <div class="form-group">
                <input type="email" name="email" id="email" placeholder="Email (obligatorio):" required value="<?php echo esc_attr($current_user->user_email); ?>">
            </div>
            
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="Nueva Contraseña (obligatoria):" required>
                <small>La contraseña debe tener al menos 8 caracteres.</small>
            </div>
            
            <div class="form-group">
                <input type="password" name="password_confirm" id="password_confirm" placeholder="Confirmar Contraseña:" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Actualizar Datos" class="button">
            </div>
        </form>
    </div>
    <?php
    $output .= ob_get_clean();
    
    return $output;
}

// Shortcode para formulario de registro
/**
 * Ejecuta register form shortcode.
 *
 * @param mixed $atts Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_register_form_shortcode($atts) {
    // Verificar si ya está logueado
    if (is_user_logged_in()) {
        return '<p>Ya estás registrado y logueado.</p>';
    }

    // Mostrar mensajes
    if (isset($_GET['register']) && $_GET['register'] === 'success') {
        $output = '<div class="asmel-success">Registro enviado. Un administrador revisará tu solicitud.</div>';
        $output .= '<div class="login-links"><a href="' . home_url('/') . '">← Volver al inicio de sesión</a></div>';
        return $output;
    }
    
    if (isset($_GET['register']) && $_GET['register'] === 'failed') {
        $output = '<div class="asmel-error">Error en el registro. Por favor intenta nuevamente.</div>';
    } elseif (isset($_GET['register']) && $_GET['register'] === 'empty') {
        $output = '<div class="asmel-error">Por favor completa todos los campos.</div>';
    } elseif (isset($_GET['register']) && $_GET['register'] === 'exists') {
        $output = '<div class="asmel-error">El nombre de usuario o email ya están en uso.</div>';
    } else {
        $output = '';
    }

    ob_start();
    ?>
    <div class="asmel-register-form">
        <h3>Registro de Nuevo Cliente</h3>
        <p>Completa el formulario para solicitar tu registro como cliente.</p>
        
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="asmel_register">
            <?php wp_nonce_field('asmel_register_nonce', 'register_nonce'); ?>
            
            <div class="form-group">
                <input type="text" name="username" id="username" placeholder="Usuario (obligatorio):" required value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <input type="text" name="empresa" id="empresa" placeholder="Nombre de la Empresa (obligatorio):" required value="<?php echo isset($_POST['empresa']) ? esc_attr($_POST['empresa']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <input type="email" name="email" id="email" placeholder="Email (obligatorio):" required value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="Contraseña (obligatoria):" required>
                <small>La contraseña debe tener al menos 8 caracteres.</small>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Enviar Solicitud" class="button">
            </div>
        </form>
    </div>
    <?php
    $output .= ob_get_clean();
    
    return $output;
}

// Shortcode para formulario de recuperación de contraseña
/**
 * Ejecuta forgot password form shortcode.
 *
 * @param mixed $atts Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_forgot_password_form_shortcode($atts) {
    // Verificar si ya está logueado
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (current_user_can('cliente') || current_user_can('administrator') || current_user_can('desarrollador')) {
            // Verificar si necesita primer login
            if (get_user_meta($current_user->ID, 'force_password_change', true) || 
                get_user_meta($current_user->ID, 'needs_email_update', true)) {
                wp_redirect(home_url('/primer-login/'));
                exit;
            } else {
                if (current_user_can('cliente')) {
                    wp_redirect(home_url('/dashboard-clientes/'));
                } else {
                    wp_redirect(admin_url());
                }
                exit;
            }
        }
    }

    $output = '';
    
    // Mostrar mensajes
    if (isset($_GET['forgot']) && $_GET['forgot'] === 'success') {
        return '<div class="asmel-success">Se ha enviado un email con instrucciones para recuperar tu contraseña.</div>';
    }
    
    if (isset($_GET['forgot']) && $_GET['forgot'] === 'failed') {
        $output .= '<div class="asmel-error">No se encontró ninguna cuenta con ese email o nombre de usuario.</div>';
    }
    
    if (isset($_GET['forgot']) && $_GET['forgot'] === 'empty') {
        $output .= '<div class="asmel-error">Por favor ingresa tu nombre de usuario o email.</div>';
    }

    ob_start();
    ?>
    <div class="asmel-forgot-password-form">
        <h3>Recuperar Contraseña</h3>
        <p>Ingresa tu nombre de usuario o email para recibir instrucciones de recuperación.</p>
        
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="asmel_forgot_password">
            <?php wp_nonce_field('asmel_forgot_password_nonce', 'forgot_password_nonce'); ?>
            
            <div class="form-group">
                <input type="text" name="user_login" id="user_login" placeholder="Nombre de usuario o Email:" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Recuperar Contraseña" class="button">
            </div>
        </form>
        
        <div class="login-links">
            <a href="<?php echo home_url('/'); ?>">← Volver al inicio de sesión</a>
        </div>
    </div>
    <?php
    $output .= ob_get_clean();
    
    return $output;
}

// Shortcode para formulario de restablecimiento de contraseña
/**
 * Ejecuta reset password form shortcode.
 *
 * @param mixed $atts Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_reset_password_form_shortcode($atts) {
    // Verificar si ya está logueado
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (current_user_can('cliente') || current_user_can('administrator') || current_user_can('desarrollador')) {
            // Verificar si necesita primer login
            if (get_user_meta($current_user->ID, 'force_password_change', true) || 
                get_user_meta($current_user->ID, 'needs_email_update', true)) {
                wp_redirect(home_url('/primer-login/'));
                exit;
            } else {
                if (current_user_can('cliente')) {
                    wp_redirect(home_url('/dashboard-clientes/'));
                } else {
                    wp_redirect(admin_url());
                }
                exit;
            }
        }
    }
    
    // Verificar parámetros de restablecimiento
    $key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
    $login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';
    $reset_success = isset($_GET['reset']) && $_GET['reset'] === 'success';
    
    // Si ya se restableció la contraseña con éxito, mostrar mensaje de éxito
    if ($reset_success) {
        return '<div class="asmel-success">¡Contraseña actualizada correctamente! <a href="' . home_url('/') . '">Haz clic aquí para iniciar sesión</a></div>';
    }
    
    if (empty($key) || empty($login)) {
        return '<div class="asmel-error">Enlace de restablecimiento inválido.</div>';
    }
    
    // Verificar la clave de restablecimiento
    $user = check_password_reset_key($key, $login);
    
    if (is_wp_error($user)) {
        return '<div class="asmel-error">El enlace de restablecimiento ha expirado o es inválido.</div>';
    }
    
    // Verificar que el usuario tenga rol cliente, administrador o desarrollador
    if (!in_array('cliente', $user->roles) && !in_array('administrator', $user->roles) && !in_array('desarrollador', $user->roles)) {
        return '<div class="asmel-error">Acceso denegado.</div>';
    }
    
    $output = '';
    
    // Mostrar mensajes de error
    if (isset($_GET['reset']) && $_GET['reset'] === 'failed') {
        $output .= '<div class="asmel-error">Error al actualizar la contraseña. Por favor intenta nuevamente.</div>';
    }
    
    if (isset($_GET['reset']) && $_GET['reset'] === 'mismatch') {
        $output .= '<div class="asmel-error">Las contraseñas no coinciden.</div>';
    }
    
    if (isset($_GET['reset']) && $_GET['reset'] === 'invalid') {
        $output .= '<div class="asmel-error">La contraseña no cumple con los requisitos de seguridad.</div>';
    }

    ob_start();
    ?>
    <div class="asmel-reset-password-form">
        <h3>Restablecer Contraseña</h3>
        <p>Hola <strong><?php echo esc_html($user->display_name); ?></strong>, ingresa tu nueva contraseña.</p>
        
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="asmel_reset_password">
            <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
            <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
            <?php wp_nonce_field('asmel_reset_password_nonce', 'reset_password_nonce'); ?>
            
            <div class="form-group">
                <input type="password" name="password" id="password" placeholder="Nueva Contraseña:" required>
                <small>La contraseña debe tener al menos 8 caracteres.</small>
            </div>
            
            <div class="form-group">
                <input type="password" name="password_confirm" id="password_confirm" placeholder="Confirmar Contraseña:" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Actualizar Contraseña" class="button">
            </div>
        </form>
        
        <div class="login-links">
            <a href="<?php echo home_url('/'); ?>">← Volver al inicio de sesión</a>
        </div>
    </div>
    <?php
    $output .= ob_get_clean();
    
    return $output;
}


