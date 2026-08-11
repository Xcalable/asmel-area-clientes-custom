<?php
// Handler AJAX para subir archivos de informes

// Verificar que no se acceda directamente
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registrar el endpoint AJAX para subir archivos de informes
 */
add_action('wp_ajax_asmel_upload_informe_documento', 'asmel_ajax_upload_informe_documento');
add_action('wp_ajax_nopriv_asmel_upload_informe_documento', 'asmel_ajax_upload_informe_documento');

/**
 * Handler AJAX para subir el documento .doc/.docx del informe
 */
function asmel_ajax_upload_informe_documento() {
    // Intentar aumentar límites de ejecución y memoria para cargas pesadas 
    @set_time_limit(600); // 10 minutos
    @ini_set('memory_limit', '512M');
    @ini_set('max_execution_time', 600);

    // Verificar si el usuario está logueado y tiene permisos
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Debes estar logueado para realizar esta acción.'));
        wp_die();
    }
    
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asmel_upload_informe_nonce')) {
        wp_send_json_error(array('message' => 'Error de seguridad.'));
        wp_die();
    }
    
    // Verificar si hay archivo para subir
    if (empty($_FILES['informe_documento'])) {
        wp_send_json_error(array('message' => 'No se ha seleccionado ningún archivo.'));
        wp_die();
    }
    
    // Verificar errores de subida
    if ($_FILES['informe_documento']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = array(
            UPLOAD_ERR_INI_SIZE   => 'El archivo excede upload_max_filesize en php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo excede MAX_FILE_SIZE especificado en el formulario HTML.',
            UPLOAD_ERR_PARTIAL    => 'El archivo fue subido parcialmente.',
            UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.',
        );
        $error_msg = isset($upload_errors[$_FILES['informe_documento']['error']]) ? 
                     $upload_errors[$_FILES['informe_documento']['error']] : 
                     'Error desconocido en la subida.';
        
        wp_send_json_error(array('message' => $error_msg));
        wp_die();
    }
    
    // Verificar tipo de archivo
    $file_name = $_FILES['informe_documento']['name'];
    $file_type = wp_check_filetype($file_name);
    $allowed_extensions = array('doc', 'docx', 'zip'); // Agregar 'zip'
    
    if (!in_array(strtolower($file_type['ext']), $allowed_extensions, true)) {
        wp_send_json_error(array('message' => 'Tipo de archivo no permitido. Solo se permiten .doc, .docx y .zip.'));
        wp_die();
    }
    
    // Incluir dependencias necesarias
    if (!function_exists('media_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    // --- MANEJO DE ARCHIVOS ZIP ---
    if (strtolower($file_type['ext']) === 'zip') {
        // Cargar las funciones del convertidor si no están disponibles
        if (!function_exists('asmel_get_client_directory')) {
            require_once get_stylesheet_directory() . '/includes/document-converter.php';
        }
        
        $zip_file = $_FILES['informe_documento']['tmp_name'];
        $zip = new ZipArchive;
        $res = $zip->open($zip_file);
        
        if ($res === TRUE) {
            $processed_count = 0;
            $failed_count = 0;
            $errors = array();
            
            // Crear carpeta temporal para extracción
            $temp_extract_dir = get_temp_dir() . 'asmel_zip_' . uniqid() . '/';
            if (!file_exists($temp_extract_dir)) {
                mkdir($temp_extract_dir, 0755, true);
            }
            
            // Extraer y procesar
            $zip->extractTo($temp_extract_dir);
            $zip->close();
            
            $extracted_files = scandir($temp_extract_dir);
            
            foreach ($extracted_files as $extracted_file) {
                if ($extracted_file === '.' || $extracted_file === '..') continue;
                if (strpos($extracted_file, '__MACOSX') === 0) continue; // Ignorar metadatos Mac
                
                $full_path = $temp_extract_dir . $extracted_file;
                $ext = strtolower(pathinfo($extracted_file, PATHINFO_EXTENSION));
                
                if (in_array($ext, ['doc', 'docx'])) {
                    // Regex para la nomenclatura: PACIENTE + FECHA(8) + CLIENTE(6) + TIPO(2)
                    if (preg_match('/^([A-Z0-9_]+)(\d{8})(\d{6})(\d{2})\.(doc|docx)$/i', $extracted_file, $match)) {
                        $paciente_nombre = str_replace('_', ' ', $match[1]);
                        $fecha_creacion = substr($match[2], 0, 4) . '-' . substr($match[2], 4, 2) . '-' . substr($match[2], 6, 2);
                        $numero_cliente = $match[3];
                        $tipo_informe = $match[4];
                        
                        // 1. Convertir a PDF
                        $pdf_filename = asmel_generate_filename($paciente_nombre, $fecha_creacion, $numero_cliente, $tipo_informe);
                        $client_dir = asmel_get_client_directory($numero_cliente);
                        $pdf_path = $client_dir . $pdf_filename;
                        
                        // Intentar conversión
                        if (asmel_convert_doc_to_pdf($full_path, $pdf_path)) {
                            // 2. Mover también el archivo original .doc/.docx a la carpeta del cliente
                            $dest_doc_path = $client_dir . $extracted_file;
                            
                            // Si existe, borrarlo antes para reemplazar
                            if (file_exists($dest_doc_path)) {
                                unlink($dest_doc_path);
                            }
                            
                            // Mover
                            if (rename($full_path, $dest_doc_path)) {
                                $processed_count++;
                            } else {
                                $failed_count++;
                                $errors[] = "Error al mover original: $extracted_file";
                            }
                        } else {
                            $failed_count++;
                            $errors[] = "Error de conversión PDF: $extracted_file";
                        }
                    } else {
                        $failed_count++; // No cumplía la nomenclatura
                        $errors[] = "Nomenclatura inválida: $extracted_file";
                    }
                }
            }
            
            // Limpieza
            array_map('unlink', glob("$temp_extract_dir/*.*"));
            rmdir($temp_extract_dir);
            
            // Responder al cliente
            $msg = "Procesamiento ZIP finalizado. Éxito: $processed_count. Fallidos: $failed_count.";
            if (!empty($errors)) {
                $msg .= " Errores: " . implode(', ', array_slice($errors, 0, 3)); // Mostrar primeros 3 errores
            }
            
            if ($processed_count > 0) {
                 wp_send_json_success(array(
                    'message' => $msg,
                    'is_zip_process' => true // Señal para el frontend de que esto fue especial
                ));
            } else {
                 wp_send_json_error(array('message' => $msg));
            }
            wp_die();
            
        } else {
            wp_send_json_error(array('message' => 'No se pudo abrir el archivo ZIP.'));
            wp_die();
        }
    }

    // --- FIN MANEJO ZIP (Sigue lógica normal para single file) ---

    // Subir archivo usando la API de WordPress
    $attachment_id = media_handle_upload('informe_documento', 0); // 0 = no adjuntar a ningún post aún
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        wp_die();
    }
    
    // Obtener URL del archivo subido
    $attachment_url = wp_get_attachment_url($attachment_id);
    
    // Devolver respuesta exitosa
    wp_send_json_success(array(
        'message' => 'Archivo subido correctamente.',
        'attachment_id' => $attachment_id,
        'attachment_url' => $attachment_url,
        'file_name' => $file_name
    ));
    
    wp_die();
}

