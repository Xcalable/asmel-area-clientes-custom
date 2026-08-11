<?php
// Conversor de documentos .doc/.docx a PDF

// - Constantes de configuración -
// Define si se fuerza la regeneración del PDF incluso si ya existe (útil para debugging)
if (!defined('ASMEL_FORCE_PDF_REGENERATION')) {
    define('ASMEL_FORCE_PDF_REGENERATION', false);
}

/**
 * Verifica si las librerías necesarias están cargadas.
 *
 * @return bool True si están cargadas, false en caso contrario.
 */
function asmel_check_converter_dependencies() {
    $phpword_loaded = class_exists('\\PhpOffice\\PhpWord\\IOFactory');
    $dompdf_loaded = class_exists('\\Dompdf\\Dompdf');

    if (!$phpword_loaded) {
        error_log('Asmel Document Converter: PHPWord no está cargado. Asegúrate de que vendor/autoload.php se haya incluido correctamente.');
    }
    if (!$dompdf_loaded) {
        error_log('Asmel Document Converter: Dompdf no está cargado. Asegúrate de que vendor/autoload.php se haya incluido correctamente.');
    }

    return $phpword_loaded && $dompdf_loaded;
}

/**
 * Genera un nombre de archivo PDF basado en la nomenclatura definida.
 *
 * @param string $paciente_nombre Nombre del paciente.
 * @param string $fecha_creacion Fecha de creación (formato Y-m-d).
 * @param string $numero_cliente Número de cliente.
 * @param string $tipo_informe Tipo de informe (01, 02, etc.).
 * @return string Nombre del archivo PDF.
 */
/**
 * Ejecuta generate filename.
 *
 * @param mixed $paciente_nombre Parametro de entrada.
 * @param mixed $fecha_creacion Parametro de entrada.
 * @param mixed $numero_cliente Parametro de entrada.
 * @param mixed $tipo_informe Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_generate_filename($paciente_nombre, $fecha_creacion, $numero_cliente, $tipo_informe) {
    // Limpiar y formatear nombre del paciente (solo letras, números y guiones bajos)
    $paciente_limpio = preg_replace('/[^A-Za-z0-9_]/', '_', strtoupper(trim($paciente_nombre)));

    // Formatear fecha (YYYYMMDD)
    $timestamp_fecha = strtotime($fecha_creacion);
    if ($timestamp_fecha === false) {
        $fecha_formateada = date('Ymd'); // Fallback a hoy si la fecha es inválida
        error_log("Asmel Document Converter: Fecha de creación inválida '$fecha_creacion'. Usando fecha actual.");
    } else {
        $fecha_formateada = date('Ymd', $timestamp_fecha);
    }

    // Formatear número de cliente (6 dígitos con ceros a la izquierda)
    $cliente_formateado = str_pad($numero_cliente, 6, '0', STR_PAD_LEFT);

    // Formatear tipo de informe (2 dígitos)
    $tipo_formateado = str_pad($tipo_informe, 2, '0', STR_PAD_LEFT);

    // Construir nombre de archivo
    $filename = "{$paciente_limpio}{$fecha_formateada}{$cliente_formateado}{$tipo_formateado}.pdf";

    // Asegurar que no haya dobles guiones bajos consecutivos
    $filename = preg_replace('/_+/', '_', $filename);

    return $filename;
}

/**
 * Obtiene la ruta base donde se deben guardar los archivos del sistema.
 *
 * @param string $numero_cliente Número de cliente.
 * @return string Ruta base del directorio del cliente.
 */
function asmel_get_client_directory($numero_cliente) {
    // Ruta base del sistema
    $base_path = '/path/to/asmel-data/informes/';
    
    // Crear el directorio del cliente si no existe
    $client_dir = $base_path . $numero_cliente . '/';
    if (!is_dir($client_dir)) {
        mkdir($client_dir, 0755, true);
    }
    
    return $client_dir;
}

