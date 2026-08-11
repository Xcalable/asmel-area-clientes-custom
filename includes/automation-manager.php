<?php
/**
 * Asmel Automation Manager
 * Maneja la detección de archivos ZIP (Datos y Documentos) y su procesamiento automático.
 */

if (!defined('ABSPATH')) exit;

require_once get_stylesheet_directory() . '/includes/class-dbf-reader.php';

// --- NUEVO: Página de TEST en Admin ---
add_action('admin_menu', 'asmel_add_automation_test_page');
/**
 * Ejecuta add automation test page.
 * @return mixed Resultado de la funcion.
 */
function asmel_add_automation_test_page() {
    add_submenu_page(
        'tools.php', // Bajo el menú Herramientas
        'Test Automatización Asmel',
        'Test Sync Asmel',
        'manage_options',
        'asmel-automation-test',
        'asmel_render_automation_test_page'
    );
}

/**
 * Ejecuta render automation test page.
 * @return mixed Resultado de la funcion.
 */
function asmel_render_automation_test_page() {
    $result_message = '';
    
    // Procesar acción manual
    if (isset($_POST['run_sync']) && check_admin_referer('asmel_run_sync_nonce')) {
        asmel_run_automation_process(); // Ejecutar el proceso
        $result_message = '<div class="notice notice-success is-dismissible"><p>Proceso de sincronización ejecutado. Revisa los logs de error (debug.log) o la carpeta /procesados/ para verificar resultados.</p></div>';
    }
    
    // Ruta donde busca
    // Configuración para servidor producción:
    $source_dir = '/path/to/asmel-data/';
    
    // Si la ruta del servidor no existe (estamos en local WAMP/XAMPP), usar carpeta relativa al WP
    if (!is_dir($source_dir)) {
        $source_dir = ABSPATH . 'clientes/';
    }
    
    // Asegurar trailing slash
    $source_dir = trailingslashit($source_dir);

    $file_count = count(glob($source_dir . '*.zip'));

    ?>
    <div class="wrap">
        <h1>Test de Automatización Asmel (SFTP/Local)</h1>
        <?php echo $result_message; ?>
        
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2>Estado Actual</h2>
            <p><strong>Carpeta vigilada:</strong> <code><?php echo esc_html($source_dir); ?></code></p>
            <p><strong>Archivos ZIP pendientes:</strong> <?php echo $file_count; ?></p>
            
            <hr>
            
            <p>Este botón fuerza la ejecución inmediata del script que normalmente corre automáticamente (Cron Job). Úsalo para probar si los archivos ZIP se procesan correctamente.</p>
            
            <form method="post">
                <?php wp_nonce_field('asmel_run_sync_nonce'); ?>
                <input type="submit" name="run_sync" class="button button-primary button-hero" value="Ejecutar Sincronización Ahora">
            </form>
        </div>
    </div>
    <?php
}

// Programar el Cron Job automaticamente
if (!wp_next_scheduled('asmel_daily_sync_event')) {
    wp_schedule_event(time(), 'hourly', 'asmel_daily_sync_event');
}

add_action('asmel_daily_sync_event', 'asmel_run_automation_process');

/**
 * Función principal del Cron Job
 */
