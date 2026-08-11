<?php
// Custom Post Type "Informe" para subir archivos .doc/.docx

// Encolar scripts y estilos para el admin de Informes
add_action('admin_enqueue_scripts', 'asmel_admin_informe_scripts');
/**
 * Ejecuta admin informe scripts.
 *
 * @param mixed $hook Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_admin_informe_scripts($hook) {
    global $post;
    
    // Solo cargar en la pantalla de edición/creación de 'informe'
    if (($hook === 'post-new.php' || $hook === 'post.php') && get_post_type($post) === 'informe') {
        
        // Encolar JS de subida AJAX
        wp_enqueue_script(
            'asmel-ajax-upload',
            get_stylesheet_directory_uri() . '/assets/js/ajax-upload.js',
            array('jquery'),
            filemtime(get_stylesheet_directory() . '/assets/js/ajax-upload.js'),
            true
        );
        
        // Localizar variables para JS
        wp_localize_script('asmel-ajax-upload', 'asmel_ajax_upload', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'upload_nonce' => wp_create_nonce('asmel_upload_informe_nonce'),
            'associate_nonce' => wp_create_nonce('asmel_associate_informe_nonce'),
            'post_id' => $post->ID
        ));
        
        // Estilos CSS para el uploader
        $custom_css = "
            .asmel-uploader-container {
                border: 2px dashed #b4b9be;
                padding: 20px;
                text-align: center;
                background: #f9f9f9;
                cursor: pointer;
                transition: border-color 0.3s;
            }
            .asmel-uploader-container:hover, .asmel-uploader-container.dragover {
                border-color: #0073aa;
                background: #fff;
            }
            #asmel-uploader-result.success { color: #46b450; font-weight: bold; margin-top: 10px; }
            #asmel-uploader-result.error { color: #dc3232; font-weight: bold; margin-top: 10px; }
            #asmel-uploader-progress { 
                margin-top: 15px; background: #ddd; border-radius: 3px; overflow: hidden; height: 20px; position: relative;
            }
            #asmel-uploader-progress-fill {
                background: #0073aa; height: 100%; width: 0%; transition: width 0.3s;
            }
            #asmel-uploader-progress-text {
                position: absolute; width: 100%; top: 0; left: 0; text-align: center; color: #fff; font-size: 12px; line-height: 20px; text-shadow: 0 0 2px rgba(0,0,0,0.5);
            }
        ";
        wp_add_inline_style('wp-admin', $custom_css);
    }
}

// Registrar el CPT "Informe"
add_action('init', 'asmel_register_informe_cpt');
/**
 * Ejecuta register informe cpt.
 * @return mixed Resultado de la funcion.
 */
function asmel_register_informe_cpt() {
    $labels = array(
        'name'                  => 'Informes',
        'singular_name'         => 'Informe',
        'menu_name'             => 'Informes',
        'name_admin_bar'        => 'Informe',
        'archives'              => 'Archivo de Informes',
        'attributes'            => 'Atributos del Informe',
        'parent_item_colon'     => 'Informe Padre:',
        'all_items'         => 'Todos los Informes',
        'add_new_item'          => 'Añadir Nuevo Informe',
        'add_new'               => 'Añadir Nuevo',
        'new_item'              => 'Nuevo Informe',
        'edit_item'             => 'Editar Informe',
        'update_item'           => 'Actualizar Informe',
        'view_item'             => 'Ver Informe',
        'view_items'            => 'Ver Informes',
        'search_items'          => 'Buscar Informe',
        'not_found'             => 'No se encontraron informes',
        'not_found_in_trash'    => 'No se encontraron informes en la papelera',
        'featured_image'        => 'Imagen Destacada',
        'set_featured_image'   => 'Establecer imagen destacada',
        'remove_featured_image' => 'Remover imagen destacada',
        'use_featured_image'    => 'Usar como imagen destacada',
        'insert_into_item'      => 'Insertar en el informe',
        'uploaded_to_this_item' => 'Subido a este informe',
        'items_list'            => 'Lista de informes',
        'items_list_navigation' => 'Navegación de lista de informes',
        'filter_items_list'     => 'Filtrar lista de informes',
    );
    
    $args = array(
        'label'                 => 'Informe',
        'description'           => 'Documentos de informes médicos',
        'labels'                => $labels,
        'supports'              => array('title'), // El contenido se gestiona mediante metadatos y adjuntos
        'taxonomies'            => array(),
        'hierarchical'          => false,
        'public'                => false, // No público
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 25,
        'menu_icon'             => 'dashicons-media-document',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'           => true,
        'has_archive'           => false,
        'exclude_from_search'  => true,
        'publicly_queryable'   => false, // No accesible públicamente
        'capability_type'       => 'post',
        'map_meta_cap'          => true,
        'capabilities' => array(
            // Definir capacidades personalizadas para mayor control
            'edit_post'          => 'edit_informe',
            'read_post'          => 'read_informe',
            'delete_post'        => 'delete_informe',
            'edit_posts'         => 'edit_informes',
            'edit_others_posts'  => 'edit_others_informes',
            'publish_posts'      => 'publish_informes',
            'read_private_posts' => 'read_private_informes',
            'delete_posts'       => 'delete_informes',
        ),
    );
    
    register_post_type('informe', $args);
}

