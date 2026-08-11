<?php
// Dashboard del Cliente - Funcionalidades del área de clientes

// Función para obtener número de pacientes reales del cliente
/**
 * Ejecuta get numero pacientes real.
 *
 * @param mixed $numero_cliente Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_get_numero_pacientes_real($numero_cliente) {
    // Ruta base del sistema
    $base_path = '/path/to/asmel-data/informes/';
    
    // Verificar que el directorio del cliente exista
    $client_dir = $base_path . $numero_cliente . '/';
    
    if (!is_dir($client_dir)) {
        return 0; // No hay directorio para este cliente
    }
    
    // Contar archivos PDF en el directorio del cliente
    $pdf_files = glob($client_dir . '*.pdf');
    
    if ($pdf_files === false) {
        return 0; // Error al leer el directorio
    }
    
    return count($pdf_files);
}

// Función para obtener últimos archivos reales del cliente
/**
 * Ejecuta get ultimos archivos real.
 *
 * @param mixed $numero_cliente Parametro de entrada.
 * @param mixed $limite Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_get_ultimos_archivos_real($numero_cliente, $limite = 5) {
    // Ruta base del sistema
    $base_path = '/path/to/asmel-data/informes/';
    
    // Verificar que el directorio del cliente exista
    $client_dir = $base_path . $numero_cliente . '/';
    
    if (!is_dir($client_dir)) {
        return array(); // No hay directorio para este cliente
    }
    
    // Obtener todos los archivos PDF en el directorio del cliente
    $pdf_files = glob($client_dir . '*.pdf');
    
    if ($pdf_files === false || empty($pdf_files)) {
        return array(); // No hay archivos o error al leer el directorio
    }
    
    // Ordenar por fecha de modificación (más recientes primero)
    usort($pdf_files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Tomar solo los últimos $limite archivos
    $latest_files = array_slice($pdf_files, 0, $limite);
    
    // Procesar archivos para obtener información
    $archivos = array();
    foreach ($latest_files as $file_path) {
        $file_info = pathinfo($file_path);
        $filename = $file_info['filename'];
        
        // Extraer información del nombre del archivo
        // Formato: PACIENTEFECHAClienteTIPO.pdf
        // Ejemplo: PEREZ_JUAN_CARLOS2023101500000101.pdf
        
        // Extraer tipo (últimos 2 dígitos)
        $tipo_codigo = substr($filename, -2);
        $tipos = array(
            '01' => 'Médico',
            '02' => 'Preocupacional',
            '03' => 'Periódico',
            '04' => 'Egreso'
        );
        $tipo_nombre = isset($tipos[$tipo_codigo]) ? $tipos[$tipo_codigo] : 'Desconocido';
        
        // Extraer fecha (8 dígitos antes del tipo)
        $fecha_str = substr($filename, -16, 8); // 8 dígitos de fecha
        $fecha_formateada = date('d/m/Y', strtotime($fecha_str));
        
        // Extraer paciente (todo lo que está antes de la fecha)
        $paciente_str = substr($filename, 0, -16);
        $paciente_nombre = str_replace('_', ' ', $paciente_str);
        
        // Obtener tamaño del archivo
        $tamano_bytes = filesize($file_path);
        $tamano_kb = round($tamano_bytes / 1024, 2) . ' KB';
        
        // Construir URL pública CORREGIDA
        $file_url = str_replace('/path/to/public_html/clientes', home_url(), $file_path);
        
        $archivos[] = array(
            'fecha' => $fecha_formateada,
            'paciente' => $paciente_nombre,
            'tipo' => $tipo_nombre,
            'tipo_codigo' => $tipo_codigo,
            'tamano' => $tamano_kb,
            'url' => $file_url,
            'ruta_completa' => $file_path // Para descarga en ZIP
        );
    }
    
    return $archivos;
}

// Función para obtener todos los archivos del cliente
/**
 * Ejecuta obtener todos archivos cliente.
 *
 * @param mixed $numero_cliente Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_obtener_todos_archivos_cliente($numero_cliente) {
    // Ruta base del sistema
    $base_path = '/path/to/asmel-data/informes/';
    
    // Verificar que el directorio del cliente exista
    $client_dir = $base_path . $numero_cliente . '/';
    
    if (!is_dir($client_dir)) {
        return array(); // No hay directorio para este cliente
    }
    
    // Obtener todos los archivos PDF en el directorio del cliente
    $pdf_files = glob($client_dir . '*.pdf');
    
    if ($pdf_files === false || empty($pdf_files)) {
        return array(); // No hay archivos o error al leer el directorio
    }
    
    // Ordenar por fecha de modificación (más recientes primero)
    usort($pdf_files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Procesar archivos para obtener información
    $archivos = array();
    foreach ($pdf_files as $file_path) {
        $file_info = pathinfo($file_path);
        $filename = $file_info['filename'];
        
        // Extraer información del nombre del archivo
        // Formato: PACIENTEFECHAClienteTIPO.pdf
        // Ejemplo: PEREZ_JUAN_CARLOS2023101500000101.pdf
        
        // Extraer tipo (últimos 2 dígitos)
        $tipo_codigo = substr($filename, -2);
        $tipos = array(
            '01' => 'Médico',
            '02' => 'Preocupacional',
            '03' => 'Periódico',
            '04' => 'Egreso'
        );
        $tipo_nombre = isset($tipos[$tipo_codigo]) ? $tipos[$tipo_codigo] : 'Desconocido';
        
        // Extraer fecha (8 dígitos antes del tipo)
        $fecha_str = substr($filename, -16, 8); // 8 dígitos de fecha
        $fecha_formateada = date('d/m/Y', strtotime($fecha_str));
        
        // Extraer paciente (todo lo que está antes de la fecha)
        $paciente_str = substr($filename, 0, -16);
        $paciente_nombre = str_replace('_', ' ', $paciente_str);
        
        // Obtener tamaño del archivo
        $tamano_bytes = filesize($file_path);
        $tamano_kb = round($tamano_bytes / 1024, 2) . ' KB';
        
        // Construir URL pública CORREGIDA
        $file_url = str_replace('/path/to/public_html/clientes', home_url(), $file_path);
        
        $archivos[] = array(
            'fecha' => $fecha_formateada,
            'paciente' => $paciente_nombre,
            'tipo' => $tipo_nombre,
            'tipo_codigo' => $tipo_codigo,
            'tamano' => $tamano_kb,
            'url' => $file_url,
            'ruta_completa' => $file_path // Para descarga en ZIP
        );
    }
    
    return $archivos;
}

// Función para buscar archivos reales del cliente
/**
 * Ejecuta buscar archivos reales.
 *
 * @param mixed $numero_cliente Parametro de entrada.
 * @param mixed $nombre_paciente Parametro de entrada.
 * @param mixed $tipo_archivo Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_buscar_archivos_reales($numero_cliente, $nombre_paciente = '', $tipo_archivo = '') {
    // Obtener todos los archivos del cliente
    $todos_archivos = asmel_obtener_todos_archivos_cliente($numero_cliente);
    
    // Filtrar por nombre de paciente
    if (!empty($nombre_paciente)) {
        $todos_archivos = array_filter($todos_archivos, function($archivo) use ($nombre_paciente) {
            return stripos($archivo['paciente'], $nombre_paciente) !== false;
        });
    }
    
    // Filtrar por tipo de archivo
    if (!empty($tipo_archivo)) {
        $todos_archivos = array_filter($todos_archivos, function($archivo) use ($tipo_archivo) {
            return isset($archivo['tipo_codigo']) && $archivo['tipo_codigo'] === $tipo_archivo;
        });
    }
    
    return array_values($todos_archivos); // Reindexar array
}

// Procesar descarga de archivos en ZIP
add_action('admin_post_asmel_descargar_archivos_zip', 'asmel_procesar_descargar_archivos_zip');
add_action('admin_post_nopriv_asmel_descargar_archivos_zip', 'asmel_procesar_descargar_archivos_zip');
/**
 * Ejecuta procesar descargar archivos zip.
 * @return mixed Resultado de la funcion.
 */
