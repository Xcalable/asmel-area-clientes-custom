<?php
// Gestion de Usuarios, Roles y Aprobacion

// Agregar columna en lista de usuarios para mostrar estado de aprobacion
add_filter('manage_users_columns', 'asmel_add_user_approval_column');
/**
 * Ejecuta add user approval column.
 *
 * @param mixed $columns Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_add_user_approval_column($columns) {
    $columns['empresa'] = 'Empresa';
    $columns['approval_status'] = 'Estado de Aprobacion';
    return $columns;
}

add_action('manage_users_custom_column', 'asmel_show_user_approval_status', 10, 3);
/**
 * Ejecuta show user approval status.
 *
 * @param mixed $value Parámetro de entrada.
 * @param mixed $column_name Parámetro de entrada.
 * @param mixed $user_id Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_show_user_approval_status($value, $column_name, $user_id) {
    if ($column_name == 'approval_status') {
        if (get_user_meta($user_id, 'pending_approval', true)) {
            return '<span style="color: #dc3545; font-weight: bold;">Pendiente de Aprobacion</span>';
        } else {
            return '<span style="color: #28a745; font-weight: bold;">Aprobado</span>';
        }
    } elseif ($column_name == 'empresa') {
        return get_user_meta($user_id, 'empresa', true);
    }
    return $value;
}

// Agregar acciones personalizadas en la lista de usuarios (metodo alternativo)
add_filter('user_row_actions', 'asmel_user_row_actions', 10, 2);
/**
 * Ejecuta user row actions.
 *
 * @param mixed $actions Parámetro de entrada.
 * @param mixed $user Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_user_row_actions($actions, $user) {
    // Solo para usuarios con rol cliente
    if (in_array('cliente', $user->roles)) {
        // Verificar si esta pendiente de aprobacion
        if (get_user_meta($user->ID, 'pending_approval', true)) {
            $approve_url = wp_nonce_url(admin_url('admin-post.php?action=asmel_approve_user&user_id=' . $user->ID), 'approve_user_' . $user->ID);
            $actions['approve'] = '<a href="' . $approve_url . '" style="color: #28a745; font-weight: bold;">Aprobar</a>';
        } else {
            $disapprove_url = wp_nonce_url(admin_url('admin-post.php?action=asmel_disapprove_user&user_id=' . $user->ID), 'disapprove_user_' . $user->ID);
            $actions['disapprove'] = '<a href="' . $disapprove_url . '" style="color: #dc3545; font-weight: bold;">Desaprobar</a>';
        }
    }
    return $actions;
}

// Procesar aprobacion de usuario
add_action('admin_post_asmel_approve_user', 'asmel_approve_user');
/**
 * Ejecuta approve user.
 * @return mixed Resultado de la función.
 */
function asmel_approve_user() {
    // Verificar permisos
    if (!current_user_can('administrator') && !current_user_can('desarrollador')) {
        wp_die('No tienes permisos para realizar esta accion.');
    }
    
    // Verificar nonce
    $user_id = intval($_GET['user_id']);
    if (!wp_verify_nonce($_GET['_wpnonce'], 'approve_user_' . $user_id)) {
        wp_die('Error de seguridad.');
    }
    
    // Aprobar usuario
    delete_user_meta($user_id, 'pending_approval');
    
    // Enviar notificacion al usuario
    asmel_notify_user_approved($user_id);
    
    // Redirigir con mensaje de exito
    wp_redirect(add_query_arg('approved', 'true', admin_url('users.php')));
    exit;
}

// Procesar desaprobacion de usuario
add_action('admin_post_asmel_disapprove_user', 'asmel_disapprove_user');
/**
 * Ejecuta disapprove user.
 * @return mixed Resultado de la función.
 */
function asmel_disapprove_user() {
    // Verificar permisos
    if (!current_user_can('administrator') && !current_user_can('desarrollador')) {
        wp_die('No tienes permisos para realizar esta accion.');
    }
    
    // Verificar nonce
    $user_id = intval($_GET['user_id']);
    if (!wp_verify_nonce($_GET['_wpnonce'], 'disapprove_user_' . $user_id)) {
        wp_die('Error de seguridad.');
    }
    
    // Desaprobar usuario
    update_user_meta($user_id, 'pending_approval', true);
    
    // Redirigir con mensaje de exito
    wp_redirect(add_query_arg('disapproved', 'true', admin_url('users.php')));
    exit;
}