// Agregar capacidades personalizadas a roles específicos
add_action('admin_init', 'asmel_add_informe_capabilities');
/**
 * Ejecuta add informe capabilities.
 * @return mixed Resultado de la funcion.
 */
function asmel_add_informe_capabilities() {
    // Solo ejecutar una vez
    if (get_option('asmel_informe_caps_added')) {
        return;
    }
    
    // Agregar capacidades a administradores
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('edit_informe');
        $admin_role->add_cap('read_informe');
        $admin_role->add_cap('delete_informe');
        $admin_role->add_cap('edit_informes');
        $admin_role->add_cap('edit_others_informes');
        $admin_role->add_cap('publish_informes');
        $admin_role->add_cap('read_private_informes');
        $admin_role->add_cap('delete_informes');
    }
    
    // Agregar capacidades a desarrolladores
    $dev_role = get_role('desarrollador');
    if ($dev_role) {
        $dev_role->add_cap('edit_informe');
        $dev_role->add_cap('read_informe');
        $dev_role->add_cap('delete_informe');
        $dev_role->add_cap('edit_informes');
        $dev_role->add_cap('edit_others_informes');
        $dev_role->add_cap('publish_informes');
        $dev_role->add_cap('read_private_informes');
        $dev_role->add_cap('delete_informes');
    }
    
    // Marcar como completado
    update_option('asmel_informe_caps_added', true);
}

/**
 * Ejecuta obtener directorios clientes existentes.
 * @return mixed Resultado de la funcion.
 */
function asmel_obtener_directorios_clientes_existentes() {
    $base_path = '/path/to/asmel-data/informes/';
    if (!is_dir($base_path)) {
        return array();
    }

    $directories = glob($base_path . '*', GLOB_ONLYDIR);
    if ($directories === false || empty($directories)) {
        return array();
    }

    $clientes = array();
    foreach ($directories as $dir) {
        $nombre = basename($dir);
        if ($nombre !== '' && preg_match('/^\d+$/', $nombre)) {
            $clientes[] = $nombre;
        }
    }

    sort($clientes, SORT_NATURAL);
    return $clientes;
}

// Agregar metabox para subir documento .doc/.docx
add_action('add_meta_boxes', 'asmel_add_informe_metabox');
/**
 * Ejecuta add informe metabox.
 * @return mixed Resultado de la funcion.
 */
function asmel_add_informe_metabox() {
    add_meta_box(
        'informe_documento',
        'Documento del Informe',
        'asmel_informe_metabox_callback',
        'informe',
        'normal',
        'high'
    );
    
    // Metabox para información del informe
    add_meta_box(
        'informe_info',
        'Información del Informe',
        'asmel_informe_info_callback',
        'informe',
        'side',
        'default'
    );
}