function asmel_procesar_descargar_archivos_zip() {
    // Verificar si el usuario está logueado y es cliente
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        wp_die('Debes estar logueado como cliente para acceder a esta función.');
    }
    
    // Verificar nonce
    if (!isset($_POST['descargar_archivos_zip_nonce']) || !wp_verify_nonce($_POST['descargar_archivos_zip_nonce'], 'asmel_descargar_archivos_zip_nonce')) {
        wp_die('Error de seguridad.');
    }
    
    // Validar archivos seleccionados
    if (empty($_POST['archivos_seleccionados']) || !is_array($_POST['archivos_seleccionados'])) {
        wp_redirect(add_query_arg('error', 'no_archivos', wp_get_referer()));
        exit;
    }
    
    $archivos_rutas = array_map('sanitize_text_field', $_POST['archivos_seleccionados']);
    
    // Verificar que los archivos pertenezcan al cliente (seguridad)
    $current_user = wp_get_current_user();
    $numero_cliente = $current_user->user_login;
    $base_path = '/path/to/asmel-data/informes/' . $numero_cliente . '/';
    
    foreach ($archivos_rutas as $ruta) {
        // Verificar que la ruta comience con el directorio del cliente
        if (strpos($ruta, $base_path) !== 0) {
            wp_die('Acceso denegado a archivos fuera de tu directorio.');
        }
        
        // Verificar que el archivo exista
        if (!file_exists($ruta)) {
            wp_die('Uno o más archivos seleccionados no existen.');
        }
    }
    
    // Crear archivo ZIP
    if (!class_exists('ZipArchive')) {
        wp_die('La extensión ZipArchive no está disponible en este servidor.');
    }
    
    $zip = new ZipArchive();
    $zip_filename = 'archivos_seleccionados_' . date('YmdHis') . '.zip';
    $zip_filepath = wp_tempnam($zip_filename);
    
    if ($zip->open($zip_filepath, ZipArchive::CREATE) !== TRUE) {
        wp_die('Error al crear archivo ZIP.');
    }
    
    // Agregar archivos al ZIP
    foreach ($archivos_rutas as $ruta) {
        if (file_exists($ruta)) {
            $zip->addFile($ruta, basename($ruta));
        }
    }
    
    $zip->close();
    
    // Forzar descarga del ZIP
    if (file_exists($zip_filepath)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_filepath));
        
        readfile($zip_filepath);
        unlink($zip_filepath); // Eliminar archivo temporal
        exit;
    } else {
        wp_die('Error al generar archivo ZIP.');
    }
}

/**
 * Shortcode para mostrar número de pacientes activos del cliente logueado.
 *
 * @param array $atts Atributos del shortcode.
 * @return string Número de pacientes activos.
 */
function asmel_numero_pacientes_activos_shortcode($atts) {
    // Verificar si el usuario está logueado y es cliente
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        return '<!-- Debes estar logueado como cliente para ver esta información -->';
    }
    
    $current_user = wp_get_current_user();
    $numero_cliente = $current_user->user_login; // El user_login es el número de cliente
    
    // Usar la función existente de conexión a la base de datos externa
    $connection = asmel_connect_to_external_db();
    
    if (!$connection) {
        return '<!-- Error al conectar con la base de datos externa -->';
    }
    
    // Escapar parámetros para evitar inyección SQL
    $numero_cliente_escapado = mysqli_real_escape_string($connection, $numero_cliente);
    
    // Consulta SQL para contar pacientes activos
    $query = "SELECT COUNT(*) as total FROM paciente WHERE CLIENTE = '$numero_cliente_escapado' AND ACTIVO = 1";
    
    $result = mysqli_query($connection, $query);
    
    if (!$result) {
        error_log('Asmel External DB: Error en consulta de pacientes activos (' . mysqli_errno($connection) . ') ' . mysqli_error($connection));
        mysqli_close($connection);
        return '<!-- Error al obtener número de pacientes activos -->';
    }
    
    $row = mysqli_fetch_assoc($result);
    $total_pacientes = intval($row['total']);
    
    mysqli_free_result($result);
    mysqli_close($connection);
    
    return '<span class="numero-pacientes-activos">' . esc_html($total_pacientes) . '</span>';
}
add_shortcode('asmel_numero_pacientes_activos', 'asmel_numero_pacientes_activos_shortcode');

/**
 * Shortcode para mostrar tabla de pacientes activos del cliente logueado.
 *
 * @param array $atts Atributos del shortcode.
 * @return string Tabla HTML con lista de pacientes.
 */
function asmel_lista_pacientes_activos_shortcode($atts) {
    // Verificar si el usuario está logueado y es cliente
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        return '<!-- Debes estar logueado como cliente para ver esta información -->';
    }
    
    $current_user = wp_get_current_user();
    $numero_cliente = $current_user->user_login; // El user_login es el número de cliente
    
    // Obtener parámetros de paginación y búsqueda
    $pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $nombre_paciente = isset($_GET['nombre_paciente']) ? sanitize_text_field($_GET['nombre_paciente']) : '';
    $registros_por_pagina = 20;
    $offset = ($pagina_actual - 1) * $registros_por_pagina;
    
    // Usar la función existente de conexión a la base de datos externa
    $connection = asmel_connect_to_external_db();
    
    if (!$connection) {
        return '<div class="asmel-error">Error al conectar con la base de datos externa.</div>';
    }
    
    // Escapar parámetros para evitar inyección SQL
    $numero_cliente_escapado = mysqli_real_escape_string($connection, $numero_cliente);
    $nombre_paciente_escapado = mysqli_real_escape_string($connection, $nombre_paciente);
    
    // Construir consulta base para contar registros
    $count_query = "SELECT COUNT(*) as total FROM paciente WHERE CLIENTE = '$numero_cliente_escapado' AND ACTIVO = 1";
    if (!empty($nombre_paciente_escapado)) {
        $count_query .= " AND NOMBRE LIKE '%$nombre_paciente_escapado%'";
    }
    
    $count_result = mysqli_query($connection, $count_query);
    if (!$count_result) {
        error_log('Asmel External DB: Error en consulta de conteo de pacientes (' . mysqli_errno($connection) . ') ' . mysqli_error($connection));
        mysqli_close($connection);
        return '<div class="asmel-error">Error al obtener número de pacientes.</div>';
    }
    
    $count_row = mysqli_fetch_assoc($count_result);
    $total_registros = intval($count_row['total']);
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
    mysqli_free_result($count_result);
    
    // Construir consulta para obtener pacientes
    $query = "SELECT p.NOMBRE, p.TIPO, p.DOCUMENTO
              FROM paciente p 
              WHERE p.CLIENTE = '$numero_cliente_escapado' AND p.ACTIVO = 1";
    
    if (!empty($nombre_paciente_escapado)) {
        $query .= " AND p.NOMBRE LIKE '%$nombre_paciente_escapado%'";
    }
    
    $query .= " ORDER BY p.NOMBRE ASC LIMIT $registros_por_pagina OFFSET $offset";
    
    $result = mysqli_query($connection, $query);
    
    if (!$result) {
        error_log('Asmel External DB: Error en consulta de lista de pacientes (' . mysqli_errno($connection) . ') ' . mysqli_error($connection));
        mysqli_close($connection);
        return '<div class="asmel-error">Error al obtener lista de pacientes.</div>';
    }
    
    $pacientes = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $pacientes[] = array(
            'nombre' => $row['NOMBRE'],
            'tipo' => $row['TIPO'],
            'documento' => $row['DOCUMENTO']
        );
    }
    
    mysqli_free_result($result);
    mysqli_close($connection);
    
    ob_start();
    ?>
    <div class="asmel-consultas">
        <!-- Formulario de búsqueda -->
        <form method="get" action="<?php echo esc_url(home_url('/dashboard-clientes-pacientes/')); ?>" class="pacientes-search-form">
            <input type="hidden" name="pagina" value="1" />
            
            <div class="form-group">
                <input type="text" name="nombre_paciente" id="nombre_paciente" 
                       placeholder="Nombre del Empleado" 
                       value="<?php echo esc_attr($nombre_paciente); ?>" />
            </div>
            
            <div class="form-group">
                <input type="submit" value="BUSCAR PACIENTES" class="button button-primary">
                <a href="<?php echo home_url('/dashboard-clientes-pacientes/'); ?>" class="button button-secondary">LIMPIAR FILTROS</a>
            </div>
        </form>
        
        <!-- Tabla de pacientes -->
        <?php if (!empty($pacientes)): ?>
            <div class="pacientes-table-container">
                <table class="pacientes-table">
                    <thead>
                        <tr>
                            <th>Nombre del Empleado</th>
                            <th>Tipo de Documento</th>
                            <th>Número de Documento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pacientes as $paciente): ?>
                            <tr>
                                <td><?php echo esc_html($paciente['nombre']); ?></td>
                                <td><?php echo esc_html($paciente['tipo']); ?></td>
                                <td><?php echo esc_html($paciente['documento']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <div class="pacientes-pagination">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="<?php echo add_query_arg(array('pagina' => $pagina_actual - 1, 'nombre_paciente' => $nombre_paciente), home_url('/dashboard-clientes-pacientes/')); ?>" class="pagination-prev"><</a>
                        <?php endif; ?>
                        
                        <span class="pagination-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
                        
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="<?php echo add_query_arg(array('pagina' => $pagina_actual + 1, 'nombre_paciente' => $nombre_paciente), home_url('/dashboard-clientes-pacientes/')); ?>" class="pagination-next">></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="archivos-results">
                <p>No se encontraron pacientes activos<?php if (!empty($nombre_paciente)) echo ' con el nombre "' . esc_html($nombre_paciente) . '"'; ?>.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $output = ob_get_clean();
    
    return $output;
}
add_shortcode('asmel_lista_pacientes_activos', 'asmel_lista_pacientes_activos_shortcode');