/**
 * Carga un documento .doc/.docx usando el lector específico de PHPWord.
 *
 * @param string $doc_path Ruta completa al archivo .doc/.docx de origen.
 * @return \PhpOffice\PhpWord\PhpWord|null Objeto PhpWord o null si falla.
 */
function asmel_load_word_document($doc_path) {
    if (!asmel_check_converter_dependencies()) {
        return null;
    }

    $file_info = pathinfo($doc_path);
    $extension = strtolower($file_info['extension'] ?? '');

    try {
        error_log("Asmel Document Converter: Intentando cargar documento desde '$doc_path' (extensión: .$extension)...");

        // Determinar el lector según la extensión
        switch ($extension) {
            case 'docx':
                // .docx es un archivo ZIP, usar IOFactory::load
                $phpWord = \PhpOffice\PhpWord\IOFactory::load($doc_path);
                break;
            
            case 'doc':
                // .doc es un archivo binario, usar el lector específico
                try {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($doc_path, 'MsDoc');
                } catch (\Exception $e) {
                    // Si falla porque no es OLE, intentar como Word2007 (posible .docx renombrado)
                    if (strpos($e->getMessage(), 'not recognised as an OLE file') !== false) {
                        error_log("Asmel Document Converter: El archivo .doc no es OLE. Intentando cargar como Word2007...");
                        try {
                            $phpWord = \PhpOffice\PhpWord\IOFactory::load($doc_path, 'Word2007');
                        } catch (\Exception $e2) {
                            // Si falla, intentar RTF
                            try {
                                $phpWord = \PhpOffice\PhpWord\IOFactory::load($doc_path, 'RTF');
                            } catch (\Exception $e3) {
                                throw $e; // Lanzar la excepción original si fallan los fallbacks
                            }
                        }
                    } else {
                        throw $e;
                    }
                }
                break;
            
            default:
                $msg = "Extensión de archivo no soportada: .$extension";
                error_log("Asmel Document Converter: $msg");
                if (function_exists('get_current_user_id')) {
                    set_transient('asmel_informe_error_' . get_current_user_id(), $msg, 45);
                }
                return null;
        }

        error_log("Asmel Document Converter: Documento .$extension cargado exitosamente.");
        return $phpWord;

    } catch (\Throwable $e) { // Capturar Exception Y Error (PHP 7+)
        $msg = 'Error CRÍTICO al leer el archivo Word: ' . $e->getMessage();
        error_log('Asmel Document Converter: ' . $msg);
        if (function_exists('get_current_user_id')) {
            set_transient('asmel_informe_error_' . get_current_user_id(), $msg, 45);
        }
        return null; // Fallo controlado, permite que el script siga
    }
}

/**
 * Convierte un documento PHPWord a PDF usando Dompdf.
 *
 * @param \PhpOffice\PhpWord\PhpWord $phpWord Objeto PHPWord cargado.
 * @param string $pdf_path Ruta completa donde se guardará el PDF.
 * @return bool True si la conversión fue exitosa, false en caso contrario.
 */
