<?php
// Configuración para Desarrolladores

// Agregar página de configuración para desarrolladores
add_action('admin_menu', 'asmel_add_developer_settings');
/**
 * Ejecuta add developer settings.
 * @return mixed Resultado de la funcion.
 */
function asmel_add_developer_settings() {
    if (current_user_can('desarrollador')) {
        add_menu_page(
            'Configuración Asmel',
            'Configuración Asmel',
            'manage_options',
            'asmel-config',
            'asmel_developer_config_page',
            'dashicons-admin-generic',
            2
        );
    }
}

// Página de configuración para desarrolladores
/**
 * Ejecuta developer config page.
 * @return mixed Resultado de la funcion.
 */
function asmel_developer_config_page() {
    if (!current_user_can('desarrollador')) {
        wp_die('No tienes permisos para acceder a esta página.');
    }

    global $menu;

    $menu_options = array(
        'index.php' => 'Escritorio',
        'users.php' => 'Usuarios',
        'profile.php' => 'Perfil',
    );

    foreach ($menu as $item) {
        if (empty($item[2])) {
            continue;
        }

        $menu_slug = $item[2];
        if (strpos($menu_slug, 'separator') === 0 || $menu_slug === 'asmel-config') {
            continue;
        }

        $menu_label = wp_strip_all_tags(html_entity_decode($item[0], ENT_QUOTES, 'UTF-8'));
        $menu_label = trim(preg_replace('/\s+\d+$/', '', $menu_label));

        if ($menu_label === '') {
            continue;
        }

        $menu_options[$menu_slug] = $menu_label;
    }

    if (isset($menu_options['tools.php'])) {
        $menu_options['tools.php'] = 'Herramientas (incluye Test Sync Asmel)';
    }
    
    // Procesar formulario de guardado
    if (isset($_POST['save_config']) && wp_verify_nonce($_POST['asmel_config_nonce'], 'save_asmel_config')) {
        $admins = get_users(array('role' => 'administrator'));
        foreach ($admins as $admin) {
            $allowed_menu_slugs = isset($_POST['allowed_cpts_' . $admin->ID]) ? array_map('sanitize_text_field', wp_unslash($_POST['allowed_cpts_' . $admin->ID])) : array();
            update_user_meta($admin->ID, 'allowed_cpts', asmel_normalize_allowed_menu_slugs($allowed_menu_slugs));
        }
        echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
    }
    
    $admins = get_users(array('role' => 'administrator'));
    ?>
    <div class="wrap">
        <h1>Configuración de Permisos para Administradores</h1>
        <p>Selecciona qué menús reales del panel pueden ver los administradores.</p>
        
        <form method="post">
            <?php wp_nonce_field('save_asmel_config', 'asmel_config_nonce'); ?>
            
            <?php foreach ($admins as $admin): ?>
                <?php $allowed_cpts = asmel_normalize_allowed_menu_slugs(get_user_meta($admin->ID, 'allowed_cpts', true)); ?>
                
                <h3><?php echo esc_html($admin->display_name); ?> (<?php echo esc_html($admin->user_login); ?>)</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Menús Permitidos</th>
                        <td>
                            <?php foreach ($menu_options as $menu_slug => $menu_label): ?>
                                <label><input type="checkbox" name="allowed_cpts_<?php echo $admin->ID; ?>[]" value="<?php echo esc_attr($menu_slug); ?>" <?php checked(in_array($menu_slug, $allowed_cpts, true)); ?>> <?php echo esc_html($menu_label); ?></label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <hr>
            <?php endforeach; ?>
            
            <p class="submit">
                <input type="submit" name="save_config" class="button-primary" value="Guardar Configuración">
            </p>
        </form>
    </div>
    <?php
}