// Callback para el metabox de documento
/**
 * Ejecuta informe metabox callback.
 *
 * @param mixed $post Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_informe_metabox_callback($post) {
    // Agregar nonce para seguridad
    wp_nonce_field('asmel_save_informe', 'asmel_informe_nonce');
    
    // --- TRUCO PARA FORZAR ENCTYPE ---
    // Agregamos un input de tipo 'file' oculto para forzar a WordPress
    // a agregar automáticamente el atributo enctype="multipart/form-data"
    // al formulario de edición del post.
    echo '<div style="display:none;"><input type="file" name="asmel_force_enctype" tabindex="-1" /></div>';
    // -------------------------------
    
    // Obtener valores actuales
    $documento_url = get_post_meta($post->ID, '_informe_documento', true);
    $documento_id = get_post_meta($post->ID, '_informe_documento_id', true);
    $pdf_url = get_post_meta($post->ID, '_informe_pdf', true);
    $pdf_id = get_post_meta($post->ID, '_informe_pdf_id', true);
    
    echo '<div class="asmel-informe-upload-section">';
    echo '<h4>Subir Documento .doc/.docx</h4>';
    
    // Mostrar campo de subida de archivo
    echo '<table class="form-table">';
    echo '<tbody>';
    echo '<tr>';
    echo '<th scope="row"><label>Seleccionar Archivo:</label></th>';
    echo '<td>';
    
    // Estructura AJAX Uploader compatible con ajax-upload.js
    echo '<div id="asmel-uploader-dropzone" class="asmel-uploader-container">';
    echo '<p>Arrastra archivos aquí o haz clic para subir</p>';
    // NOTA: 'required' removido para evitar bloqueo en carga masiva ZIP
    echo '<input type="file" id="asmel-uploader-input" name="informe_documento" accept=".doc,.docx,.zip" style="display: none;" />';
    echo '<button type="button" class="button" id="asmel-uploader-select-btn" onclick="document.getElementById(\'asmel-uploader-input\').click();">Seleccionar Archivo</button>';
    echo '<p class="description">.doc, .docx (Individual) o .zip (Masivo)</p>';
    echo '</div>';
    
    echo '<div id="asmel-uploader-progress" style="display:none;">';
    echo '<div id="asmel-uploader-progress-fill"></div>';
    echo '<div id="asmel-uploader-progress-text"></div>';
    echo '</div>';
    
    echo '<div id="asmel-uploader-result"></div>';
    echo '<input type="hidden" id="asmel-attachment-id" name="asmel_attachment_id" value="' . esc_attr($documento_id) . '">';
    
    echo '</td>';
    echo '</tr>';
    
    // Mostrar documento actual si existe
    if ($documento_url) {
        echo '<tr>';
        echo '<th scope="row">Documento Actual:</th>';
        echo '<td>';
        echo '<a href="' . esc_url($documento_url) . '" target="_blank" class="button">Descargar .doc/.docx</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    // Mostrar PDF generado si existe
    if ($pdf_url) {
        echo '<tr>';
        echo '<th scope="row">PDF Generado:</th>';
        echo '<td>';
        echo '<a href="' . esc_url(admin_url('admin-post.php?action=asmel_ver_pdf_informe&archivo=' . urlencode($pdf_url) . '&post_id=' . $post->ID)) . '" target="_blank" class="button">Ver PDF</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    // Campo oculto para mantener el ID del documento (si es necesario para otro propósito)
    echo '<input type="hidden" name="informe_documento_id" value="' . esc_attr($documento_id) . '" />';
    echo '<input type="hidden" name="informe_pdf_id" value="' . esc_attr($pdf_id) . '" />';
    
    // Agregar un poco de CSS inline para mejorar la presentación del metabox
    echo '<style>
        .asmel-informe-upload-section {
            padding: 10px 0;
            border-top: 1px solid #eee;
            margin-top: 15px;
        }
        .asmel-informe-upload-section h4 {
            margin: 0 0 15px 0;
            padding: 0;
            color: #23282d;
        }
        .asmel-informe-upload-section .form-table th {
            padding: 10px 10px 10px 0;
            width: 150px;
        }
        .asmel-informe-upload-section .form-table td {
            padding: 10px 0;
        }
        .asmel-informe-upload-section .button {
            margin-right: 10px;
        }
    </style>';
}

// Callback para el metabox de información
/**
 * Ejecuta informe info callback.
 *
 * @param mixed $post Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_informe_info_callback($post) {
    // Obtener valores actuales
    $paciente_nombre = get_post_meta($post->ID, '_informe_paciente_nombre', true);
    $fecha_creacion = get_post_meta($post->ID, '_informe_fecha_creacion', true);
    $numero_cliente = get_post_meta($post->ID, '_informe_numero_cliente', true);
    $tipo_informe = get_post_meta($post->ID, '_informe_tipo', true);
    $clientes_existentes = asmel_obtener_directorios_clientes_existentes();
    
    echo '<p><label for="informe_paciente_nombre">Nombre del Paciente:</label></p>';
    echo '<input type="text" id="informe_paciente_nombre" name="informe_paciente_nombre" value="' . esc_attr($paciente_nombre) . '" style="width:100%;" required />';
    
    echo '<p><label for="informe_fecha_creacion">Fecha de Creación:</label></p>';
    echo '<input type="date" id="informe_fecha_creacion" name="informe_fecha_creacion" value="' . esc_attr($fecha_creacion) . '" style="width:100%;" required />';
    
    echo '<p><label for="informe_numero_cliente">Número de Cliente:</label></p>';
    echo '<input type="text" id="informe_numero_cliente" name="informe_numero_cliente" value="' . esc_attr($numero_cliente) . '" style="width:100%;" list="asmel-clientes-existentes" autocomplete="off" required />';
    if (!empty($clientes_existentes)) {
        echo '<datalist id="asmel-clientes-existentes">';
        foreach ($clientes_existentes as $cliente) {
            echo '<option value="' . esc_attr($cliente) . '"></option>';
        }
        echo '</datalist>';
    }
    
    echo '<p><label for="informe_tipo">Tipo de Informe:</label></p>';
    echo '<select id="informe_tipo" name="informe_tipo" style="width:100%;" required>';
    echo '<option value="">Seleccionar tipo...</option>';
    echo '<option value="01"' . selected($tipo_informe, '01', false) . '>Médico</option>';
    echo '<option value="02"' . selected($tipo_informe, '02', false) . '>Preocupacional</option>';
    echo '<option value="03"' . selected($tipo_informe, '03', false) . '>Periódico</option>';
    echo '<option value="04"' . selected($tipo_informe, '04', false) . '>Egreso</option>';
    echo '</select>';
}

/**
 * Validar campos obligatorios antes de guardar el informe.
 *
 * @param array $messages Mensajes de error existentes.
 * @param WP_Post $post Post que se está guardando.
 * @return array Mensajes de error actualizados.
 */