function asmel_convert_phpword_to_pdf($phpWord, $pdf_path) {
    if (!asmel_check_converter_dependencies()) {
        return false;
    }

    try {
        error_log("Asmel Document Converter: Iniciando conversión de PHPWord a PDF...");

        // --- MEJORA: Usar el Writer HTML nativo de PHPWord ---
        // Esto preserva mucho mejor el formato (tablas, estilos, etc.) que iterar manualmente.
        
        // Configurar el escritor HTML
        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        
        // Capturar el HTML generado usando buffer de salida (método más compatible que getContent)
        ob_start();
        $xmlWriter->save('php://output');
        $htmlContent = ob_get_clean();
        
        // Limpiar el HTML para Dompdf (opcional, pero recomendado)
        // Asegurar charset y estilos básicos
        $html = '<html><head><meta charset="UTF-8">';
        // CSS Mejorado para fidelidad visual basado en la imagen de referencia (Formulario Médico)
        $html .= '<style>
            @page { margin: 1cm; }
            body { 
                font-family: Helvetica, Arial, sans-serif; 
                font-size: 9pt; 
                line-height: 1.3; 
                color: #000;
            }
            /* Forzamos estructura de tabla similar al formulario de la imagen */
            table { 
                width: 100% !important; 
                border-collapse: collapse; 
                border-spacing: 0; 
                margin-bottom: 20px; 
                border: 1px solid #000; /* Borde exterior */
            }
            td, th { 
                padding: 5px; 
                vertical-align: top; 
                border: 1px solid #666; /* Bordes internos de celdas */
            }
            
            /* Títulos y textos */
            p { margin: 0 0 5px 0; }
            strong, b { font-weight: bold; color: #000; }
            
            /* Ajuste para imágenes (Logo) */
            img { max-width: 100%; height: auto; }
            
            /* Clases específicas que PHPWord podría generar */
            .TableGrid, .TableGrid td { border: 1px solid #000; }
        </style>';
        $html .= '</head><body>';
        
        // Extraer solo el body del HTML generado por PHPWord si viene con tags completos
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $htmlContent, $matches)) {
            $html .= $matches[1];
        } else {
            $html .= $htmlContent;
        }
        
        $html .= '</body></html>';

        error_log("Asmel Document Converter: HTML generado con Writer nativo. Iniciando Dompdf...");
        
        // Inicializar Dompdf
        $dompdf = new \Dompdf\Dompdf();
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans'); // Fuente que soporta mejor UTF-8
        $options->set('isRemoteEnabled', true); // Permitir imágenes remotas si es necesario
        $options->set('isHtml5ParserEnabled', true);
        $dompdf->setOptions($options);

        // Cargar HTML
        $dompdf->loadHtml($html, 'UTF-8');

        // Configurar papel y orientación
        $dompdf->setPaper('A4', 'portrait');

        // Renderizar
        $dompdf->render();

        // Guardar PDF
        $output = $dompdf->output();
        
        // Asegurar que el archivo anterior se elimine si existe (aunque file_put_contents sobrescribe)
        if (file_exists($pdf_path)) {
            unlink($pdf_path);
            error_log("Asmel Document Converter: Archivo PDF anterior eliminado para sobrescritura.");
        }
        
        if (file_put_contents($pdf_path, $output) !== false) {
            error_log("Asmel Document Converter: PDF guardado exitosamente en '$pdf_path'.");
            return true;
        } else {
            error_log("Asmel Document Converter: Error al escribir el archivo PDF en '$pdf_path'.");
            return false;
        }

    } catch (\Exception $e) {
        $msg = 'Error técnico al convertir (Excepción): ' . $e->getMessage();
        error_log('Asmel Document Converter: ' . $msg);
        // Guardar el error específico para mostrarlo al usuario
        if (function_exists('get_current_user_id')) {
            set_transient('asmel_informe_error_' . get_current_user_id(), $msg, 45);
        }
        return false;
    } catch (\Error $e) {
         $msg = 'Error fatal al convertir: ' . $e->getMessage();
         error_log('Asmel Document Converter: ' . $msg);
         if (function_exists('get_current_user_id')) {
            set_transient('asmel_informe_error_' . get_current_user_id(), $msg, 45);
         }
         return false;
    }
}

/**
 * Convierte un archivo .doc/.docx a PDF usando PHPWord y Dompdf.
 *
 * @param string $doc_path Ruta completa al archivo .doc/.docx de origen.
 * @param string $pdf_path Ruta completa donde se guardará el PDF.
 * @return bool True si la conversión fue exitosa, false en caso contrario.
 */
