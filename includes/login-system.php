<?php
// Sistema de Login, Registro y Recuperación de Contraseña

// Verificar si el usuario necesita restablecer contraseña (90 días)
add_action('wp_login', 'asmel_check_password_expiration', 10, 2);
/**
 * Ejecuta check password expiration.
 *
 * @param mixed $user_login Parametro de entrada.
 * @param mixed $user Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_check_password_expiration($user_login, $user) {
    if (user_can($user, 'cliente')) {
        $last_password_change = get_user_meta($user->ID, 'last_password_change', true);
        
        if (!$last_password_change) {
            // Si no hay registro, establecer la fecha actual
            update_user_meta($user->ID, 'last_password_change', time());
            return;
        }
        
        // Verificar si han pasado 90 días (7776000 segundos)
        $days_since_change = (time() - $last_password_change) / 86400;
        
        if ($days_since_change >= 90) {
            // Marcar para forzar cambio de contraseña
            update_user_meta($user->ID, 'force_password_change', 1);
            update_user_meta($user->ID, 'needs_email_update', 1);
            
            // Redirigir a primer login
            if (!defined('DOING_AJAX') || !DOING_AJAX) {
                wp_redirect(home_url('/primer-login/'));
                exit;
            }
        }
    }
}

// Prevenir acceso a usuarios pendientes de aprobación
add_action('wp', 'asmel_check_pending_approval');
/**
 * Ejecuta check pending approval.
 * @return mixed Resultado de la funcion.
 */
function asmel_check_pending_approval() {
    if (is_user_logged_in() && current_user_can('cliente')) {
        $user_id = get_current_user_id();
        if (get_user_meta($user_id, 'pending_approval', true)) {
            // Verificar si está en páginas permitidas
            if (!is_page(array('inicio', 'primer-login'))) {
                wp_logout();
                wp_redirect(home_url('/?login=pending'));
                exit;
            }
        }
    }
}

// Registrar shortcodes de login
add_action('init', 'asmel_register_login_shortcodes');
/**
 * Ejecuta register login shortcodes.
 * @return mixed Resultado de la funcion.
 */
function asmel_register_login_shortcodes() {
    add_shortcode('asmel_login_form', 'asmel_login_form_shortcode');
    add_shortcode('asmel_register_form', 'asmel_register_form_shortcode');
    add_shortcode('asmel_forgot_password_form', 'asmel_forgot_password_form_shortcode');
    add_shortcode('asmel_reset_password_form', 'asmel_reset_password_form_shortcode');
    add_shortcode('asmel_primer_login_form', 'asmel_primer_login_form_shortcode');
}

// Procesar login
add_action('admin_post_asmel_login', 'asmel_process_login');
add_action('admin_post_nopriv_asmel_login', 'asmel_process_login');
/**
 * Ejecuta process login.
 * @return mixed Resultado de la funcion.
 */
function asmel_process_login() {
    // Verificar nonce
    if (!isset($_POST['login_nonce']) || !wp_verify_nonce($_POST['login_nonce'], 'asmel_login_nonce')) {
        wp_die('Error de seguridad.');
    }
    
    // Validar campos
    if (empty($_POST['username']) || empty($_POST['password'])) {
        wp_redirect(add_query_arg('login', 'empty', wp_get_referer()));
        exit;
    }
    
    $username = sanitize_user($_POST['username']);
    $password = $_POST['password'];
    
    // Autenticar usuario
    $user = wp_authenticate($username, $password);
    
    if (is_wp_error($user)) {
        wp_redirect(add_query_arg('login', 'failed', wp_get_referer()));
        exit;
    }
    
    // Verificar si es cliente y está pendiente de aprobación
    if (user_can($user, 'cliente')) {
        if (get_user_meta($user->ID, 'pending_approval', true)) {
            wp_clear_auth_cookie();
            wp_redirect(add_query_arg('login', 'pending', wp_get_referer()));
            exit;
        }
    }
    
    // Iniciar sesión
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);
    
    // Verificar si necesita primer login (excluyendo administradores)
    if (!user_can($user, 'administrator') && (get_user_meta($user->ID, 'force_password_change', true) || 
        get_user_meta($user->ID, 'needs_email_update', true))) {
        // Usar JavaScript para evitar headers already sent
        echo '<script>window.location.href = "' . home_url('/primer-login/') . '";</script>';
        exit;
    } else {
        // Redirigir según el rol
        if (current_user_can('cliente')) {
            echo '<script>window.location.href = "' . home_url('/dashboard-clientes/') . '";</script>';
        } else {
            // Administradores y desarrolladores van al dashboard de WP
            echo '<script>window.location.href = "' . admin_url() . '";</script>';
        }
        exit;
    }
}