add_filter('post_updated_messages', 'asmel_validate_informe_before_save');
function asmel_validate_informe_before_save($messages) {
    global $post;
    
    if ($post && $post->post_type === 'informe') {
        // Verificar si es una acción de publicación
        if (isset($_POST['publish']) || isset($_POST['save'])) {
            $errors = array();
            
            // Validar campos obligatorios
            if (empty($_POST['informe_paciente_nombre'])) {
                $errors[] = 'El campo "Nombre del Paciente" es obligatorio.';
            }
            
            if (empty($_POST['informe_fecha_creacion'])) {
                $errors[] = 'El campo "Fecha de Creación" es obligatorio.';
            }
            
            if (empty($_POST['informe_numero_cliente'])) {
                $errors[] = 'El campo "Número de Cliente" es obligatorio.';
            }
            
            if (empty($_POST['informe_tipo'])) {
                $errors[] = 'El campo "Tipo de Informe" es obligatorio.';
            }
            
            // Validar archivo (solo si es nuevo o se está actualizando)
            if (empty($_FILES['informe_documento']['name']) && empty(get_post_meta($post->ID, '_informe_documento', true))) {
                $errors[] = 'Debes seleccionar un archivo .doc/.docx.';
            }
            
            // Si hay errores, mostrarlos y evitar guardar
            if (!empty($errors)) {
                // Agregar errores a los mensajes
                $messages['informe'][1] = '<div class="error"><p>' . implode('</p><p>', $errors) . '</p></div>';
                
                // Forzar que el post vuelva a ser un borrador
                remove_action('save_post_informe', 'asmel_save_informe_meta');
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_status' => 'draft'
                ));
                add_action('save_post_informe', 'asmel_save_informe_meta', 10, 3);
                
                // Redirigir para mostrar los errores
                add_action('admin_notices', function() use ($errors) {
                    echo '<div class="error"><p><strong>Errores al guardar el informe:</strong></p><ul>';
                    foreach ($errors as $error) {
                        echo '<li>' . esc_html($error) . '</li>';
                    }
                    echo '</ul></div>';
                });
            }
        }
    }
    
    return $messages;
}

/**
 * Guarda los metadatos y el archivo del informe.
 *
 * @param int     $post_id ID del post.
 * @param WP_Post $post    Objeto del post.
 * @param bool    $update  Si es una actualización.
 */