function asmel_run_automation_process() {
    // --- NUEVO: Capturar Errores Fatales que matan el script ---
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error !== null && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR || $error['type'] === E_CORE_ERROR)) {
            $msg = "Asmel Automation CRASH FATAL: " . $error['message'] . " en " . $error['file'] . ":" . $error['line'];
            error_log($msg);
            // Intentar escribir un archivo de bloqueo/error en disco si es posible
            if (defined('ABSPATH')) {
                file_put_contents(ABSPATH . 'asmel_fatal_crash.log', date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, FILE_APPEND);
            }
        }
    });

    // --- BLINDAJE ANTI-CRASH ---
    @ini_set('memory_limit', '1024M'); // 1GB de memoria
    @set_time_limit(0); // Tiempo ilimitado
    @ini_set('display_errors', 1); // Mostrar errores en pantalla (útil para test manual)
    // Ocultar avisos de DEPRECATED que genera la librería PhpWord antigua en PHP 8+
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
    
    // Validar carga de dependencias críticas antes de empezar
    if (!function_exists('asmel_get_client_directory')) {
        $doc_converter_path = get_stylesheet_directory() . '/includes/document-converter.php';
        if (file_exists($doc_converter_path)) {
            require_once $doc_converter_path;
        } else {
            error_log("Asmel Automation Error CRÍTICO: No se encontró document-converter.php en $doc_converter_path");
            echo "<div class='error'><p>Error: Falta el archivo document-converter.php</p></div>";
            return;
        }
    }

    // Definir directorios
    // Configuración para servidor producción: /path/to/asmel-data/
    $source_dir = '/path/to/asmel-data/';
    
    // Fallback para entorno local/dev si no existe la ruta absoluta del servidor
    if (!is_dir($source_dir)) {
        $source_dir = ABSPATH . 'clientes/'; 
    }
    
    // Asegurar trailing slash
    $source_dir = trailingslashit($source_dir);

    $processed_dir = $source_dir . 'procesados/';
    $error_dir = $source_dir . 'errores/';

    // Crear directorios si no existen
    if (!file_exists($processed_dir)) mkdir($processed_dir, 0755, true);
    if (!file_exists($error_dir)) mkdir($error_dir, 0755, true);

    // --- DETECTOR TIME-SLICE ---
    // Definimos el tiempo de inicio de la ejecucion actual para controlar timeouts
    // Usaremos una global o constante para que sea accesible en todo el ambito
    if (!defined('ASMEL_EXECUTION_START')) {
        define('ASMEL_EXECUTION_START', microtime(true));
    }
    
    // Configurar limite de seguridad: 
    // Si el script lleva mas de 45 segundos, debe parar y re-agendarse.
    // Esto es vital en hostings compartidos donde max_execution_time real suele ser 60-120s.
    $max_exec_time = 45; 

    // Buscar archivos ZIP
    $zip_files = glob($source_dir . '*.zip');

    if (empty($zip_files)) return;

    // --- ORDENAR POR FECHA (Mas antiguo primero) ---
    // Importante para procesar en orden FIFO y respetar la cola
    usort($zip_files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });

    $docs_processed_count = 0; // Contador para limitar procesamiento de ZIPs de documentos pesados
    $re_schedule_needed = false; // Flag para saber si necesitamos re-programar ejecucion INMEDIATA

    foreach ($zip_files as $zip_path) {
        // Verificar si nos estamos quedando sin tiempo ANTES de empezar otro ZIP
        if ((microtime(true) - ASMEL_EXECUTION_START) > $max_exec_time) {
            error_log("Asmel Automation: Tiempo limite global alcanzado. Re-progamando ejecucion inmediata.");
            $re_schedule_needed = true;
            break; 
        }

        $filename = basename($zip_path);
        
        // Determinar tipo de ZIP por nombre (convencion simple o inspeccion de contenido)
        // Asumiremos que si contiene .dbf es de DATOS, si contiene .doc es de DOCUMENTOS
        $type = asmel_detect_zip_type($zip_path);

        // --- FILTRO DE TARIFA PLANA ---
        // Si es de tipo DOCUMENTOS y ya procesamos 1 en este ciclo, saltar al siguiente archivo.
        if ($type === 'docs' && $docs_processed_count >= 1) {
            error_log("Asmel Automation: Límite de 1 ZIP de documentos por hora alcanzado. '$filename' quedara en cola.");
            continue; 
        }

        $success = false;
        $partial_process = false; // Indica si el ZIP fue procesado PARCIALMENTE (se acabo el tiempo)
        
        if ($type === 'data') {
            error_log("Asmel Automation: Procesando ZIP de DATOS: $filename");
            $success = asmel_process_data_zip($zip_path);
        } elseif ($type === 'docs') {
            error_log("Asmel Automation: Procesando ZIP de DOCUMENTOS (Time-Sliced): $filename");
            // Pasamos el limite de tiempo a la funcion de documentos
            $result = asmel_process_docs_zip($zip_path, $max_exec_time, $error_dir);
            
            if ($result === 'timeout') {
                $partial_process = true;
                $re_schedule_needed = true;
                $docs_processed_count++; // Contamos como "trabajado" para no empezar otro DOC pesado ahora
                error_log("Asmel Automation: ZIP de documentos interrumpido por TIME LIMIT. Se continuara en breve.");
            } else {
                $success = $result; // true o false
                $docs_processed_count++;
            }
        } else {
            error_log("Asmel Automation: Tipo de ZIP desconocido o vacio: $filename");
        }

        // Mover archivo final SOLO si terminó completo (success=true)
        if ($success && !$partial_process) {
            rename($zip_path, $processed_dir . $filename);
            error_log("Asmel Automation: ZIP procesado COMPLETAMENTE y movido a procesados/.");
        } elseif (!$success && !$partial_process) {
            // Error completo (no timeout)
            rename($zip_path, $error_dir . $filename);
            error_log("Asmel Automation: ERROR al procesar ZIP. Movido a errores/.");
        } elseif ($partial_process) {
            // Si fue parcial, NO movemos nada. El ZIP original ya fue modificado (archivos borrados dentro).
            // Se procesara el resto en la siguiente ejecucion.
        }
    }

    // --- RE-AGENDAMIENTO INTELIGENTE ---
    // Si se activo el flag de re-schedule (por timeout), programamos un evento "single" para dentro de 2 minutos.
    if ($re_schedule_needed) {
        if (!wp_next_scheduled('asmel_single_sync_continuation')) {
            wp_schedule_single_event(time() + 120, 'asmel_single_sync_continuation');
            error_log("Asmel Automation: Programada continuacion de proceso en 2 minutos.");
        }
    }

    // --- LIMPIEZA AUTOMÁTICA ---
    // Borrar archivos en 'procesados' y 'errores' si tienen más de 7 días.
    asmel_cleanup_directory($processed_dir, 7);
    asmel_cleanup_directory($error_dir, 7);
}