/**
 * Shortcode para mostrar formulario de consulta de informes.
 *
 * @param array $atts Atributos del shortcode.
 * @return string Formulario HTML para consulta de informes.
 */
function asmel_consulta_informes_shortcode($atts) {
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        return '<!-- Debes estar logueado como cliente para ver esta información -->';
    }

    $current_user = wp_get_current_user();
    $numero_cliente = $current_user->user_login;

    $fecha_desde = isset($_GET['fecha_desde']) ? sanitize_text_field($_GET['fecha_desde']) : '';
    $fecha_hasta = isset($_GET['fecha_hasta']) ? sanitize_text_field($_GET['fecha_hasta']) : '';
    $nombre_paciente = isset($_GET['nombre_paciente']) ? sanitize_text_field($_GET['nombre_paciente']) : '';
    $dias_ausentismo = '';
    $dias_ausentismo_raw = isset($_GET['dias_ausentismo']) ? trim($_GET['dias_ausentismo']) : '';
    $habilitar_archivos = isset($_GET['habilitar_archivos']) && $_GET['habilitar_archivos'] === '1';
    $tipo_informe = isset($_GET['tipo_informe']) ? sanitize_text_field($_GET['tipo_informe']) : '';

    $tipo_informe_opciones = array(
        '01' => '01-Médico',
        '02' => '02-Preocupacional',
        '03' => '03-Periódico',
        '04' => '04-Egreso',
    );
    if (!array_key_exists($tipo_informe, $tipo_informe_opciones)) {
        $tipo_informe = '';
    }

    $error_fecha = '';
    $error_dias = '';

    if ($dias_ausentismo_raw !== '') {
        if (ctype_digit($dias_ausentismo_raw)) {
            $dias_ausentismo = $dias_ausentismo_raw;
        } else {
            $error_dias = '<div class="asmel-error">Ingresa un número válido para los días de ausentismo.</div>';
        }
    }

    if (!empty($fecha_desde) && !empty($fecha_hasta) && strtotime($fecha_hasta) < strtotime($fecha_desde)) {
        $error_fecha = '<div class="asmel-error">Selecciona un periodo de fechas válido.</div>';
    }

    $informes = array();
    $busqueda_realizada = false;

    if (isset($_GET['buscar_informes']) && $_GET['buscar_informes'] === 'true' && empty($error_fecha) && empty($error_dias)) {
        $busqueda_realizada = true;
        $connection = asmel_connect_to_external_db();
        if (!$connection) {
            return '<div class="asmel-error">Error al conectar con la base de datos externa.</div>';
        }

        $numero_cliente_escapado = mysqli_real_escape_string($connection, $numero_cliente);
        $fecha_desde_escapada = mysqli_real_escape_string($connection, $fecha_desde);
        $fecha_hasta_escapada = mysqli_real_escape_string($connection, $fecha_hasta);
        $nombre_paciente_escapado = mysqli_real_escape_string($connection, $nombre_paciente);
        $dias_ausentismo_escapado = $dias_ausentismo !== '' ? intval($dias_ausentismo) : null;

        $query = "SELECT FECHA, CONSULTA, PACIENTE, RESULTADO, TRABAJA, CITADO, DIAS, AMPLIACION 
                  FROM CONSULTA 
                  WHERE CLIENTE = '$numero_cliente_escapado'";

        if (!empty($fecha_desde_escapada) && !empty($fecha_hasta_escapada)) {
            $query .= " AND FECHA BETWEEN '$fecha_desde_escapada' AND '$fecha_hasta_escapada'";
        } elseif (!empty($fecha_desde_escapada)) {
            $query .= " AND FECHA >= '$fecha_desde_escapada'";
        } elseif (!empty($fecha_hasta_escapada)) {
            $query .= " AND FECHA <= '$fecha_hasta_escapada'";
        }

        if (!empty($nombre_paciente_escapado)) {
            $query .= " AND PACIENTE LIKE '%$nombre_paciente_escapado%'";
        }

        if ($dias_ausentismo_escapado !== null) {
            $query .= " AND DIAS = $dias_ausentismo_escapado";
        }

        $query .= " ORDER BY FECHA DESC";

        $result = mysqli_query($connection, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $informes[] = array(
                    'fecha' => date('d/m/Y', strtotime($row['FECHA'])),
                    'consulta' => $row['CONSULTA'],
                    'paciente' => $row['PACIENTE'],
                    'resultado' => $row['RESULTADO'],
                    'trabaja' => $row['TRABAJA'] ? date('d/m/Y', strtotime($row['TRABAJA'])) : '',
                    'citado' => $row['CITADO'] ? date('d/m/Y', strtotime($row['CITADO'])) : '',
                    'dias' => $row['DIAS'],
                    'ampliacion' => $row['AMPLIACION'],
                    'archivo' => null,
                );
            }
            mysqli_free_result($result);
        }
        mysqli_close($connection);
    }

    $hay_archivos_disponibles = false;
    if ($habilitar_archivos && !empty($informes)) {
        $archivos_reales = asmel_buscar_archivos_reales($numero_cliente, '', $tipo_informe);
        if (!empty($archivos_reales)) {
            $normalize_value = function($value) {
                $normalized = function_exists('remove_accents') ? remove_accents($value) : $value;
                $normalized = strtolower($normalized);
                $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);
                return $normalized;
            };

            $fecha_desde_iso = !empty($fecha_desde) ? $fecha_desde : null;
            $fecha_hasta_iso = !empty($fecha_hasta) ? $fecha_hasta : null;

            $archivos_por_fecha = array();
            $archivos_por_paciente = array();

            foreach ($archivos_reales as $archivo) {
                $fecha_obj = DateTime::createFromFormat('d/m/Y', $archivo['fecha']);
                $fecha_iso = $fecha_obj ? $fecha_obj->format('Y-m-d') : null;

                if ($fecha_desde_iso && $fecha_iso && $fecha_iso < $fecha_desde_iso) {
                    continue;
                }
                if ($fecha_hasta_iso && $fecha_iso && $fecha_iso > $fecha_hasta_iso) {
                    continue;
                }

                $paciente_normalizado = $normalize_value($archivo['paciente']);
                $clave_fecha = $paciente_normalizado . '|' . $archivo['fecha'];

                if (!isset($archivos_por_fecha[$clave_fecha])) {
                    $archivos_por_fecha[$clave_fecha] = array();
                }
                $archivos_por_fecha[$clave_fecha][] = $archivo;

                if (!isset($archivos_por_paciente[$paciente_normalizado])) {
                    $archivos_por_paciente[$paciente_normalizado] = array();
                }
                $archivos_por_paciente[$paciente_normalizado][] = $archivo;
            }

            foreach ($informes as &$informe) {
                $paciente_normalizado = $normalize_value($informe['paciente']);
                $clave_fecha = $paciente_normalizado . '|' . $informe['fecha'];
                $archivo_relacionado = null;

                if (!empty($archivos_por_fecha[$clave_fecha])) {
                    $archivo_relacionado = $archivos_por_fecha[$clave_fecha][0];
                } elseif (!empty($archivos_por_paciente[$paciente_normalizado])) {
                    $archivo_relacionado = $archivos_por_paciente[$paciente_normalizado][0];
                }

                if ($archivo_relacionado) {
                    $informe['archivo'] = $archivo_relacionado;
                    $hay_archivos_disponibles = true;
                }
            }
            unset($informe);
        }
    }

    $total_informes = count($informes);

    ob_start();
    ?>
    <div class="asmel-consultas">
        <?php echo $error_fecha . $error_dias; ?>
        <form method="get" action="<?php echo esc_url(home_url('/dashboard-clientes-informes/')); ?>" class="informes-search-form" id="informes-search-form">
            <input type="hidden" name="buscar_informes" value="true" />
            <div class="form-row">
                <div class="form-group">
                    <input type="date" name="fecha_desde" id="fecha_desde" value="<?php echo esc_attr($fecha_desde); ?>" placeholder="Fecha Desde" />
                </div>
                <div class="form-group">
                    <input type="date" name="fecha_hasta" id="fecha_hasta" value="<?php echo esc_attr($fecha_hasta); ?>" placeholder="Fecha Hasta" />
                </div>
            </div>
            <div class="form-group">
                <input type="text" name="nombre_paciente" id="nombre_paciente" 
                       placeholder="Nombre del Empleado (opcional)" 
                       value="<?php echo esc_attr($nombre_paciente); ?>" />
            </div>
            <div class="form-group">
                <input type="number" name="dias_ausentismo" id="dias_ausentismo" 
                       placeholder="Días de Ausentismo (opcional)" min="0" 
                       value="<?php echo esc_attr($dias_ausentismo); ?>" />
            </div>
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="habilitar_archivos" id="habilitar_archivos" value="1" <?php checked($habilitar_archivos); ?> />
                    Habilitar Archivos
                </label>
            </div>
            <div class="form-group" id="tipo-informe-group" style="<?php echo $habilitar_archivos ? '' : 'display:none;'; ?>">
                <select id="tipo_informe" name="tipo_informe">
                    <option value="">Tipo de Informe</option>
                    <?php foreach ($tipo_informe_opciones as $codigo => $label) : ?>
                        <option value="<?php echo esc_attr($codigo); ?>" <?php selected($tipo_informe, $codigo); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" value="BUSCAR INFORMES" class="button button-primary">
                <a href="<?php echo home_url('/dashboard-clientes-informes/'); ?>" class="button button-secondary">LIMPIAR FILTROS</a>
            </div>
        </form>

        <?php if ($busqueda_realizada): ?>
            <div class="archivos-results">
                <h3>Informes Encontrados (<?php echo esc_html($total_informes); ?>)</h3>
                <?php if (!empty($informes)): ?>
                    <form id="informes-result-form" method="post">
                        <input type="hidden" name="fecha_desde" value="<?php echo esc_attr($fecha_desde); ?>">
                        <input type="hidden" name="fecha_hasta" value="<?php echo esc_attr($fecha_hasta); ?>">
                        <input type="hidden" name="nombre_paciente" value="<?php echo esc_attr($nombre_paciente); ?>">
                        <input type="hidden" name="dias_ausentismo" value="<?php echo esc_attr($dias_ausentismo); ?>">
                        <input type="hidden" name="habilitar_archivos" value="<?php echo $habilitar_archivos ? '1' : '0'; ?>">
                        <input type="hidden" name="tipo_informe" value="<?php echo esc_attr($tipo_informe); ?>">
                        <?php wp_nonce_field('asmel_exportar_informes_excel_nonce', 'exportar_informes_excel_nonce'); ?>
                        <?php if ($habilitar_archivos): ?>
                            <?php wp_nonce_field('asmel_descargar_archivos_zip_nonce', 'descargar_archivos_zip_nonce'); ?>
                        <?php endif; ?>

                        <div class="export-actions">
                            <button type="submit" class="button button-primary" formaction="<?php echo esc_url(admin_url('admin-post.php?action=asmel_exportar_informes_excel')); ?>">EXPORTAR A EXCEL</button>
                            <?php if ($habilitar_archivos && $hay_archivos_disponibles): ?>
                                <button type="submit" class="button button-secondary" formaction="<?php echo esc_url(admin_url('admin-post.php?action=asmel_descargar_archivos_zip')); ?>">DESCARGAR ZIP</button>
                            <?php endif; ?>
                        </div>

                        <table class="informes-table">
                            <thead>
                                <tr>
                                    <?php if ($habilitar_archivos): ?>
                                        <th><input type="checkbox" id="select-all-informes" /></th>
                                    <?php endif; ?>
                                    <th>Fecha</th>
                                    <th>Consulta</th>
                                    <th>Paciente</th>
                                    <th>Resultado</th>
                                    <th>Trabaja</th>
                                    <th>Citado</th>
                                    <th>Días</th>
                                    <th>Ampliación</th>
                                    <?php if ($habilitar_archivos): ?>
                                        <th>Descargar</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($informes as $informe): ?>
                                    <tr>
                                        <?php if ($habilitar_archivos): ?>
                                            <td>
                                                <?php if (!empty($informe['archivo'])): ?>
                                                    <input type="checkbox" name="archivos_seleccionados[]" value="<?php echo esc_attr($informe['archivo']['ruta_completa']); ?>" />
                                                <?php else: ?>
                                                    <span class="no-file">&mdash;</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td><?php echo esc_html($informe['fecha']); ?></td>
                                        <td><?php echo esc_html($informe['consulta']); ?></td>
                                        <td><?php echo esc_html($informe['paciente']); ?></td>
                                        <td><?php echo esc_html($informe['resultado']); ?></td>
                                        <td><?php echo esc_html($informe['trabaja']); ?></td>
                                        <td><?php echo esc_html($informe['citado']); ?></td>
                                        <td><?php echo esc_html($informe['dias']); ?></td>
                                        <td><?php echo esc_html($informe['ampliacion']); ?></td>
                                        <?php if ($habilitar_archivos): ?>
                                            <td>
                                                <?php if (!empty($informe['archivo'])): ?>
                                                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=asmel_descargar_pdf_individual&archivo=' . urlencode($informe['archivo']['ruta_completa']))); ?>" class="button button-small">DESCARGAR</a>
                                                <?php else: ?>
                                                    <span class="no-file">Sin archivo</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="export-actions">
                            <button type="submit" class="button button-primary" formaction="<?php echo esc_url(admin_url('admin-post.php?action=asmel_exportar_informes_excel')); ?>">EXPORTAR A EXCEL</button>
                            <?php if ($habilitar_archivos && $hay_archivos_disponibles): ?>
                                <button type="submit" class="button button-secondary" formaction="<?php echo esc_url(admin_url('admin-post.php?action=asmel_descargar_archivos_zip')); ?>">DESCARGAR ZIP</button>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="archivos-results">
                        <p>No se encontraron informes con los criterios de búsqueda especificados.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="archivos-results">
                <p>Utiliza el formulario de búsqueda para encontrar informes.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggleArchivos = document.getElementById('habilitar_archivos');
        var tipoInformeGroup = document.getElementById('tipo-informe-group');
        if (toggleArchivos && tipoInformeGroup) {
            var toggleVisibility = function() {
                tipoInformeGroup.style.display = toggleArchivos.checked ? '' : 'none';
            };
            toggleVisibility();
            toggleArchivos.addEventListener('change', toggleVisibility);
        }

        var selectAllInformes = document.getElementById('select-all-informes');
        if (selectAllInformes) {
            selectAllInformes.addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('#informes-result-form input[name="archivos_seleccionados[]"]');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = selectAllInformes.checked;
                }
            });
        }
    });
    </script>
    <?php
    $output = ob_get_clean();
    return $output;
}
add_shortcode('asmel_consulta_informes', 'asmel_consulta_informes_shortcode');