add_action('save_post_informe', 'asmel_save_informe_meta', 10, 3);
function asmel_save_informe_meta($post_id, $post, $update) {
    // Verificar si es una acción de autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        error_log("Asmel Informe Meta Save (" . current_action() . "): Autosave detectado, saliendo. ID: $post_id");
        return;
    }

    // Verificar nonce
    if (!isset($_POST['asmel_informe_nonce']) || !wp_verify_nonce($_POST['asmel_informe_nonce'], 'asmel_save_informe')) {
        error_log("Asmel Informe Meta Save (" . current_action() . "): Nonce inválido o ausente. ID: $post_id");
        return;
    }
    
    // Verificar permisos
    if (!current_user_can('edit_post', $post_id)) {
        error_log("Asmel Informe Meta Save (" . current_action() . "): Permisos insuficientes para ID: $post_id");
        return;
    }

    // --- 1. Depuración inicial ---
    error_log("--- INICIO Asmel Informe Meta Save (" . current_action() . ") para ID: $post_id ---");
    error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));
    error_log("_POST keys: " . json_encode(array_keys($_POST ?? [])));
    error_log("_FILES keys: " . json_encode(array_keys($_FILES ?? [])));
    if (isset($_FILES['informe_documento'])) {
        error_log("_FILES['informe_documento']: " . json_encode([
            'name' => $_FILES['informe_documento']['name'] ?? null,
            'type' => $_FILES['informe_documento']['type'] ?? null,
            'tmp_name' => $_FILES['informe_documento']['tmp_name'] ?? null,
            'error' => $_FILES['informe_documento']['error'] ?? null,
            'size' => $_FILES['informe_documento']['size'] ?? null,
        ]));
    } else {
        error_log("_FILES['informe_documento'] NO ESTÁ PRESENTE");
    }

    // --- 2. Extracción de datos del nombre del archivo (Backup del JS) ---
    // Si se sube un archivo, intentamos extraer datos de ahí si los campos POST están vacíos o para asegurar consistencia
    if (!empty($_FILES['informe_documento']['name'])) {
        $filename = $_FILES['informe_documento']['name'];
        // Regex: NOMBRE_PACIENTE + FECHA(8) + CLIENTE(6) + TIPO(2) + .EXT
        if (preg_match('/^([A-Z0-9_]+)(\d{8})(\d{6})(\d{2})\.(doc|docx)$/i', $filename, $match)) {
            $pacienteRaw = $match[1];
            $fechaRaw = $match[2];
            $cliente = $match[3];
            $tipo = $match[4];

            $paciente = str_replace('_', ' ', $pacienteRaw);
            $fecha = substr($fechaRaw, 0, 4) . '-' . substr($fechaRaw, 4, 2) . '-' . substr($fechaRaw, 6, 2);

            // Si los campos POST están vacíos, usamos estos valores
            if (empty($_POST['informe_paciente_nombre'])) $_POST['informe_paciente_nombre'] = $paciente;
            if (empty($_POST['informe_fecha_creacion'])) $_POST['informe_fecha_creacion'] = $fecha;
            if (empty($_POST['informe_numero_cliente'])) $_POST['informe_numero_cliente'] = $cliente;
            if (empty($_POST['informe_tipo'])) $_POST['informe_tipo'] = $tipo;
            
            // Actualizar título del post si está vacío o es "Auto Draft"
            if (empty($post->post_title) || $post->post_title === 'Auto Draft') {
                $new_title = $paciente . ' - ' . $fecha;
                // Evitar bucle infinito al actualizar post
                remove_action('save_post_informe', 'asmel_save_informe_meta');
                wp_update_post(array('ID' => $post_id, 'post_title' => $new_title));
                add_action('save_post_informe', 'asmel_save_informe_meta', 10, 3);
            }
        }
    }

    // --- 3. Guardar metadatos simples desde $_POST ---
    $meta_fields = [
        'informe_paciente_nombre'   => '_informe_paciente_nombre',
        'informe_fecha_creacion'    => '_informe_fecha_creacion',
        'informe_numero_cliente'     => '_informe_numero_cliente',
        'informe_tipo'              => '_informe_tipo',
    ];

    foreach ($meta_fields as $post_key => $meta_key) {
        if (isset($_POST[$post_key])) {
            $value = sanitize_text_field(wp_unslash($_POST[$post_key]));
            update_post_meta($post_id, $meta_key, $value);
            error_log("Guardado $meta_key: $value");
        } else {
            error_log("Campo $post_key no encontrado en \$_POST");
        }
    }

    // --- 4. Manejar subida de archivo ---
    // Opción A: Se ha subido un archivo nuevo
    if (!empty($_FILES['informe_documento']['name'])) {
        error_log("Detectado nuevo archivo para subir: " . $_FILES['informe_documento']['name']);

        // Verificar errores de subida
        if ($_FILES['informe_documento']['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo excede upload_max_filesize en php.ini.',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo excede MAX_FILE_SIZE especificado en el formulario HTML.',
                UPLOAD_ERR_PARTIAL    => 'El archivo fue subido parcialmente.',
                UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco.',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.',
            ];
            $error_msg = $upload_errors[$_FILES['informe_documento']['error']] ?? 'Error desconocido en la subida.';
            error_log("ERROR de subida: " . $error_msg . " (Código: " . $_FILES['informe_documento']['error'] . ")");
            set_transient('asmel_informe_error_' . get_current_user_id(), "Error al subir el archivo: $error_msg", 45);
            return; // Detener procesamiento
        }

        // Verificar tipo de archivo
        $file_name = $_FILES['informe_documento']['name'];
        $file_type = wp_check_filetype($file_name);
        $allowed_extensions = ['doc', 'docx'];
        if (!in_array(strtolower($file_type['ext']), $allowed_extensions, true)) {
            error_log("ERROR: Tipo de archivo no permitido: " . $file_type['ext'] . " para el archivo: $file_name");
            set_transient('asmel_informe_error_' . get_current_user_id(), "Tipo de archivo no permitido. Solo .doc y .docx.", 45);
            return;
        }

        // Incluir dependencias de WordPress para subir archivos
        if (!function_exists('media_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // Subir archivo usando la API de WordPress
        // Importante: Pasar $post_id como segundo argumento para adjuntarlo al post.
        $attachment_id = media_handle_upload('informe_documento', $post_id);

        if (is_wp_error($attachment_id)) {
            $error_string = $attachment_id->get_error_message();
            error_log("ERROR al subir archivo con media_handle_upload: $error_string");
            set_transient('asmel_informe_error_' . get_current_user_id(), "Error al guardar el archivo en medios: $error_string", 45);
        } else {
            $attachment_url = wp_get_attachment_url($attachment_id);
            update_post_meta($post_id, '_informe_documento', $attachment_url);
            update_post_meta($post_id, '_informe_documento_id', $attachment_id);
            error_log("Archivo subido exitosamente. Attachment ID: $attachment_id, URL: $attachment_url");
        }

    }
    // Opción B: No hay archivo nuevo, pero tal vez se está actualizando
    // y el archivo ya existía. No hacemos nada especial aquí, ya que
    // WordPress mantiene los metadatos existentes si no se sobrescriben.
    else {
        error_log("No se detectó un nuevo archivo para subir en \$_FILES['informe_documento']['name'].");
        // Podríamos verificar si `_informe_documento` ya existe para este post.
        // Si no existe y debería existir, podríamos mostrar un aviso.
        // Pero normalmente, si ya existe, no necesitamos hacer nada.
        $existing_doc_url = get_post_meta($post_id, '_informe_documento', true);
        if ($existing_doc_url) {
             error_log("Documento ya existe para este informe: $existing_doc_url");
        } else {
             error_log("No hay documento adjunto a este informe.");
        }
    }

    error_log("--- FIN Asmel Informe Meta Save (" . current_action() . ") para ID: $post_id ---");
}

// Agregar columnas personalizadas en la lista de informes
add_filter('manage_informe_posts_columns', 'asmel_add_informe_columns');
/**
 * Ejecuta add informe columns.
 *
 * @param mixed $columns Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_add_informe_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        if ($key == 'title') {
            $new_columns['title'] = 'Título';
            $new_columns['paciente'] = 'Paciente';
            $new_columns['fecha'] = 'Fecha Creación';
            $new_columns['cliente'] = 'Nº Cliente';
            $new_columns['tipo'] = 'Tipo Informe';
            $new_columns['documento'] = 'Documento';
            $new_columns['pdf'] = 'PDF';
        } else {
            $new_columns[$key] = $value;
        }
    }
    
    return $new_columns;
}

// Mostrar contenido en las columnas personalizadas
add_action('manage_informe_posts_custom_column', 'asmel_show_informe_columns', 10, 2);
/**
 * Ejecuta show informe columns.
 *
 * @param mixed $column Parametro de entrada.
 * @param mixed $post_id Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_show_informe_columns($column, $post_id) {
    switch ($column) {
        case 'paciente':
            echo esc_html(get_post_meta($post_id, '_informe_paciente_nombre', true));
            break;
        case 'fecha':
            $fecha = get_post_meta($post_id, '_informe_fecha_creacion', true);
            if ($fecha) {
                echo esc_html(date('d/m/Y', strtotime($fecha)));
            }
            break;
        case 'cliente':
            echo esc_html(get_post_meta($post_id, '_informe_numero_cliente', true));
            break;
        case 'tipo':
            $tipo = get_post_meta($post_id, '_informe_tipo', true);
            $tipos = array(
                '01' => 'Médico',
                '02' => 'Preocupacional',
                '03' => 'Periódico',
                '04' => 'Egreso'
            );
            echo isset($tipos[$tipo]) ? esc_html($tipos[$tipo]) : esc_html($tipo);
            break;
        case 'documento':
            $url = get_post_meta($post_id, '_informe_documento', true);
            if ($url) {
                echo '<a href="' . esc_url($url) . '" target="_blank">Descargar</a>';
            }
            break;
        case 'pdf':
            $url = get_post_meta($post_id, '_informe_pdf', true);
            if ($url) {
                echo '<a href="' . esc_url(admin_url('admin-post.php?action=asmel_ver_pdf_informe&archivo=' . urlencode($url) . '&post_id=' . $post_id)) . '" target="_blank">Ver PDF</a>';
            } else {
                echo '<span style="color:#999;">Pendiente</span>';
            }
            break;
    }
    return $value;
}

// Hacer las columnas ordenables
add_filter('manage_edit-informe_sortable_columns', 'asmel_sortable_informe_columns');
/**
 * Ejecuta sortable informe columns.
 *
 * @param mixed $columns Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_sortable_informe_columns($columns) {
    $columns['paciente'] = 'paciente';
    $columns['fecha'] = 'fecha';
    $columns['cliente'] = 'cliente';
    $columns['tipo'] = 'tipo';
    return $columns;
}

// Agregar filtro por tipo de informe en la lista
add_action('restrict_manage_posts', 'asmel_add_informe_filters');
/**
 * Ejecuta add informe filters.
 *
 * @param mixed $post_type Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_add_informe_filters($post_type) {
    if ($post_type !== 'informe') {
        return;
    }
    
    $selected_tipo = isset($_GET['tipo_informe']) ? $_GET['tipo_informe'] : '';
    ?>
    <select name="tipo_informe">
        <option value="">Todos los tipos</option>
        <option value="01" <?php selected($selected_tipo, '01'); ?>>Médico</option>
        <option value="02" <?php selected($selected_tipo, '02'); ?>>Preocupacional</option>
        <option value="03" <?php selected($selected_tipo, '03'); ?>>Periódico</option>
        <option value="04" <?php selected($selected_tipo, '04'); ?>>Egreso</option>
    </select>
    <?php
}

// Aplicar filtro por tipo de informe
add_filter('parse_query', 'asmel_filter_informe_by_tipo');
/**
 * Ejecuta filter informe by tipo.
 *
 * @param mixed $query Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_filter_informe_by_tipo($query) {
    global $pagenow;
    
    if (
        is_admin() &&
        $pagenow === 'edit.php' &&
        isset($_GET['post_type']) &&
        $_GET['post_type'] === 'informe' &&
        isset($_GET['tipo_informe']) &&
        !empty($_GET['tipo_informe'])
    ) {
        $meta_query = array(
            array(
                'key' => '_informe_tipo',
                'value' => sanitize_text_field($_GET['tipo_informe']),
                'compare' => '='
            )
        );
        
        $query->set('meta_query', $meta_query);
    }
}

// Asegurar que solo se muestren usuarios cliente
add_filter('map_meta_cap', 'asmel_informe_capabilities', 10, 4);
/**
 * Ejecuta informe capabilities.
 *
 * @param mixed $caps Parametro de entrada.
 * @param mixed $cap Parametro de entrada.
 * @param mixed $user_id Parametro de entrada.
 * @param mixed $args Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_informe_capabilities($caps, $cap, $user_id, $args) {
    if (in_array($cap, array('edit_post', 'read_post', 'delete_post')) && isset($args[0]) && get_post_type($args[0]) === 'informe') {
        // Solo administradores, desarrolladores y clientes pueden acceder
        if (!user_can($user_id, 'administrator') && !user_can($user_id, 'desarrollador') && !user_can($user_id, 'cliente')) {
            $caps[] = 'do_not_allow';
        }
    }
    
    return $caps;
}

// Verificar si el informe tiene todos los campos obligatorios antes de publicar
add_action('admin_notices', 'asmel_check_informe_required_fields');
/**
 * Ejecuta check informe required fields.
 * @return mixed Resultado de la funcion.
 */
function asmel_check_informe_required_fields() {
    global $pagenow, $post;
    
    if ($pagenow == 'post.php' && $post && $post->post_type == 'informe') {
        // Verificar si es una acción de publicación
        if (isset($_POST['publish']) || isset($_GET['message']) && $_GET['message'] == 1) {
            $errors = array();
            
            // Verificar campos obligatorios
            if (empty(get_post_meta($post->ID, '_informe_paciente_nombre', true))) {
                $errors[] = 'El campo "Nombre del Paciente" es obligatorio.';
            }
            
            if (empty(get_post_meta($post->ID, '_informe_fecha_creacion', true))) {
                $errors[] = 'El campo "Fecha de Creación" es obligatorio.';
            }
            
            if (empty(get_post_meta($post->ID, '_informe_numero_cliente', true))) {
                $errors[] = 'El campo "Número de Cliente" es obligatorio.';
            }
            
            if (empty(get_post_meta($post->ID, '_informe_tipo', true))) {
                $errors[] = 'El campo "Tipo de Informe" es obligatorio.';
            }
            
            // Verificar archivo
            if (empty(get_post_meta($post->ID, '_informe_documento', true))) {
                $errors[] = 'Debes seleccionar un archivo .doc/.docx.';
            }
            
            // Si hay errores, mostrarlos
            if (!empty($errors)) {
                echo '<div class="error"><p><strong>Errores en el informe:</strong></p><ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul></div>';
                
                // Forzar que el post vuelva a ser un borrador
                remove_action('save_post_informe', 'asmel_save_informe_meta');
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_status' => 'draft'
                ));
                add_action('save_post_informe', 'asmel_save_informe_meta', 10, 3);
            }
        }
    }
}