// Hook para la continuacion inmediata (mismo handler)
add_action('asmel_single_sync_continuation', 'asmel_run_automation_process');

/**
 * Función auxiliar para borrar archivos antiguos de una carpeta
 */
function asmel_cleanup_directory($dir, $days) {
    if (!is_dir($dir)) return;

    $files = glob($dir . '*'); // Obtener todos los archivos
    $now = time();
    $threshold = $days * 24 * 60 * 60; // Días a segundos
    
    foreach ($files as $file) {
        if (is_file($file)) {
            // Verificar antigüedad
            if ($now - filemtime($file) >= $threshold) {
                unlink($file); // Borrar
                error_log("Asmel Cleanup: Eliminado archivo antiguo " . basename($file) . " de " . basename($dir));
            }
        }
    }
}

/**
 * Inspecciona el ZIP para ver si trae DBFs o DOCs
 */
function asmel_detect_zip_type($zip_path) {
    $zip = new ZipArchive;
    if ($zip->open($zip_path) === TRUE) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if ($ext === 'dbf') {
                $zip->close();
                return 'data';
            }
            if ($ext === 'doc' || $ext === 'docx') {
                $zip->close();
                return 'docs';
            }
        }
        $zip->close();
    }
    return 'unknown';
}

/**
 * Procesa ZIP de Documentos (Reutiliza la lógica de ajax-file-upload mejorada)
 * Soporta Time-Slicing: Si se pasa $max_exec_time, retorna 'timeout'.
 */