// Procesar exportación de informes a Excel
add_action('admin_post_asmel_exportar_informes_excel', 'asmel_procesar_exportar_informes_excel');
add_action('admin_post_nopriv_asmel_exportar_informes_excel', 'asmel_procesar_exportar_informes_excel');
/**
 * Ejecuta procesar exportar informes excel.
 * @return mixed Resultado de la funcion.
 */
function asmel_procesar_exportar_informes_excel() {
    // Verificar si el usuario está logueado y es cliente
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        wp_die('Debes estar logueado como cliente para acceder a esta función.');
    }
    
    // Verificar nonce
    if (!isset($_POST['exportar_informes_excel_nonce']) || !wp_verify_nonce($_POST['exportar_informes_excel_nonce'], 'asmel_exportar_informes_excel_nonce')) {
        wp_die('Error de seguridad.');
    }
    
    // Obtener filtros de búsqueda
    $fecha_desde = isset($_POST['fecha_desde']) ? sanitize_text_field($_POST['fecha_desde']) : '';
    $fecha_hasta = isset($_POST['fecha_hasta']) ? sanitize_text_field($_POST['fecha_hasta']) : '';
    $nombre_paciente = isset($_POST['nombre_paciente']) ? sanitize_text_field($_POST['nombre_paciente']) : '';
    $dias_ausentismo = '';
    if (isset($_POST['dias_ausentismo'])) {
        $dias_ausentismo_raw = trim($_POST['dias_ausentismo']);
        if ($dias_ausentismo_raw !== '' && ctype_digit($dias_ausentismo_raw)) {
            $dias_ausentismo = $dias_ausentismo_raw;
        }
    }
    
    $current_user = wp_get_current_user();
    $numero_cliente = $current_user->user_login;
    
    // Conexión a la base externa
    $connection = asmel_connect_to_external_db();
    if (!$connection) {
        wp_die('Error al conectar con la base de datos externa.');
    }
    
    // Escapar parámetros
    $numero_cliente_escapado = mysqli_real_escape_string($connection, $numero_cliente);
    $fecha_desde_escapada = mysqli_real_escape_string($connection, $fecha_desde);
    $fecha_hasta_escapada = mysqli_real_escape_string($connection, $fecha_hasta);
    $nombre_paciente_escapado = mysqli_real_escape_string($connection, $nombre_paciente);
    $dias_ausentismo_escapado = $dias_ausentismo !== '' ? intval($dias_ausentismo) : null;
    
    // Construir consulta base
    $query = "SELECT FECHA, CONSULTA, PACIENTE, RESULTADO, TRABAJA, CITADO, DIAS, AMPLIACION 
              FROM CONSULTA 
              WHERE CLIENTE = '$numero_cliente_escapado'";
    
    // Agregar condiciones de fecha
    if (!empty($fecha_desde_escapada) && !empty($fecha_hasta_escapada)) {
        $query .= " AND FECHA BETWEEN '$fecha_desde_escapada' AND '$fecha_hasta_escapada'";
    } elseif (!empty($fecha_desde_escapada)) {
        $query .= " AND FECHA >= '$fecha_desde_escapada'";
    } elseif (!empty($fecha_hasta_escapada)) {
        $query .= " AND FECHA <= '$fecha_hasta_escapada'";
    }
    
    // Agregar condición de paciente (si se especifica)
    if (!empty($nombre_paciente_escapado)) {
        $query .= " AND PACIENTE LIKE '%$nombre_paciente_escapado%'";
    }
    if ($dias_ausentismo_escapado !== null) {
        $query .= " AND DIAS = $dias_ausentismo_escapado";
    }
    
    $query .= " ORDER BY FECHA DESC";
    
    $result = mysqli_query($connection, $query);
    if (!$result) {
        mysqli_close($connection);
        wp_die('Error al obtener los informes para exportar.');
    }
    
    $datos_exportar = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $datos_exportar[] = array(
            'Fecha' => date('d/m/Y', strtotime($row['FECHA'])),
            'Consulta' => $row['CONSULTA'],
            'Paciente' => $row['PACIENTE'],
            'Resultado' => $row['RESULTADO'],
            'Trabaja' => $row['TRABAJA'] ? date('d/m/Y', strtotime($row['TRABAJA'])) : '',
            'Citado' => $row['CITADO'] ? date('d/m/Y', strtotime($row['CITADO'])) : '',
            'Días' => $row['DIAS'],
            'Ampliación' => $row['AMPLIACION']
        );
    }
    mysqli_free_result($result);
    mysqli_close($connection);
    
    if (empty($datos_exportar)) {
        wp_die('No hay datos para exportar.');
    }

    $columnas = array_keys($datos_exportar[0]);
    $filename = 'informes_' . date('YmdHis') . '.xlsx';
    asmel_generar_informes_xlsx($filename, $columnas, $datos_exportar);
    exit;
}