// Agregar validación antes de publicar
add_filter('wp_insert_post_data', 'asmel_validate_informe_before_publish', 10, 2);
/**
 * Ejecuta validate informe before publish.
 *
 * @param mixed $data Parametro de entrada.
 * @param mixed $postarr Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_validate_informe_before_publish($data, $postarr) {
    if ($data['post_type'] === 'informe') {
        // Verificar si es una acción de publicación
        if (isset($_POST['publish']) || isset($_POST['save'])) {
            $errors = array();
            
            // Validar campos obligatorios
            if (empty($_POST['informe_paciente_nombre'])) {
                $errors[] = 'El campo "Nombre del Paciente" es obligatorio.';
            }
            
            if (empty($_POST['informe_fecha_creacion'])) {
                $errors[] = 'El campo "Fecha de Creación" es obligatorio.';
            }
            
            if (empty($_POST['informe_numero_cliente'])) {
                $errors[] = 'El campo "Número de Cliente" es obligatorio.';
            }
            
            if (empty($_POST['informe_tipo'])) {
                $errors[] = 'El campo "Tipo de Informe" es obligatorio.';
            }
            
            // Validar archivo (solo si es nuevo o se está actualizando)
            if (empty($_FILES['informe_documento']['name']) && empty(get_post_meta($postarr['ID'], '_informe_documento', true))) {
                $errors[] = 'Debes seleccionar un archivo .doc/.docx.';
            }
            
            // Si hay errores, mostrarlos y evitar publicar
            if (!empty($errors)) {
                // Agregar errores a los mensajes
                add_action('admin_notices', function() use ($errors) {
                    echo '<div class="error"><p><strong>Errores al guardar el informe:</strong></p><ul>';
                    foreach ($errors as $error) {
                        echo '<li>' . esc_html($error) . '</li>';
                    }
                    echo '</ul></div>';
                });
                
                // Forzar que el post vuelva a ser un borrador
                $data['post_status'] = 'draft';
            }
        }
    }
    
    return $data;
}

// Crear el endpoint para ver el PDF
add_action('admin_post_asmel_ver_pdf_informe', 'asmel_ver_pdf_informe');
add_action('admin_post_nopriv_asmel_ver_pdf_informe', 'asmel_ver_pdf_informe');
/**
 * Ejecuta ver pdf informe.
 * @return mixed Resultado de la funcion.
 */