function asmel_process_docs_zip($zip_path, $max_exec_time = 0, $error_dir = '') {
    if (!function_exists('asmel_get_client_directory')) {
        require_once get_stylesheet_directory() . '/includes/document-converter.php';
    }

    $zip = new ZipArchive;
    if ($zip->open($zip_path) !== TRUE) return false;

    // Estrategia: Iterar sobre el ZIP sin descomprimirlo todo (para ahorrar espacio y tiempo)
    // Procesar uno a uno y marcar para borrado.
    
    $processed_files_in_zip = []; // Lista de archivos internos a eliminar del ZIP al final
    $timeout_reached = false;
    $count_ok = 0;
    $numFiles = $zip->numFiles;

    for ($i = 0; $i < $numFiles; $i++) {
        // 1. Chequeo de Timeout Global
        if ($max_exec_time > 0 && defined('ASMEL_EXECUTION_START')) {
            if ((microtime(true) - ASMEL_EXECUTION_START) > $max_exec_time) {
                $timeout_reached = true;
                break; // Salir del bucle para guardar y re-agendar
            }
        }

        // 2. Obtener info del archivo
        $stat = $zip->statIndex($i);
        $filename = $stat['name'];
        $clean_filename = basename($filename); // Para parsear nombre
        
        // Ignorar carpetas, ocultos y __MACOSX
        if ($filename[strlen($filename)-1] == '/') continue;
        if (strpos($filename, '__MACOSX') !== false) continue;
        if (strpos($clean_filename, '.') === 0) continue; // Archivos ocultos .DS_Store etc

        $ext = strtolower(pathinfo($clean_filename, PATHINFO_EXTENSION));
        if (!in_array($ext, ['doc', 'docx'])) continue;

        // 3. Extraer SOLO este archivo a un temporal
        $content = $zip->getFromIndex($i);
        if ($content === false) continue;

        $temp_file = get_temp_dir() . 'asmel_temp_' . uniqid() . '.' . $ext;
        file_put_contents($temp_file, $content);
        
        // 4. Procesar conversión (Lógica original)
        // Ejemplo: ABADIA_FACUNDO_EMANUEL2026012700900701.doc
        // Acepta letras españolas: ñ, Ñ, tildes (Á É Í Ó Ú Ü y minúsculas)
        if (preg_match('/^([A-ZÁÉÍÓÚÜÑ0-9_]+)(\d{8})(\d{6})(\d{2})\.(doc|docx)$/iu', $clean_filename, $match)) {
            $paciente_nombre = str_replace('_', ' ', $match[1]);
            $fecha_creacion = substr($match[2], 0, 4) . '-' . substr($match[2], 4, 2) . '-' . substr($match[2], 6, 2);
            $numero_cliente = $match[3];
            $tipo_informe = $match[4];

            $pdf_filename = asmel_generate_filename($paciente_nombre, $fecha_creacion, $numero_cliente, $tipo_informe);
            $client_dir = asmel_get_client_directory($numero_cliente);
            $pdf_path = $client_dir . $pdf_filename;

            if (asmel_convert_doc_to_pdf($temp_file, $pdf_path)) {
                $count_ok++;
                $processed_files_in_zip[] = $filename; // Guardar path interno exacto para borrado
            } else {
                // Falló la conversión a PDF: guardar el .docx suelto en errores/ para revisión manual
                error_log("Asmel Automation: Falló conversión a PDF: $clean_filename");
                if (!empty($error_dir) && is_dir($error_dir)) {
                    copy($temp_file, trailingslashit($error_dir) . $clean_filename);
                    error_log("Asmel Automation: Archivo fallido guardado en errores/: $clean_filename");
                }
                $processed_files_in_zip[] = $filename; // Sacar del ZIP para no bloquear el ciclo
            }
        } else {
            // Nombre no cumple el formato: guardar el .docx suelto en errores/ para revisión manual
            error_log("Asmel Automation: Archivo con nombre inválido en ZIP: $clean_filename");
            if (!empty($error_dir) && is_dir($error_dir)) {
                copy($temp_file, trailingslashit($error_dir) . $clean_filename);
                error_log("Asmel Automation: Archivo inválido guardado en errores/: $clean_filename");
            }
            $processed_files_in_zip[] = $filename; // Sacar del ZIP para no bloquear el ciclo
        }

        // 5. Limpiar temporal
        if (file_exists($temp_file)) unlink($temp_file);
        
        // 6. Liberar memoria
        if (function_exists('gc_collect_cycles')) gc_collect_cycles();
    }

    $zip->close(); // Cerrar modo lectura

    // --- FASE DE ACTUALIZACIÓN DEL ZIP ORIGINAL ---
    if (!empty($processed_files_in_zip)) {
        // Reabrimos el ZIP para modificarlo (Borrar los ya procesados)
        $zipWriter = new ZipArchive;
        // Intentar abrir. En Windows a veces hay lock instantáneo, reintentar.
        $opened = false;
        for ($retry=0; $retry<3; $retry++) {
            if ($zipWriter->open($zip_path) === TRUE) {
                $opened = true;
                break;
            }
            sleep(1);
        }

        if ($opened) {
            foreach ($processed_files_in_zip as $file_to_del) {
                $zipWriter->deleteName($file_to_del);
            }
            $zipWriter->close();
            error_log("Asmel Automation: Eliminados " . count($processed_files_in_zip) . " archivos procesados del ZIP original (Time-Slice).");
        } else {
            error_log("Asmel Automation CRITICAL: No se pudo abrir el ZIP para eliminar archivos procesados. Se repetirán en el próximo ciclo.");
        }
    }

    if ($timeout_reached) return 'timeout';

    // Verificar si el ZIP quedó "vacío" (de archivos útiles)
    $zipCheck = new ZipArchive;
    $remaining_useful_files = 0;
    if ($zipCheck->open($zip_path) === TRUE) {
        for ($k = 0; $k < $zipCheck->numFiles; $k++) {
            $fname = $zipCheck->getNameIndex($k);
            if (strpos($fname, '__MACOSX') === false && substr($fname, -1) !== '/' && preg_match('/\.(doc|docx)$/i', $fname)) {
                $remaining_useful_files++;
            }
        }
        $zipCheck->close();
    }

    // Si ya no quedan archivos DOC/DOCX útiles, se considera ÉXITO total para mover el ZIP a procesados (o borrarlo)
    if ($remaining_useful_files === 0) return true;

    // Si quedan archivos y NO hubo timeout, significa que eran archivos inválidos o corruptos. 
    // Devolvemos false para que el ZIP se mueva a 'errores' si corresponde, o true si al menos procesamos algo.
    // Para ser conservadores: Si procesamos algo, true.
    return ($count_ok > 0);
}