// Procesar primer login
add_action('admin_post_asmel_primer_login', 'asmel_process_primer_login');
/**
 * Ejecuta process primer login.
 * @return mixed Resultado de la funcion.
 */
function asmel_process_primer_login() {
    // Verificar si está logueado
    if (!is_user_logged_in()) {
        echo '<script>window.location.href = "' . home_url('/') . '";</script>';
        exit;
    }
    
    // Verificar nonce
    if (!isset($_POST['primer_login_nonce']) || !wp_verify_nonce($_POST['primer_login_nonce'], 'asmel_primer_login_nonce')) {
        wp_die('Error de seguridad.');
    }
    
    $current_user = wp_get_current_user();
    
    // Validar campos
    if (empty($_POST['email']) || empty($_POST['password']) || empty($_POST['password_confirm'])) {
        echo '<script>window.location.href = "' . add_query_arg('error', 'empty', wp_get_referer()) . '";</script>';
        exit;
    }
    
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Validar email
    if (!is_email($email)) {
        echo '<script>window.location.href = "' . add_query_arg('error', 'email', wp_get_referer()) . '";</script>';
        exit;
    }
    
    // Validar contraseña
    if ($password !== $password_confirm) {
        echo '<script>window.location.href = "' . add_query_arg('error', 'password', wp_get_referer()) . '";</script>';
        exit;
    }
    
    if (strlen($password) < 8) {
        echo '<script>window.location.href = "' . add_query_arg('error', 'password', wp_get_referer()) . '";</script>';
        exit;
    }
    
    // Actualizar email y display_name
    wp_update_user(array(
        'ID' => $current_user->ID,
        'user_email' => $email,
        'display_name' => get_user_meta($current_user->ID, 'empresa', true) // Usar empresa como display_name
    ));
    
    // Actualizar contraseña
    wp_set_password($password, $current_user->ID);
    
    // Actualizar fecha de cambio de contraseña
    update_user_meta($current_user->ID, 'last_password_change', time());
    
    // Eliminar flags
    delete_user_meta($current_user->ID, 'force_password_change');
    delete_user_meta($current_user->ID, 'needs_email_update');
    
    // Refrescar cookie con nueva contraseña
    wp_set_auth_cookie($current_user->ID);
    
    // Redirigir con éxito
    if (current_user_can('cliente')) {
        echo '<script>window.location.href = "' . add_query_arg('success', 'true', wp_get_referer()) . '";</script>';
    } else {
        echo '<script>window.location.href = "' . admin_url() . '";</script>';
    }
    exit;
}

// Procesar registro
add_action('admin_post_asmel_register', 'asmel_process_register');
add_action('admin_post_nopriv_asmel_register', 'asmel_process_register');
/**
 * Ejecuta process register.
 * @return mixed Resultado de la funcion.
 */