function asmel_convert_doc_to_pdf($doc_path, $pdf_path) {
    // 1. Cargar el documento usando el lector específico
    $phpWord = asmel_load_word_document($doc_path);
    
    if (!$phpWord) {
        error_log("Asmel Document Converter: No se pudo cargar el documento desde '$doc_path'.");
        return false;
    }

    // 2. Convertir el documento PHPWord a PDF
    return asmel_convert_phpword_to_pdf($phpWord, $pdf_path);
}

/**
 * Procesa la conversión cuando se guarda (o actualiza) un informe.
 * Hook principal para la funcionalidad.
 *
 * @param int     $post_id ID del post.
 * @param WP_Post $post    Objeto del post.
 * @param bool    $update  Si es una actualización.
 */
function asmel_process_informe_conversion($post_id, $post, $update) {
    // Evitar ejecuciones innecesarias
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!isset($_POST['post_type']) || $_POST['post_type'] !== 'informe') {
        return;
    }

    // Verificar permisos básicos
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Verificar nonce para seguridad (asumiendo que tu metabox lo tiene)
    // Mantener la validacion de nonce para proteger el guardado del informe.
    // if (!isset($_POST['asmel_informe_nonce']) || !wp_verify_nonce($_POST['asmel_informe_nonce'], 'asmel_save_informe')) {
    //     error_log('Asmel Document Converter: Nonce inválido o ausente.');
    //     return;
    // }


    error_log("Asmel Document Converter: Iniciando proceso para el informe ID $post_id...");

    // 1. Obtener metadatos del informe
    $paciente_nombre   = get_post_meta($post_id, '_informe_paciente_nombre', true);
    $fecha_creacion    = get_post_meta($post_id, '_informe_fecha_creacion', true);
    $numero_cliente    = get_post_meta($post_id, '_informe_numero_cliente', true);
    $tipo_informe      = get_post_meta($post_id, '_informe_tipo', true);
    $documento_url     = get_post_meta($post_id, '_informe_documento', true); // Esta es la URL del .doc/.docx

    // 2. Validar datos requeridos
    $missing_fields = [];
    if (empty($paciente_nombre)) $missing_fields[] = '_informe_paciente_nombre';
    if (empty($fecha_creacion)) $missing_fields[] = '_informe_fecha_creacion';
    if (empty($numero_cliente)) $missing_fields[] = '_informe_numero_cliente';
    if (empty($tipo_informe)) $missing_fields[] = '_informe_tipo';
    if (empty($documento_url)) $missing_fields[] = '_informe_documento (URL del archivo)';

    if (!empty($missing_fields)) {
        error_log("Asmel Document Converter: Faltan campos requeridos para la conversión en el informe $post_id: " . implode(', ', $missing_fields));
        // Considerar mostrar un mensaje de administración aquí si es necesario.
        return;
    }

    // 3. Verificar si ya existe un PDF y si se debe regenerar
    $pdf_url_existente = get_post_meta($post_id, '_informe_pdf', true);
    $pdf_id_existente  = get_post_meta($post_id, '_informe_pdf_id', true);

    if (!ASMEL_FORCE_PDF_REGENERATION && !empty($pdf_url_existente)) {
        // Verificar si el archivo físico existe
        $upload_dir = wp_upload_dir();
        $pdf_path_existente = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $pdf_url_existente);
        if (file_exists($pdf_path_existente)) {
            error_log("Asmel Document Converter: PDF ya existe para el informe $post_id y ASMEL_FORCE_PDF_REGENERATION es false. Saltando conversión.");
            return; // Ya existe y no se fuerza regeneración
        } else {
            error_log("Asmel Document Converter: PDF registrado pero archivo físico no encontrado. Regenerando...");
             // Limpiar metadatos inválidos
             delete_post_meta($post_id, '_informe_pdf');
             delete_post_meta($post_id, '_informe_pdf_id');
        }
    }

    // 4. Obtener la ruta del archivo .doc/.docx
    $upload_dir = wp_upload_dir();
    $documento_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $documento_url);

    if (!file_exists($documento_path)) {
        error_log("Asmel Document Converter: El archivo fuente '$documento_path' no existe en el sistema de archivos.");
        // Considerar mostrar un mensaje de administración aquí si es necesario.
        return;
    }

    // 5. Generar nombre del archivo PDF de destino
    $pdf_filename = asmel_generate_filename($paciente_nombre, $fecha_creacion, $numero_cliente, $tipo_informe);
    
    // 6. Obtener la ruta base del cliente
    $client_dir = asmel_get_client_directory($numero_cliente);
    $pdf_path = $client_dir . $pdf_filename;
    $pdf_url_destino = home_url('/') . 'Asmel/informes/' . $numero_cliente . '/' . $pdf_filename;

    // Loggear información de depuración
    error_log("Asmel Document Converter: Datos para conversión:");
    error_log("  - Paciente: $paciente_nombre");
    error_log("  - Fecha: $fecha_creacion");
    error_log("  - Cliente: $numero_cliente");
    error_log("  - Tipo: $tipo_informe");
    error_log("  - Doc Origen (URL): $documento_url");
    error_log("  - Doc Origen (Path): $documento_path");
    error_log("  - PDF Destino (Path): $pdf_path");
    error_log("  - PDF Destino (URL): $pdf_url_destino");

    // 7. Realizar la conversión
    if (asmel_convert_doc_to_pdf($documento_path, $pdf_path)) {
        error_log("Asmel Document Converter: Conversión exitosa.");

        // 8. Crear attachment de WordPress para el PDF generado
        // Nota: Este paso es opcional si no necesitas gestionar el PDF como un attachment de WordPress.
        // Si quieres mantener la compatibilidad con WordPress, puedes omitirlo.
        /*
        $attachment_args = array(
            'guid'           => $pdf_url_destino,
            'post_mime_type' => 'application/pdf',
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($pdf_filename)),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        require_once(ABSPATH . 'wp-admin/includes/image.php'); // Asegurar que image.php esté cargado

        $attach_id = wp_insert_attachment($attachment_args, $pdf_path, $post_id);

        if (!is_wp_error($attach_id)) {
            // Generar metadata para el attachment (miniaturas, etc.)
            $attach_data = wp_generate_attachment_metadata($attach_id, $pdf_path);
            wp_update_attachment_metadata($attach_id, $attach_data);

            // 9. Guardar las URLs/IDs en los metadatos del informe
            update_post_meta($post_id, '_informe_pdf', $pdf_url_destino);
            update_post_meta($post_id, '_informe_pdf_id', $attach_id);

            error_log("Asmel Document Converter: Attachment de PDF creado con ID $attach_id.");
            // Punto de extension para notificaciones en el panel de administracion.
            // add_action('admin_notices', function() { echo '<div class="notice notice-success"><p>PDF generado exitosamente para el informe.</p></div>'; });

        } else {
            error_log("Asmel Document Converter: Error al crear attachment de WordPress: " . $attach_id->get_error_message());
            // Punto de extension para manejo de errores de attachment.
        }
        */

        // 9. Guardar la URL del PDF en los metadatos del informe (sin attachment)
        update_post_meta($post_id, '_informe_pdf', $pdf_url_destino);
        
        // 10. COPIAR EL ARCHIVO ORIGINAL (.doc/.docx) A LA CARPETA DEL CLIENTE
        $extension_origen = pathinfo($documento_path, PATHINFO_EXTENSION);
        // Usar el mismo nombre base que el PDF pero con la extensión original
        $doc_filename_destino = pathinfo($pdf_filename, PATHINFO_FILENAME) . '.' . $extension_origen;
        $doc_path_destino = $client_dir . $doc_filename_destino;
        
        if (copy($documento_path, $doc_path_destino)) {
            error_log("Asmel Document Converter: Archivo original copiado a: $doc_path_destino");
        } else {
            error_log("Asmel Document Converter: Error al copiar el archivo original a: $doc_path_destino");
        }
        
        error_log("Asmel Document Converter: PDF generado y guardado en la carpeta del cliente: $pdf_path");
        
        // Guardar mensaje de éxito en transient para mostrarlo después de la recarga
        set_transient('asmel_informe_success_' . get_current_user_id(), "El archivo fue convertido a PDF y almacenado en la carpeta \"$numero_cliente\" correctamente.", 45);

    } else {
        error_log("Asmel Document Converter: La función de conversión devolvió false. No se generó el PDF.");
        // Solo poner mensaje genérico si no se ha puesto uno específico
        if (!get_transient('asmel_informe_error_' . get_current_user_id())) {
            set_transient('asmel_informe_error_' . get_current_user_id(), "Error al generar el PDF. Consulte los logs.", 45);
        }
    }
}