/**
 * Registrar el endpoint AJAX para asociar archivo a informe
 */
add_action('wp_ajax_asmel_associate_informe_documento', 'asmel_ajax_associate_informe_documento');

/**
 * Handler AJAX para asociar el documento subido a un informe específico
 */
function asmel_ajax_associate_informe_documento() {
    // Verificar si el usuario está logueado y tiene permisos
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Debes estar logueado para realizar esta acción.'));
        wp_die();
    }
    
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'asmel_associate_informe_nonce')) {
        wp_send_json_error(array('message' => 'Error de seguridad.'));
        wp_die();
    }
    
    // Validar campos
    if (empty($_POST['post_id']) || empty($_POST['attachment_id'])) {
        wp_send_json_error(array('message' => 'Faltan datos requeridos.'));
        wp_die();
    }
    
    $post_id = intval($_POST['post_id']);
    $attachment_id = intval($_POST['attachment_id']);
    
    // Verificar que el post exista y sea de tipo 'informe'
    if (get_post_type($post_id) !== 'informe') {
        wp_send_json_error(array('message' => 'El post no es un informe válido.'));
        wp_die();
    }
    
    // Verificar permisos
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'No tienes permisos para editar este informe.'));
        wp_die();
    }
    
    // Verificar que el attachment exista
    if (!get_post($attachment_id)) {
        wp_send_json_error(array('message' => 'El archivo adjunto no existe.'));
        wp_die();
    }
    
    // Asociar el archivo al informe
    $attachment_url = wp_get_attachment_url($attachment_id);
    update_post_meta($post_id, '_informe_documento', $attachment_url);
    update_post_meta($post_id, '_informe_documento_id', $attachment_id);
    
    // Registrar fecha de cambio de contraseña (para el sistema de expiración)
    update_user_meta(get_current_user_id(), 'last_password_change', time());
    
    // Devolver respuesta exitosa
    wp_send_json_success(array(
        'message' => 'Archivo asociado correctamente al informe.',
        'attachment_url' => $attachment_url
    ));
    
    wp_die();
}