function asmel_process_register() {
    // Verificar nonce
    if (!isset($_POST['register_nonce']) || !wp_verify_nonce($_POST['register_nonce'], 'asmel_register_nonce')) {
        wp_die('Error de seguridad.');
    }
    
    // Validar campos
    if (empty($_POST['username']) || empty($_POST['empresa']) || 
        empty($_POST['email']) || empty($_POST['password'])) {
        wp_redirect(add_query_arg('register', 'empty', wp_get_referer()));
        exit;
    }
    
    $username = sanitize_user($_POST['username']);
    $empresa = sanitize_text_field($_POST['empresa']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    
    // Validar email
    if (!is_email($email)) {
        wp_redirect(add_query_arg('register', 'failed', wp_get_referer()));
        exit;
    }
    
    // Validar contraseña
    if (strlen($password) < 8) {
        wp_redirect(add_query_arg('register', 'failed', wp_get_referer()));
        exit;
    }
    
    // Verificar si el usuario o email ya existen
    if (username_exists($username) || email_exists($email)) {
        wp_redirect(add_query_arg('register', 'exists', wp_get_referer()));
        exit;
    }
    
    // Crear usuario pendiente de aprobación
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        error_log('Error al crear usuario: ' . $user_id->get_error_message());
        wp_redirect(add_query_arg('register', 'failed', wp_get_referer()));
        exit;
    }
    
    // Asignar rol cliente
    $user = new WP_User($user_id);
    $user->set_role('cliente');
    
    // Guardar datos adicionales
    update_user_meta($user_id, 'empresa', $empresa);
    update_user_meta($user_id, 'pending_approval', true);
    update_user_meta($user_id, 'first_name', $empresa); // También guardar en first_name
    update_user_meta($user_id, 'last_password_change', time()); // Registrar fecha de cambio de contraseña
    
    // Actualizar display_name
    $update_result = wp_update_user(array(
        'ID' => $user_id,
        'display_name' => $empresa
    ));
    
    if (is_wp_error($update_result)) {
        error_log('Error al actualizar display_name: ' . $update_result->get_error_message());
    }
    
    // Enviar notificación al administrador
    $notify_result = asmel_notify_admin_new_registration($user_id);
    if (!$notify_result) {
        error_log('Error al enviar notificación al administrador');
    }
    
    // Redirigir con éxito
    wp_redirect(add_query_arg('register', 'success', wp_get_referer()));
    exit;
}

// Notificar al administrador sobre nuevo registro
/**
 * Ejecuta notify admin new registration.
 *
 * @param mixed $user_id Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_notify_admin_new_registration($user_id) {
    $user = get_userdata($user_id);
    $empresa = get_user_meta($user_id, 'empresa', true);
    
    // Obtener el email del primer administrador
    $admins = get_users(array('role' => 'administrator', 'number' => 1));
    if (!empty($admins)) {
        $admin_email = $admins[0]->user_email;
    } else {
        $admin_email = get_option('admin_email');
    }
    
    $subject = 'Nuevo registro de cliente pendiente de aprobación - Asmel';
    
    $message = "
    Nuevo cliente registrado y pendiente de aprobación:
    
    Usuario: {$user->user_login}
    Empresa: {$empresa}
    Email: {$user->user_email}
    
    Para aprobar este cliente, ve a:
    " . admin_url('users.php') . "
    
    Saludos,
    Clínica Asmel
    ";
    
    // Usar wp_mail con headers adecuados
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    return wp_mail($admin_email, $subject, $message, $headers);
}

// Procesar recuperación de contraseña
add_action('admin_post_asmel_forgot_password', 'asmel_process_forgot_password');
add_action('admin_post_nopriv_asmel_forgot_password', 'asmel_process_forgot_password');
/**
 * Ejecuta process forgot password.
 * @return mixed Resultado de la funcion.
 */
function asmel_process_forgot_password() {
    // Verificar nonce
    if (!isset($_POST['forgot_password_nonce']) || !wp_verify_nonce($_POST['forgot_password_nonce'], 'asmel_forgot_password_nonce')) {
        wp_die('Error de seguridad.');
    }
    
    // Validar campo
    if (empty($_POST['user_login'])) {
        wp_redirect(add_query_arg('forgot', 'empty', wp_get_referer()));
        exit;
    }
    
    $user_login = sanitize_text_field($_POST['user_login']);
    
    // Buscar usuario por nombre de usuario o email
    if (is_email($user_login)) {
        $user = get_user_by('email', $user_login);
    } else {
        $user = get_user_by('login', $user_login);
    }
    
    // Verificar si el usuario existe y tiene rol cliente
    if (!$user || (!in_array('cliente', $user->roles) && !in_array('administrator', $user->roles) && !in_array('desarrollador', $user->roles))) {
        wp_redirect(add_query_arg('forgot', 'failed', wp_get_referer()));
        exit;
    }
    
    // Generar clave de restablecimiento
    $key = get_password_reset_key($user);
    
    if (is_wp_error($key)) {
        wp_redirect(add_query_arg('forgot', 'failed', wp_get_referer()));
        exit;
    }
    
    // Enviar email de recuperación
    $sent = asmel_send_password_reset_email($user, $key);
    
    if (!$sent) {
        wp_redirect(add_query_arg('forgot', 'failed', wp_get_referer()));
        exit;
    }
    
    // Redirigir con éxito
    wp_redirect(add_query_arg('forgot', 'success', wp_get_referer()));
    exit;
}

