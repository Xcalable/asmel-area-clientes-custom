<?php
// Funciones del area de clientes

/**
 * Ejecuta get default allowed admin menu slugs.
 * @return mixed Resultado de la función.
 */
function asmel_get_default_allowed_admin_menu_slugs() {
    return array('index.php', 'users.php', 'profile.php');
}

/**
 * Ejecuta normalize allowed menu slugs.
 *
 * @param mixed $allowed_menu_slugs Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_normalize_allowed_menu_slugs($allowed_menu_slugs) {
    if (!is_array($allowed_menu_slugs)) {
        $allowed_menu_slugs = array();
    }

    $legacy_map = array(
        'dashboard' => 'index.php',
        'users' => 'users.php',
        'profile' => 'profile.php',
        'posts' => 'edit.php',
        'post' => 'edit.php',
        'pages' => 'edit.php?post_type=page',
        'page' => 'edit.php?post_type=page',
        'media' => 'upload.php',
        'comments' => 'edit-comments.php',
        'appearance' => 'themes.php',
        'plugins' => 'plugins.php',
        'tools' => 'tools.php',
        'settings' => 'options-general.php',
        'informe' => 'edit.php?post_type=informe',
    );

    $normalized = array();
    foreach ($allowed_menu_slugs as $menu_slug) {
        if (!is_string($menu_slug) || $menu_slug === '') {
            continue;
        }

        $normalized[] = isset($legacy_map[$menu_slug]) ? $legacy_map[$menu_slug] : $menu_slug;
    }

    if (empty($normalized)) {
        $normalized = asmel_get_default_allowed_admin_menu_slugs();
    }

    return array_values(array_unique($normalized));
}

/**
 * Ejecuta get current admin menu slug.
 * @return mixed Resultado de la función.
 */
function asmel_get_current_admin_menu_slug() {
    global $pagenow;

    if ($pagenow === 'profile.php') {
        return 'profile.php';
    }

    if ($pagenow === 'index.php' || $pagenow === 'users.php' || $pagenow === 'upload.php' || $pagenow === 'plugins.php' || $pagenow === 'themes.php' || $pagenow === 'tools.php' || $pagenow === 'options-general.php' || $pagenow === 'edit-comments.php' || $pagenow === 'edit.php') {
        if ($pagenow === 'edit.php' && !empty($_GET['post_type'])) {
            return 'edit.php?post_type=' . sanitize_key(wp_unslash($_GET['post_type']));
        }

        return $pagenow;
    }

    if (($pagenow === 'post.php' || $pagenow === 'post-new.php')) {
        $post_type = '';

        if (!empty($_GET['post_type'])) {
            $post_type = sanitize_key(wp_unslash($_GET['post_type']));
        } elseif (!empty($_GET['post'])) {
            $post_type = get_post_type((int) $_GET['post']);
        } elseif (!empty($_POST['post_ID'])) {
            $post_type = get_post_type((int) $_POST['post_ID']);
        }

        if ($post_type === 'page') {
            return 'edit.php?post_type=page';
        }

        if ($post_type && $post_type !== 'post') {
            return 'edit.php?post_type=' . $post_type;
        }

        return 'edit.php';
    }

    if ($pagenow === 'admin.php' && !empty($_GET['page'])) {
        return sanitize_key(wp_unslash($_GET['page']));
    }

    return '';
}

// Restringir menu del administrador segun el rol
// Prioridad alta para ejecutar despues de que otros plugins/temas agreguen sus menus.
add_action('admin_menu', 'asmel_restrict_admin_menu', 999);
/**
 * Ejecuta restrict admin menu.
 * @return mixed Resultado de la función.
 */
function asmel_restrict_admin_menu() {
    $current_user = wp_get_current_user();
    
    // Solo aplicar restricciones a administradores (no a desarrolladores)
    if (current_user_can('administrator') && !current_user_can('desarrollador')) {
        $allowed_menu_slugs = asmel_normalize_allowed_menu_slugs(get_user_meta($current_user->ID, 'allowed_cpts', true));
        $always_allowed = array('profile.php', 'separator1', 'separator2', 'separator-last');
        $allowed_lookup = array_fill_keys(array_merge($allowed_menu_slugs, $always_allowed), true);
        
        // Remover menus no permitidos
        global $menu;
        foreach ($menu as $item) {
            if (!isset($item[2])) continue;
            
            $menu_slug = $item[2];

            if (!isset($allowed_lookup[$menu_slug])) {
                remove_menu_page($menu_slug);
            }
        }
    }
}

add_action('admin_init', 'asmel_restrict_admin_page_access');
/**
 * Ejecuta restrict admin page access.
 * @return mixed Resultado de la función.
 */
function asmel_restrict_admin_page_access() {
    if (!is_admin() || wp_doing_ajax()) {
        return;
    }

    $current_user = wp_get_current_user();
    if (!current_user_can('administrator') || current_user_can('desarrollador')) {
        return;
    }

    $allowed_menu_slugs = asmel_normalize_allowed_menu_slugs(get_user_meta($current_user->ID, 'allowed_cpts', true));
    $current_menu_slug = asmel_get_current_admin_menu_slug();

    if ($current_menu_slug === '' || $current_menu_slug === 'profile.php') {
        return;
    }

    if (!in_array($current_menu_slug, $allowed_menu_slugs, true)) {
        wp_safe_redirect(admin_url());
        exit;
    }
}