// Notificar al usuario que ha sido aprobado
/**
 * Ejecuta notify user approved.
 *
 * @param mixed $user_id Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_notify_user_approved($user_id) {
    $user = get_userdata($user_id);
    $empresa = get_user_meta($user_id, 'empresa', true);
    
    $subject = 'Tu cuenta ha sido aprobada - Asmel';
    
    $message = "
    Hola {$empresa},
    
    Tu cuenta de cliente en Asmel ha sido aprobada.
    
    Ya puedes acceder al area de clientes con tus credenciales:
    Usuario: {$user->user_login}
    Contrasena: (la que configuraste durante el registro)
    
    Accede aqui: " . home_url('/clientes/') . "
    
    Saludos,
    Clinica Asmel
    ";
    
    // Usar wp_mail con headers adecuados
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    return wp_mail($user->user_email, $subject, $message, $headers);
}

// Mostrar mensajes de exito en la lista de usuarios
add_action('admin_notices', 'asmel_user_approval_notices');
/**
 * Ejecuta user approval notices.
 * @return mixed Resultado de la función.
 */
function asmel_user_approval_notices() {
    global $pagenow;
    
    if ($pagenow == 'users.php') {
        if (isset($_GET['approved']) && $_GET['approved'] == 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>Usuario aprobado correctamente.</p></div>';
        }
        
        if (isset($_GET['disapproved']) && $_GET['disapproved'] == 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>Usuario desaprobado correctamente.</p></div>';
        }
    }
}

// Agregar filtro por estado de aprobacion en la lista de usuarios
add_action('restrict_manage_users', 'asmel_add_user_approval_filter');
/**
 * Ejecuta add user approval filter.
 * @return mixed Resultado de la función.
 */
function asmel_add_user_approval_filter() {
    $screen = get_current_screen();
    if ($screen->id !== 'users') return;
    
    $approval_status = isset($_GET['approval_status']) ? $_GET['approval_status'] : '';
    ?>
    <select name="approval_status">
        <option value="">Todos los estados</option>
        <option value="pending" <?php selected($approval_status, 'pending'); ?>>Pendientes de Aprobacion</option>
        <option value="approved" <?php selected($approval_status, 'approved'); ?>>Aprobados</option>
    </select>
    <?php
}

// Aplicar filtro por estado de aprobacion
add_action('pre_get_users', 'asmel_filter_users_by_approval_status');
/**
 * Ejecuta filter users by approval status.
 *
 * @param mixed $query Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_filter_users_by_approval_status($query) {
    global $pagenow;
    
    if (is_admin() && $pagenow == 'users.php' && isset($_GET['approval_status'])) {
        $approval_status = $_GET['approval_status'];
        
        if ($approval_status == 'pending') {
            $meta_query = array(
                array(
                    'key' => 'pending_approval',
                    'value' => '1',
                    'compare' => '='
                )
            );
        } elseif ($approval_status == 'approved') {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => 'pending_approval',
                    'value' => '1',
                    'compare' => '!='
                ),
                array(
                    'key' => 'pending_approval',
                    'compare' => 'NOT EXISTS'
                )
            );
        }
        
        if (isset($meta_query)) {
            $existing_meta_query = $query->get('meta_query');
            if (!empty($existing_meta_query)) {
                $meta_query = array_merge($existing_meta_query, $meta_query);
            }
            $query->set('meta_query', $meta_query);
        }
    }
}

// Asegurar que solo se muestren usuarios cliente
add_action('pre_get_users', 'asmel_filter_users_by_role_fix');
/**
 * Ejecuta filter users by role fix.
 *
 * @param mixed $query Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_filter_users_by_role_fix($query) {
    global $pagenow;
    
    if (is_admin() && $pagenow == 'users.php') {
        // Solo aplicar si no hay otros filtros activos
        if (!isset($_GET['role']) && !isset($_GET['s']) && !isset($_GET['approval_status'])) {
            $query->set('role', 'cliente');
        }
    }
}

// Agregar botones de accion en la columna de acciones (metodo alternativo)
add_filter('manage_users_columns', 'asmel_add_action_column');
/**
 * Ejecuta add action column.
 *
 * @param mixed $columns Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_add_action_column($columns) {
    $columns['user_actions'] = 'Acciones';
    return $columns;
}

add_action('manage_users_custom_column', 'asmel_show_user_actions', 10, 3);
/**
 * Ejecuta show user actions.
 *
 * @param mixed $value Parámetro de entrada.
 * @param mixed $column_name Parámetro de entrada.
 * @param mixed $user_id Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_show_user_actions($value, $column_name, $user_id) {
    if ($column_name == 'user_actions') {
        $user = get_userdata($user_id);
        if (in_array('cliente', $user->roles)) {
            if (get_user_meta($user_id, 'pending_approval', true)) {
                $approve_url = wp_nonce_url(admin_url('admin-post.php?action=asmel_approve_user&user_id=' . $user_id), 'approve_user_' . $user_id);
                return '<a href="' . $approve_url . '" class="button button-primary" style="margin-right: 5px;">Aprobar</a>';
            } else {
                $disapprove_url = wp_nonce_url(admin_url('admin-post.php?action=asmel_disapprove_user&user_id=' . $user_id), 'disapprove_user_' . $user_id);
                return '<a href="' . $disapprove_url . '" class="button button-secondary">Desaprobar</a>';
            }
        }
    }
    return $value;
}