// Enviar email de recuperación de contraseña
/**
 * Ejecuta send password reset email.
 *
 * @param mixed $user Parametro de entrada.
 * @param mixed $key Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_send_password_reset_email($user, $key) {
    $subject = 'Recuperar contraseña - Asmel';
    
    $reset_url = add_query_arg(
        array(
            'action' => 'rp',
            'key' => $key,
            'login' => rawurlencode($user->user_login)
        ),
        home_url('/restablecer-contrasena/')
    );
    
    $message = "
    Hola {$user->display_name},
    
    Has solicitado recuperar tu contraseña para el area de clientes de Asmel.
    
    Para restablecer tu contraseña, haz clic en el siguiente enlace:
    {$reset_url}
    
    Si no solicitaste este cambio, puedes ignorar este email.
    
    Saludos,
    Clínica Asmel
    ";
    
    // Usar wp_mail con headers adecuados
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    return wp_mail($user->user_email, $subject, $message, $headers);
}

// Procesar restablecimiento de contraseña
add_action('admin_post_asmel_reset_password', 'asmel_process_reset_password');
add_action('admin_post_nopriv_asmel_reset_password', 'asmel_process_reset_password');
/**
 * Ejecuta process reset password.
 * @return mixed Resultado de la funcion.
 */
function asmel_process_reset_password() {
    // Verificar nonce
    if (!isset($_POST['reset_password_nonce']) || !wp_verify_nonce($_POST['reset_password_nonce'], 'asmel_reset_password_nonce')) {
        wp_die('Error de seguridad.');
    }
    
    // Validar campos
    $key = isset($_POST['key']) ? sanitize_text_field($_POST['key']) : '';
    $login = isset($_POST['login']) ? sanitize_text_field($_POST['login']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    
    if (empty($key) || empty($login) || empty($password) || empty($password_confirm)) {
        wp_redirect(add_query_arg('reset', 'failed', wp_get_referer()));
        exit;
    }
    
    // Verificar que las contraseñas coincidan
    if ($password !== $password_confirm) {
        wp_redirect(add_query_arg('reset', 'mismatch', wp_get_referer()));
        exit;
    }
    
    // Validar contraseña
    if (strlen($password) < 8) {
        wp_redirect(add_query_arg('reset', 'invalid', wp_get_referer()));
        exit;
    }
    
    // Verificar la clave de restablecimiento
    $user = check_password_reset_key($key, $login);
    
    if (is_wp_error($user)) {
        wp_redirect(add_query_arg('reset', 'failed', wp_get_referer()));
        exit;
    }
    
    // Verificar que el usuario tenga rol cliente, administrador o desarrollador
    if (!in_array('cliente', $user->roles) && !in_array('administrator', $user->roles) && !in_array('desarrollador', $user->roles)) {
        wp_redirect(add_query_arg('reset', 'failed', wp_get_referer()));
        exit;
    }
    
    // Actualizar contraseña
    reset_password($user, $password);
    
    // Actualizar fecha de cambio de contraseña
    update_user_meta($user->ID, 'last_password_change', time());
    
    // Eliminar flags si existen
    delete_user_meta($user->ID, 'force_password_change');
    delete_user_meta($user->ID, 'needs_email_update');
    
    // Redirigir con éxito - Usar la página de restablecimiento como base
    // Importante: No incluir key y login en la redirección para evitar mostrar el formulario de nuevo
    $redirect_url = home_url('/restablecer-contrasena/');
    wp_redirect(add_query_arg('reset', 'success', $redirect_url));
    exit;
}

// Restringir acceso al Dashboard de WP para Clientes
add_action('admin_init', 'asmel_restrict_admin_access');
/**
 * Ejecuta restrict admin access.
 * @return mixed Resultado de la funcion.
 */
function asmel_restrict_admin_access() {
    // Si es un request AJAX, permitirlo (para funcionalidades del frontend que usen admin-ajax.php)
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    
    // Si el usuario es cliente, redirigir al dashboard de clientes
    if (current_user_can('cliente')) {
        wp_redirect(home_url('/dashboard-clientes/'));
        exit;
    }
}