/**
 * Genera y entrega un archivo XLSX con formato para los informes.
 */
function asmel_generar_informes_xlsx($filename, $columnas, $datos) {
    if (!class_exists('ZipArchive')) {
        wp_die('La extensión ZipArchive no está disponible en el servidor.');
    }

    $temp_file = wp_tempnam($filename);
    if (!$temp_file) {
        wp_die('No se pudo generar el archivo temporal para la exportación.');
    }

    $zip = new ZipArchive();
    if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        wp_die('No se pudo crear el archivo XLSX.');
    }

    $column_count = count($columnas);
    $last_column = asmel_excel_column_letter($column_count - 1);
    $total_rows = count($datos) + 3;
    $dimension = 'A1:' . $last_column . $total_rows;

    $cols_xml = '';
    for ($i = 1; $i <= $column_count; $i++) {
        $cols_xml .= '<col min="' . $i . '" max="' . $i . '" width="20" customWidth="1"/>';
    }

    $span_attr = ' spans="1:' . $column_count . '"';
    $rows_xml = '';

    // Título
    $title = asmel_excel_format_value('Informes Medicos Asmel');
    $rows_xml .= '<row r="1"' . $span_attr . '>';
    $rows_xml .= asmel_excel_build_inline_cell(0, 1, $title, 2);
    $rows_xml .= '</row>';

    // Fila intermedia vacía
    $rows_xml .= '<row r="2"' . $span_attr . '>';
    $rows_xml .= asmel_excel_build_inline_cell(0, 2, '', 1);
    $rows_xml .= '</row>';

    // Encabezados
    $rows_xml .= '<row r="3"' . $span_attr . '>';
    foreach ($columnas as $index => $columna) {
        $rows_xml .= asmel_excel_build_inline_cell($index, 3, asmel_excel_format_value($columna), 1);
    }
    $rows_xml .= '</row>';

    // Datos
    $row_number = 4;
    foreach ($datos as $fila) {
        $rows_xml .= '<row r="' . $row_number . '"' . $span_attr . '>';
        foreach ($columnas as $index => $columna) {
            $valor = isset($fila[$columna]) ? $fila[$columna] : '';
            $rows_xml .= asmel_excel_build_inline_cell($index, $row_number, asmel_excel_format_value($valor), 0);
        }
        $rows_xml .= '</row>';
        $row_number++;
    }

    $merge_ranges = array();
    if ($column_count > 1) {
        $merge_ranges[] = 'A1:' . $last_column . '1';
        $merge_ranges[] = 'A2:' . $last_column . '2';
    }

    $merge_cells_xml = '';
    if (!empty($merge_ranges)) {
        $merge_cells_xml = '<mergeCells count="' . count($merge_ranges) . '">';
        foreach ($merge_ranges as $merge_ref) {
            $merge_cells_xml .= '<mergeCell ref="' . $merge_ref . '"/>';
        }
        $merge_cells_xml .= '</mergeCells>';
    }

    $sheet_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<dimension ref="' . $dimension . '"/>'
        . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="15"/>'
        . '<cols>' . $cols_xml . '</cols>'
        . '<sheetData>' . $rows_xml . '</sheetData>'
        . $merge_cells_xml
        . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
        . '</worksheet>';

    $workbook_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="INFORMES" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>';

    $workbook_rels_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';

    $styles_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2">'
        . '<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
        . '</fonts>'
        . '<fills count="3">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF222F66"/><bgColor rgb="FF222F66"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="1">'
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="3">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';

    $content_types_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
        . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
        . '</Types>';

    $root_rels_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
        . '</Relationships>';

    $timestamp = gmdate('Y-m-d\TH:i:s\Z');
    $core_props_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . '<dc:creator>Clinica Asmel</dc:creator>'
        . '<cp:lastModifiedBy>Clinica Asmel</cp:lastModifiedBy>'
        . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:created>'
        . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:modified>'
        . '</cp:coreProperties>';

    $app_props_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
        . '<Application>Microsoft Excel</Application>'
        . '</Properties>';

    $zip->addFromString('[Content_Types].xml', $content_types_xml);
    $zip->addFromString('_rels/.rels', $root_rels_xml);
    $zip->addFromString('docProps/core.xml', $core_props_xml);
    $zip->addFromString('docProps/app.xml', $app_props_xml);
    $zip->addFromString('xl/workbook.xml', $workbook_xml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbook_rels_xml);
    $zip->addFromString('xl/styles.xml', $styles_xml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet_xml);

    $zip->close();

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    $handle = fopen($temp_file, 'rb');
    if ($handle) {
        while (!feof($handle)) {
            echo fread($handle, 8192);
        }
        fclose($handle);
    }

    unlink($temp_file);
    exit;
}