// Enganchar la función al evento de guardar el post de tipo 'informe'
// Usamos una prioridad alta (100) para ejecutarlo después de que se guarden todos los metadatos.
add_action('save_post_informe', 'asmel_process_informe_conversion', 100, 3);


// --- Funciones auxiliares (opcionales) ---

/**
 * Shortcode para mostrar botón de descarga de PDF.
 * Uso: [asmel_informe_pdf_download post_id="123"]
 */
function asmel_informe_pdf_download_shortcode($atts) {
    $atts = shortcode_atts(array(
        'post_id' => get_the_ID()
    ), $atts, 'asmel_informe_pdf_download');

    $post_id = intval($atts['post_id']);

    if (get_post_type($post_id) !== 'informe') {
        return '<!-- Shortcode asmel_informe_pdf_download: Post no es de tipo informe -->';
    }

    $pdf_url = get_post_meta($post_id, '_informe_pdf', true);

    if (empty($pdf_url)) {
        return '<span class="asmel-pdf-unavailable">PDF no disponible</span>';
    }

    return '<a href="' . esc_url($pdf_url) . '" target="_blank" class="button asmel-download-pdf">Descargar PDF</a>';
}
add_shortcode('asmel_informe_pdf_download', 'asmel_informe_pdf_download_shortcode');