function asmel_ver_pdf_informe() {
    if (!is_user_logged_in()) {
        wp_die('Debes estar logueado para ver el PDF.');
    }

    if (empty($_GET['archivo'])) {
        wp_die('Archivo no especificado.');
    }

    // Recibimos tanto rutas locales como URLs públicas. Intentamos resolver a una ruta de fichero.
    $archivo_raw = urldecode($_GET['archivo']);
    $archivo_sanitizado = sanitize_text_field($archivo_raw);

    $candidates = array();

    // Si viene como URL, intentar mapear a rutas locales conocidas
    if (preg_match('#^https?://#i', $archivo_sanitizado)) {
        $parsed = wp_parse_url($archivo_sanitizado);
        $path = isset($parsed['path']) ? $parsed['path'] : '';

        // Caso típico: /clientes/Asmel/informes/NNNNNN/archivo.pdf -> /path/to/asmel-data/informes/NNNNNN/archivo.pdf
        if ($path && strpos($path, '/Asmel/informes') !== false) {
            $suffix = substr($path, strpos($path, '/Asmel/informes') + strlen('/Asmel/informes'));
            $candidates[] = '/path/to/asmel-data/informes' . $suffix;
        }

        // También probar mapeo directo desde la URL pública a public_html si corresponde
        $home = wp_parse_url(home_url());
        $home_root = isset($home['path']) ? $home['path'] : '';
        // Intento directo: reemplazar el host/public path por la carpeta public_html (si aplica)
        $public_root = '/path/to/public_html';
        if ($path) {
            $candidates[] = $public_root . $path;
        }
    }

    // También aceptar que nos pasen directamente la ruta de archivo
    $candidates[] = $archivo_sanitizado;

    // Buscar primer candidato que exista
    $archivo_real = false;
    foreach ($candidates as $cand) {
        if (!$cand) continue;
        // Normalizar
        $cand = str_replace(array('\0', "\0"), '', $cand);
        if (file_exists($cand) && is_file($cand)) {
            $archivo_real = $cand;
            break;
        }
    }

    if (!$archivo_real) {
        wp_die('Acceso denegado o archivo no existe.');
    }

    // Verificar que el archivo pertenezca al cliente indicado en post_id
    $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    $numero_cliente = get_post_meta($post_id, '_informe_numero_cliente', true);
    $base_path = '/path/to/asmel-data/informes/' . $numero_cliente . '/';

    // Seguridad: solo permite ver archivos dentro del directorio del cliente
    $real_base = realpath($base_path);
    $real_file = realpath($archivo_real);
    if ($real_base === false || $real_file === false || strpos($real_file, $real_base) !== 0) {
        wp_die('Acceso denegado o archivo no existe.');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($real_file) . '"');
    header('Content-Length: ' . filesize($real_file));
    readfile($real_file);
    exit;
}