/**
 * Convierte un índice de columna (base 0) a su equivalente en Excel.
 */
function asmel_excel_column_letter($index) {
    $index = (int) $index;
    $letter = '';
    while ($index >= 0) {
        $letter = chr(($index % 26) + 65) . $letter;
        $index = (int) floor($index / 26) - 1;
    }
    return $letter;
}

/**
 * Formatea valores para el XLSX, forzando mayúsculas en textos.
 */
function asmel_excel_format_value($value) {
    if (is_null($value)) {
        return '';
    }
    if (is_bool($value)) {
        return $value ? 'TRUE' : 'FALSE';
    }
    if (is_numeric($value)) {
        return (string) $value;
    }

    $string = (string) $value;
    if (function_exists('mb_strtoupper')) {
        $string = mb_strtoupper($string, 'UTF-8');
    } else {
        $string = strtoupper($string);
    }
    return $string;
}

/**
 * Genera una celda inline string para la hoja XLSX.
 */
function asmel_excel_build_inline_cell($column_index, $row_number, $value, $style_index = 0) {
    $cell_ref = asmel_excel_column_letter($column_index) . $row_number;
    $style_attr = $style_index > 0 ? ' s="' . (int) $style_index . '"' : '';
    $escaped_value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return '<c r="' . $cell_ref . '" t="inlineStr"' . $style_attr . '><is><t>' . $escaped_value . '</t></is></c>';
}

/**
 * Endpoint seguro para ver/descargar un PDF individual.
 * Usa admin-post.php?action=asmel_descargar_pdf_individual&archivo=...&inline=1
 */
add_action('admin_post_asmel_descargar_pdf_individual', 'asmel_descargar_pdf_individual');
add_action('admin_post_nopriv_asmel_descargar_pdf_individual', 'asmel_descargar_pdf_individual');
function asmel_descargar_pdf_individual() {
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        wp_die('Debes estar logueado como cliente para acceder a esta función.');
    }

    if (empty($_GET['archivo'])) {
        wp_die('Archivo no especificado.');
    }

    $archivo_raw = urldecode($_GET['archivo']);
    $archivo = sanitize_text_field($archivo_raw);
    $archivo_real = realpath($archivo);
    if ($archivo_real === false) {
        wp_die('Archivo no válido.');
    }

    $current_user = wp_get_current_user();
    $numero_cliente = $current_user->user_login;

    // Directorios permitidos (carpetas del cliente: informes y comprobantes, carpeta con 6 dígitos)
    $allowed_dirs = array(
        realpath('/path/to/asmel-data/informes/' . $numero_cliente . '/'),
        realpath('/path/to/asmel-data/comprobantes/' . $numero_cliente . '/')
    );

    $allowed = false;
    foreach ($allowed_dirs as $dir) {
        if ($dir && strpos($archivo_real, $dir) === 0) {
            $allowed = true;
            break;
        }
    }

    if (!$allowed || !is_file($archivo_real)) {
        wp_die('Acceso denegado o archivo no existe.');
    }

    // Forzar salida PDF; inline=1 muestra en navegador
    header('Content-Type: application/pdf');
    $inline = isset($_GET['inline']) && ($_GET['inline'] === '1' || $_GET['inline'] === 'true');
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . basename($archivo_real) . '"');
    header('Content-Length: ' . filesize($archivo_real));
    readfile($archivo_real);
    exit;
}

/**
 * Shortcode para el formulario de consulta de comprobantes
 * (lista, enlace seguro a admin-post para ver/descargar y bulk ZIP)
 */