// --- Verificación de instalación ---

/**
 * Verifica e informa sobre el estado de las librerías al cargar el admin.
 */
function asmel_check_document_libraries_admin_notice() {
    // Solo mostrar en el admin
    if (!is_admin()) {
        return;
    }

    // Verificar librerías
    $phpword_ok = class_exists('\\PhpOffice\\PhpWord\\IOFactory');
    $dompdf_ok = class_exists('\\Dompdf\\Dompdf');

    if (!$phpword_ok || !$dompdf_ok) {
        $missing = [];
        if (!$phpword_ok) $missing[] = 'PHPWord';
        if (!$dompdf_ok) $missing[] = 'Dompdf';

        echo '<div class="notice notice-warning"><p><strong>Asmel - Document Converter:</strong> Las siguientes librerías no están cargadas: ' . implode(', ', $missing) . '. La conversión de documentos .doc/.docx a PDF no funcionará. Verifique que Composer se haya ejecutado correctamente y que <code>vendor/autoload.php</code> esté incluido en <code>wp-config.php</code>.</p></div>';
    }
}
add_action('admin_notices', 'asmel_check_document_libraries_admin_notice');

/**
 * Mostrar notificaciones de éxito/error después de la conversión
 */
add_action('admin_notices', 'asmel_show_informe_notices');
/**
 * Ejecuta show informe notices.
 * @return mixed Resultado de la función.
 */
function asmel_show_informe_notices() {
    $user_id = get_current_user_id();
    if ($msg = get_transient('asmel_informe_success_' . $user_id)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        delete_transient('asmel_informe_success_' . $user_id);
    }
    if ($msg = get_transient('asmel_informe_error_' . $user_id)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        delete_transient('asmel_informe_error_' . $user_id);
    }
}