/**
 * Procesa ZIP de Datos (DBF -> MySQL)
 */
function asmel_process_data_zip($zip_path) {
    $zip = new ZipArchive;
    if ($zip->open($zip_path) !== TRUE) return false;

    $temp_dir = get_temp_dir() . 'asmel_data_' . uniqid() . '/';
    if (!file_exists($temp_dir)) mkdir($temp_dir, 0755, true);

    $zip->extractTo($temp_dir);
    $zip->close();

    $success = true;
    
    // Buscar los DBF específicos
    if (file_exists($temp_dir . 'CONSULTA.DBF')) {
        if (!asmel_import_counsultas_dbf($temp_dir . 'CONSULTA.DBF')) $success = false;
    }

    if (file_exists($temp_dir . 'PACIENTE.DBF')) {
        if (!asmel_import_pacientes_dbf($temp_dir . 'PACIENTE.DBF')) $success = false;
    }
    
    // Limpiar
    array_map('unlink', glob("$temp_dir/*.*"));
    rmdir($temp_dir);

    return $success;
}

/**
 * Importa CONSULTA.DBF a MySQL
 */
function asmel_import_counsultas_dbf($dbf_path) {
    try {
        $reader = new Asmel_DBF_Reader($dbf_path);
    } catch (Exception $e) {
        error_log("Asmel DBF Error: " . $e->getMessage());
        return false;
    }

    // --- DETECCIÓN DE MODO TEST PARA LOGS ---
    // (Opcional) Puedes dejarlo activo o desactivarlo comentando líneas de echo si prefieres silencio total
    $is_test_mode = (isset($_GET['page']) && $_GET['page'] === 'asmel-automation-test');
    
    $conn = asmel_connect_to_external_db(); // Función en mu-plugins
    if (!$conn) {
        $msg = "Asmel DB Error: No se pudo conectar a la BD externa. Verifica credenciales.";
        error_log($msg);
        if ($is_test_mode) echo "<div class='notice notice-error'><p>$msg</p></div>";
        return false;
    } 

    $count = 0;
    while ($record = $reader->next_record()) {
        
        // Helper para buscar campos de forma flexible (por si vienen como PACIENTES en vez de PACIENTE)
        $get_val = function($keys_to_try) use ($record) {
            if (!is_array($keys_to_try)) $keys_to_try = [$keys_to_try];
            foreach ($keys_to_try as $k) {
                // Busqueda exacta
                if (isset($record[$k])) return $record[$k];
                // Busqueda aproximada (empieza por)
                foreach (array_keys($record) as $actual_key) {
                    if (strpos($actual_key, $k) === 0) return $record[$actual_key];
                }
            }
            return null;
        };

        // Mapeo flexible basado en lo visto en debug
        $fecha      = $get_val(['FECHA', 'FECHATES']); 
        $clientedid = $get_val(['CLIENTE', 'CLIENTED', 'CLIENTEDOC']); // ID de Cliente 009007
        $paciente   = $get_val(['PACIENTE', 'PACIENTES']);
        $resultado  = $get_val('RESULTADO');
        $trabaja    = $get_val('TRABAJA');
        $citado     = $get_val('CITADO');
        $consulta_val = $get_val('CONSULTA'); 
        
        $dias_val   = $get_val('DIAS');
        $dias       = is_numeric($dias_val) ? (int)$dias_val : 0;
        
        $ampliacion = $get_val('AMPLIACION');
        
        // Sanitización para SQL
        $fecha      = $fecha ? $fecha : ''; 
        $paciente   = $conn->real_escape_string($paciente ? $paciente : '');

        $resultado  = $conn->real_escape_string($resultado ? $resultado : '');
        $trabaja    = $trabaja ? $trabaja : null;
        $citado     = $citado ? $citado : null;
        $ampliacion = $conn->real_escape_string($ampliacion ? $ampliacion : '');
        $cliente_id = $conn->real_escape_string($clientedid ? $clientedid : '');

        // Ajuste de Fechas (YYYYMMDD -> YYYY-MM-DD)
        if ($fecha && strlen($fecha) == 8)      $fecha = substr($fecha,0,4).'-'.substr($fecha,4,2).'-'.substr($fecha,6,2);
        if ($trabaja && strlen($trabaja) == 8)  $trabaja = substr($trabaja,0,4).'-'.substr($trabaja,4,2).'-'.substr($trabaja,6,2);
        if ($citado && strlen($citado) == 8)    $citado = substr($citado,0,4).'-'.substr($citado,4,2).'-'.substr($citado,6,2);

        // --- LÓGICA DE ID DE CONSULTA ---
        // Si el campo 'CONSULTA' trae texto como 'DOMICILIO', no sirve como Primary Key numérica.
        // Si la tabla espera un string en 'CONSULTA', ok. Pero si espera ID unico...
        // Asumiremos que el valor que venga se guarda tal cual en la columna CONSULTA.
        $consulta_id_safe = $conn->real_escape_string($consulta_val ? $consulta_val : '');

        // Validar si tenemos datos mínimos para insertar (ej: evitar insertar filas vacías)
        if (empty($fecha) && empty($paciente) && empty($cliente_id)) {
             continue; // Saltar filas vacías/basura
        }

        // Query INSERT / UPDATE
        $sql = "INSERT INTO CONSULTA (FECHA, CONSULTA, PACIENTE, RESULTADO, TRABAJA, CITADO, DIAS, AMPLIACION, CLIENTE)
                VALUES ('$fecha', '$consulta_id_safe', '$paciente', '$resultado', '$trabaja', '$citado', $dias, '$ampliacion', '$cliente_id')
                ON DUPLICATE KEY UPDATE 
                FECHA='$fecha', PACIENTE='$paciente', RESULTADO='$resultado', TRABAJA='$trabaja', CITADO='$citado', DIAS=$dias, AMPLIACION='$ampliacion'";

        $conn->query($sql);
        if ($conn->error) {
            $err_msg = "Asmel MySQL Insert Error: " . $conn->error;
            error_log($err_msg);
            // Mostrar error solo si es crítico en modo test
            if ($is_test_mode) echo "<div class='notice notice-error'><p>$err_msg</p></div>";
        } else {
            $count++;
        }
    }

    $conn->close();
    $reader->close();
    
    $final_msg = "Asmel Import: Importados/Actualizados $count registros en CONSULTA.";
    error_log($final_msg);
    // Mostrar resumen final en pantalla pero SIN los detalles técnicos registro a registro
    if ($is_test_mode) echo "<div class='notice notice-success'><p>$final_msg</p></div>";

    return true;
}

/**
 * Importa PACIENTE.DBF a MySQL
 */
function asmel_import_pacientes_dbf($dbf_path) {
    // Implementación similar para tabla PACIENTE si es necesario
    return true;
}
?>