function asmel_consulta_comprobantes_shortcode($atts) {
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        return '<p>Debes estar logueado como cliente para acceder a esta sección.</p>';
    }

    $current_user = wp_get_current_user();
    $numero_cliente = $current_user->user_login; // número tal cual (6 dígitos)
    $tipo_comprobante = isset($_GET['tipo_comprobante']) ? sanitize_text_field($_GET['tipo_comprobante']) : '';
    $busqueda_comprobantes = isset($_GET['buscar']) && $_GET['buscar'] === 'true';

    $comprobantes = asmel_obtener_comprobantes_cliente($numero_cliente, $tipo_comprobante);

    $total_comprobantes = count($comprobantes);
    $registros_por_pagina = 20;
    $pagina_actual = isset($_GET['pagina_comprobantes']) ? max(1, intval($_GET['pagina_comprobantes'])) : 1;
    $total_paginas = $total_comprobantes > 0 ? (int)ceil($total_comprobantes / $registros_por_pagina) : 0;
    if ($total_paginas > 0 && $pagina_actual > $total_paginas) {
        $pagina_actual = $total_paginas;
    } elseif ($total_paginas === 0) {
        $pagina_actual = 1;
    }
    $offset = ($pagina_actual - 1) * $registros_por_pagina;
    $comprobantes_paginados = array_slice($comprobantes, $offset, $registros_por_pagina);

    $pagination_base_comprobantes = home_url('/dashboard-clientes-comprobantes/');
    $pagination_query_comprobantes = array();
    if ($busqueda_comprobantes) {
        $pagination_query_comprobantes['buscar'] = 'true';
    }
    if ($tipo_comprobante !== '') {
        $pagination_query_comprobantes['tipo_comprobante'] = $tipo_comprobante;
    }

    ob_start();
    ?>
    <div class="asmel-consultas">
        <form method="get" action="<?php echo esc_url(home_url('/dashboard-clientes-comprobantes/')); ?>" class="consulta-form">
            <input type="hidden" name="buscar" value="true" />
            <input type="hidden" name="pagina_comprobantes" value="1" />
            <div class="form-group">
                <select id="tipo_comprobante" name="tipo_comprobante">
                    <option value="">Tipo de Comprobante</option>
                    <option value="FE" <?php selected($tipo_comprobante, 'FE'); ?>>Factura</option>
                    <option value="NC" <?php selected($tipo_comprobante, 'NC'); ?>>Nota de crédito</option>
                    <option value="ND" <?php selected($tipo_comprobante, 'ND'); ?>>Nota de débito</option>
                    <option value="RC" <?php selected($tipo_comprobante, 'RC'); ?>>Recibo de papel</option>
                    <option value="RL" <?php selected($tipo_comprobante, 'RL'); ?>>Recibo electrónico</option>
                    <option value="83" <?php selected($tipo_comprobante, '83'); ?>>Factura de crédito</option>
                    <option value="91" <?php selected($tipo_comprobante, '91'); ?>>Nota de crédito para la FCE</option>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" value="BUSCAR ARCHIVOS" class="button button-primary">
                <a href="<?php echo home_url('/dashboard-clientes-comprobantes/'); ?>" class="button button-secondary">LIMPIAR FILTROS</a>
            </div>
        </form>

        <?php if ($total_comprobantes > 0): ?>
            <div class="archivos-results">
                <h3>Archivos Encontrados (<?php echo esc_html($total_comprobantes); ?>)</h3>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="archivos-bulk-form">
                    <input type="hidden" name="action" value="asmel_descargar_comprobantes_zip">
                    <?php wp_nonce_field('asmel_descargar_comprobantes_zip_nonce', 'descargar_comprobantes_zip_nonce'); ?>

                    <table class="archivos-table informes-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all" /></th>
                                <th>Fecha</th>
                                <th>Tipo de Comprobante</th>
                                <th>Número de Comprobante</th>
                                <th>Tamaño</th>
                                <th>Descargar</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($comprobantes_paginados as $comprobante): ?>
                            <tr>
                                <td><input type="checkbox" name="comprobantes_seleccionados[]" value="<?php echo esc_attr($comprobante['ruta_completa']); ?>" /></td>
                                <td><?php echo esc_html($comprobante['fecha']); ?></td>
                                <td><?php echo esc_html($comprobante['tipo']); ?></td>
                                <td><?php echo esc_html($comprobante['numero']); ?></td>
                                <td><?php echo esc_html($comprobante['tamano']); ?></td>
                                <td><a href="<?php echo esc_url($comprobante['url']); ?>" class="button button-small">DESCARGAR</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($total_paginas > 1): ?>
                        <div class="comprobantes-pagination">
                            <?php if ($pagina_actual > 1): ?>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($pagination_query_comprobantes, array('pagina_comprobantes' => $pagina_actual - 1)), $pagination_base_comprobantes)); ?>" class="pagination-prev"><</a>
                            <?php endif; ?>

                            <span class="pagination-info">Página <?php echo esc_html($pagina_actual); ?> de <?php echo esc_html($total_paginas); ?></span>

                            <?php if ($pagina_actual < $total_paginas): ?>
                                <a href="<?php echo esc_url(add_query_arg(array_merge($pagination_query_comprobantes, array('pagina_comprobantes' => $pagina_actual + 1)), $pagination_base_comprobantes)); ?>" class="pagination-next">></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="bulk-actions">
                        <input type="submit" value="DESCARGAR SELECCIONADOS EN ZIP" class="button button-primary">
                    </div>
                </form>
            </div>
        <?php elseif ($busqueda_comprobantes): ?>
            <div class="archivos-results">
                <p>No se encontraron comprobantes con los criterios de búsqueda especificados.</p>
            </div>
        <?php else: ?>
            <div class="archivos-results">
                <p>Utiliza el formulario de búsqueda para encontrar comprobantes.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Seleccionar todos los checkboxes
    document.addEventListener('DOMContentLoaded', function() {
        var selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                var checkboxes = document.querySelectorAll('input[name="comprobantes_seleccionados[]"]');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = this.checked;
                }
            });
        }
    });
    </script>
    <?php
    $output = ob_get_clean();
    return $output;
}
add_shortcode('asmel_consulta_comprobantes', 'asmel_consulta_comprobantes_shortcode');

/**
 * Obtener comprobantes del cliente.
 * La carpeta del cliente es con 6 dígitos. El nombre de archivo contiene el cliente con 7 dígitos.
 */
