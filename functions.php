<?php
// Evitar acceso directo
if (!defined('ABSPATH')) exit;

// Cargar todos los archivos de includes
require_once get_stylesheet_directory() . '/includes/login-system.php';
require_once get_stylesheet_directory() . '/includes/user-management.php';
require_once get_stylesheet_directory() . '/includes/shortcodes.php';
require_once get_stylesheet_directory() . '/includes/dashboard-functions.php';
require_once get_stylesheet_directory() . '/includes/developer-config.php';
require_once get_stylesheet_directory() . '/includes/cpt-informe.php';
require_once get_stylesheet_directory() . '/includes/document-converter.php';
require_once get_stylesheet_directory() . '/includes/ajax-file-upload.php';
require_once get_stylesheet_directory() . '/includes/client-dashboard.php';
require_once get_stylesheet_directory() . '/includes/automation-manager.php'; // Automatizacion DBF/Docs

// Cargar estilos y scripts
add_action('wp_enqueue_scripts', 'asmel_child_enqueue_styles');
/**
 * Ejecuta child enqueue styles.
 * @return mixed Resultado de la función.
 */
function asmel_child_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));
    
    // Cargar estilos personalizados para el area de clientes si es necesario
    if (is_user_logged_in() && (is_page('dashboard-clientes') || is_page('dashboard-clientes-archivos') || is_page('dashboard-clientes-informes') || is_page('dashboard-clientes-comprobantes') || is_page('dashboard-clientes-pacientes') || is_page('dashboard-clientes-perfil-de-empresa'))) {
        wp_enqueue_style('custom-dashboard', get_stylesheet_directory_uri() . '/assets/css/custom-dashboard.css');
        wp_enqueue_script(
            'asmel-dashboard-navigation',
            get_stylesheet_directory_uri() . '/assets/js/dashboard-navigation.js',
            array(),
            '1.0.0',
            true
        );
    }
}

// Deshabilitar barra de administracion para todos los usuarios
add_action('after_setup_theme', 'remove_admin_bar_for_all_users');
/**
 * Ejecuta remove admin bar for all users.
 * @return mixed Resultado de la función.
 */
function remove_admin_bar_for_all_users() {
    show_admin_bar(false);
}

// Eliminar roles no necesarios
add_action('init', 'asmel_remove_unnecessary_roles');
/**
 * Ejecuta remove unnecessary roles.
 * @return mixed Resultado de la función.
 */
function asmel_remove_unnecessary_roles() {
    if (current_user_can('administrator') || current_user_can('desarrollador')) {
        // Solo el admin o desarrollador pueden eliminar roles
        $unnecessary_roles = array('subscriber', 'contributor', 'author', 'editor');
        
        foreach ($unnecessary_roles as $role) {
            if (get_role($role)) {
                remove_role($role);
            }
        }
    }
}

// Personalizar remitente de emails
add_filter('wp_mail_from', 'asmel_mail_from');
add_filter('wp_mail_from_name', 'asmel_mail_from_name');
/**
 * Ejecuta mail from.
 *
 * @param mixed $email Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_mail_from($email) {
    return 'clientes@example.com'; // Correo remitente de notificaciones
}

/**
 * Ejecuta mail from name.
 *
 * @param mixed $name Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_mail_from_name($name) {
    return 'Clinica Asmel';
}

// Forzar enctype="multipart/form-data" en el formulario de edicion del CPT "Informe"
add_action('admin_head-post-new.php', 'asmel_force_informe_enctype_js');
add_action('admin_head-post.php', 'asmel_force_informe_enctype_js');
/**
 * Ejecuta force informe enctype js.
 * @return mixed Resultado de la función.
 */
function asmel_force_informe_enctype_js() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type == 'informe') {
        echo '<script>
        jQuery(document).ready(function($) {
            // Forzar enctype en el formulario de edicion
            $("#post").attr("enctype", "multipart/form-data");
            
            // Asegurar que el boton de publicar no tenga eventos AJAX que interfieran
            $("#publish").off("click.publish");
            
            // Forzar submit tradicional del formulario
            $("#post").off("submit.wp-ajax-response").on("submit", function() {
                // Asegurar que el formulario tenga enctype correcto
                if ($(this).attr("enctype") !== "multipart/form-data") {
                    $(this).attr("enctype", "multipart/form-data");
                }
                return true;
            });
        });
        </script>';
    }
}