/**
 * Permitir subida de archivos .doc y .docx
 */
add_filter('upload_mimes', 'asmel_allow_doc_uploads');
function asmel_allow_doc_uploads($mimes) {
    $mimes['doc'] = 'application/msword';
    $mimes['docx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    // Agregar variantes comunes
    $mimes['docm'] = 'application/vnd.ms-word.document.macroEnabled.12';
    $mimes['dot']  = 'application/msword';
    $mimes['dotx'] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
    return $mimes;
}

/**
 * Forzar la validación de archivos .doc/.docx si WordPress falla al detectar el tipo MIME real.
 * Esto soluciona el error "Lo siento, no tienes permisos para subir este tipo de archivo".
 */
add_filter('wp_check_filetype_and_ext', 'asmel_force_allow_doc_uploads', 10, 4);
/**
 * Ejecuta force allow doc uploads.
 *
 * @param mixed $data Parámetro de entrada.
 * @param mixed $file Parámetro de entrada.
 * @param mixed $filename Parámetro de entrada.
 * @param mixed $mimes Parámetro de entrada.
 * @return mixed Resultado de la función.
 */
function asmel_force_allow_doc_uploads($data, $file, $filename, $mimes) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    
    if (in_array($ext, ['doc', 'docx'])) {
        // Si WordPress ya lo validó correctamente, no hacemos nada
        if (!empty($data['ext']) && !empty($data['type'])) {
            return $data;
        }
        
        // Forzar la validación
        $data['ext'] = $ext;
        $data['type'] = ($ext === 'docx') ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/msword';
        $data['proper_filename'] = $filename;
    }
    return $data;
}

/**
 * Script JS para autocompletar campos del informe basado en el nombre del archivo
 */
add_action('admin_footer', 'asmel_informe_autofill_script');
/**
 * Ejecuta informe autofill script.
 * @return mixed Resultado de la función.
 */
function asmel_informe_autofill_script() {
    global $post_type;
    if ($post_type !== 'informe') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#informe_documento_input').on('change', function() {
            var file = this.files[0];
            if (!file) return;

            var filename = file.name;
            // Regex para: NOMBRE_PACIENTE + FECHA(8) + CLIENTE(6) + TIPO(2) + .EXT
            // Ejemplo: ESPINDOLA_CARLOS_JAVIER2025122911143101.doc
            var regex = /^([A-Z0-9_]+)(\d{8})(\d{6})(\d{2})\.(doc|docx)$/i;
            var match = filename.match(regex);

            if (match) {
                var pacienteRaw = match[1];
                var fechaRaw = match[2];
                var cliente = match[3];
                var tipo = match[4];

                // Formatear nombre: Reemplazar guiones bajos por espacios
                var paciente = pacienteRaw.replace(/_/g, ' ');

                // Formatear fecha: YYYYMMDD -> YYYY-MM-DD
                var fecha = fechaRaw.substring(0, 4) + '-' + fechaRaw.substring(4, 6) + '-' + fechaRaw.substring(6, 8);

                // Rellenar campos
                $('#informe_paciente_nombre').val(paciente);
                $('#informe_fecha_creacion').val(fecha);
                $('#informe_numero_cliente').val(cliente);
                $('#informe_tipo').val(tipo);
                
                // Actualizar título del post también para referencia
                $('#title').val(paciente + ' - ' + fecha);

                // Feedback visual
                // alert('Datos extraídos del archivo:\nPaciente: ' + paciente + '\nFecha: ' + fecha + '\nCliente: ' + cliente + '\nTipo: ' + tipo);
            } else {
                console.log('El nombre del archivo no coincide con el formato esperado.');
            }
        });
    });
    </script>
    <?php
}