function asmel_obtener_comprobantes_cliente($numero_cliente, $tipo_comprobante = '') {
    $base_path = '/path/to/asmel-data/comprobantes/';
    $client_dir = $base_path . $numero_cliente . '/'; // carpeta con 6 dígitos

    if (!is_dir($client_dir)) {
        return array();
    }

    $pdf_files = glob($client_dir . '*.pdf');
    if ($pdf_files === false || empty($pdf_files)) {
        return array();
    }

    usort($pdf_files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $comprobantes = array();
    foreach ($pdf_files as $file_path) {
        $file_info = pathinfo($file_path);
        $filename = $file_info['filename'];

        // Formato esperado: CC0007904-0000300006854-NC
        $parts = explode('-', $filename);
        if (count($parts) === 3) {
            $cliente_part = $parts[0];
            $numero_part = $parts[1];
            $tipo_part = $parts[2];

            $cliente_numero = substr($cliente_part, 2); // quitar 'CC'

            // Compara con 7 dígitos en el nombre del archivo
            if ($cliente_numero !== str_pad($numero_cliente, 7, '0', STR_PAD_LEFT)) {
                continue;
            }

            if (!empty($tipo_comprobante) && $tipo_part !== $tipo_comprobante) {
                continue;
            }

            $tipos = array(
                'FE' => 'Factura',
                'NC' => 'Nota de crédito',
                'ND' => 'Nota de débito',
                'RC' => 'Recibo de papel',
                'RL' => 'Recibo electrónico',
                '83' => 'Factura de crédito',
                '91' => 'Nota de crédito para la FCE'
            );
            $tipo_nombre = isset($tipos[$tipo_part]) ? $tipos[$tipo_part] : 'Desconocido';

            $fecha_timestamp = filemtime($file_path);
            $fecha_formateada = date('d/m/Y', $fecha_timestamp);
            $tamano_bytes = filesize($file_path);
            $tamano_kb = round($tamano_bytes / 1024, 2) . ' KB';

            // Enlace seguro al endpoint admin-post para visualizar/descargar
            $file_url = admin_url('admin-post.php?action=asmel_descargar_pdf_individual&archivo=' . urlencode($file_path));

            $comprobantes[] = array(
                'fecha' => $fecha_formateada,
                'tipo' => $tipo_nombre,
                'numero' => $numero_part,
                'tamano' => $tamano_kb,
                'url' => $file_url,
                'ruta_completa' => $file_path
            );
        }
    }

    return $comprobantes;
}

/**
 * Procesar descarga de comprobantes seleccionados en ZIP
 */
add_action('admin_post_asmel_descargar_comprobantes_zip', 'asmel_procesar_descargar_comprobantes_zip');
add_action('admin_post_nopriv_asmel_descargar_comprobantes_zip', 'asmel_procesar_descargar_comprobantes_zip');
function asmel_procesar_descargar_comprobantes_zip() {
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        wp_die('Debes estar logueado como cliente para acceder a esta función.');
    }

    if (!isset($_POST['descargar_comprobantes_zip_nonce']) || !wp_verify_nonce($_POST['descargar_comprobantes_zip_nonce'], 'asmel_descargar_comprobantes_zip_nonce')) {
        wp_die('Error de seguridad.');
    }

    if (empty($_POST['comprobantes_seleccionados']) || !is_array($_POST['comprobantes_seleccionados'])) {
        wp_redirect(add_query_arg('error', 'no_comprobantes', wp_get_referer()));
        exit;
    }

    $comprobantes_rutas = array_map('sanitize_text_field', $_POST['comprobantes_seleccionados']);

    $current_user = wp_get_current_user();
    $numero_cliente = $current_user->user_login;
    $base_path = '/path/to/asmel-data/comprobantes/' . $numero_cliente . '/'; // carpeta con 6 dígitos
    $base_path_real = realpath($base_path);
    if ($base_path_real === false) {
        wp_die('Directorio de comprobantes no disponible.');
    }

    foreach ($comprobantes_rutas as $ruta) {
        $ruta_real = realpath($ruta);
        if ($ruta_real === false || strpos($ruta_real, $base_path_real) !== 0) {
            wp_die('Acceso denegado a comprobantes fuera de tu directorio.');
        }
        if (!file_exists($ruta_real)) {
            wp_die('Uno o más comprobantes seleccionados no existen.');
        }
    }

    if (!class_exists('ZipArchive')) {
        wp_die('La extensión ZipArchive no está disponible en este servidor.');
    }

    $zip = new ZipArchive();
    $zip_filename = 'comprobantes_seleccionados_' . date('YmdHis') . '.zip';
    $zip_filepath = wp_tempnam($zip_filename);

    if ($zip->open($zip_filepath, ZipArchive::CREATE) !== TRUE) {
        wp_die('Error al crear archivo ZIP.');
    }

    foreach ($comprobantes_rutas as $ruta) {
        $ruta_real = realpath($ruta);
        if ($ruta_real && file_exists($ruta_real)) {
            $zip->addFile($ruta_real, basename($ruta_real));
        }
    }

    $zip->close();

    if (file_exists($zip_filepath)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_filepath));

        readfile($zip_filepath);
        unlink($zip_filepath);
        exit;
    } else {
        wp_die('Error al generar archivo ZIP.');
    }
}

// Shortcode para el perfil de empresa
/**
 * Ejecuta perfil empresa shortcode.
 *
 * @param mixed $atts Parametro de entrada.
 * @return mixed Resultado de la funcion.
 */
function asmel_perfil_empresa_shortcode($atts) {
    // Verificar si el usuario está logueado y es cliente
    if (!is_user_logged_in() || !current_user_can('cliente')) {
        return '<p>Debes estar logueado como cliente para acceder a esta sección.</p>';
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Obtener datos del perfil
    $empresa = get_user_meta($user_id, 'empresa', true);
    $cuit = get_user_meta($user_id, 'cuit', true);
    $numero_cliente = $current_user->user_login;
    $email = $current_user->user_email;
    
    // Procesar actualización del perfil
    if (isset($_POST['actualizar_perfil']) && wp_verify_nonce($_POST['perfil_empresa_nonce'], 'actualizar_perfil_empresa')) {
        $nueva_empresa = sanitize_text_field($_POST['empresa']);
    $nuevo_cuit = isset($_POST['cuit']) ? preg_replace('/\D/', '', sanitize_text_field($_POST['cuit'])) : $cuit;
        $nuevo_email = sanitize_email($_POST['email']);

        // Normalizar valores en variables locales para mantener lo ingresado en caso de error
        $empresa = $nueva_empresa;
        $cuit = $nuevo_cuit;
        $email = $nuevo_email;
        
        // Validaciones
        if (!is_email($nuevo_email)) {
            $mensaje_error = 'El email no es válido.';
        } elseif (!preg_match('/^\d{11}$/', $nuevo_cuit)) {
            $mensaje_error = 'El CUIT debe tener 11 dígitos numéricos.';
        } else {
            // Actualizar datos
            update_user_meta($user_id, 'empresa', $nueva_empresa);
            update_user_meta($user_id, 'cuit', $nuevo_cuit);
            wp_update_user(array('ID' => $user_id, 'user_email' => $nuevo_email));
            
            // Redirigir con PRG para evitar reenvío de formulario y 404 por slugs distintos
            $redirect_url = add_query_arg('perfil_actualizado', '1', wp_unslash($_SERVER['REQUEST_URI']));
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    // Mostrar mensaje de éxito si venimos de una redirección
    if (isset($_GET['perfil_actualizado']) && $_GET['perfil_actualizado'] === '1') {
        $mensaje_exito = 'Perfil actualizado correctamente.';
    }
    
    ob_start();
    ?>
    <div class="asmel-consultas">
        
        <?php if (isset($mensaje_exito)): ?>
            <div class="asmel-success"><?php echo esc_html($mensaje_exito); ?></div>
        <?php endif; ?>
        
        <?php if (isset($mensaje_error)): ?>
            <div class="asmel-error"><?php echo esc_html($mensaje_error); ?></div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>" class="perfil-empresa-form">
            <?php wp_nonce_field('actualizar_perfil_empresa', 'perfil_empresa_nonce'); ?>
            
            <div class="form-group">
                <label for="empresa">Nombre de la Empresa:</label>
                <input type="text" id="empresa" name="empresa" value="<?php echo esc_attr($empresa); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="cuit">CUIT:</label>
                <input type="text" id="cuit" name="cuit" value="<?php echo esc_attr($cuit); ?>" inputmode="numeric" pattern="\d{11}" maxlength="11" minlength="11" title="Ingresá 11 dígitos">
                <small>Ingresá el CUIT de tu empresa (11 dígitos, solo números).</small>
            </div>
            
            <div class="form-group">
                <label for="numero_cliente">Número de Cliente:</label>
                <input type="text" id="numero_cliente" name="numero_cliente" value="<?php echo esc_attr($numero_cliente); ?>" readonly>
                <small>Este campo no se puede editar.</small>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" required>
            </div>
            
            <div class="form-group">
                <input type="submit" name="actualizar_perfil" value="ACTUALIZAR PERFIL" class="button button-primary">
            </div>
        </form>
    </div>
    <?php
    $output = ob_get_clean();
    
    return $output;
}
add_shortcode('asmel_perfil_empresa', 'asmel_perfil_empresa_shortcode');


