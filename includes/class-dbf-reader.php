<?php
/**
 * Clase simple para leer archivos DBF (dBase III/IV) sin extensión dbase.
 */
class Asmel_DBF_Reader {
    private $fp;
    private $header;
    private $fields = array();
    private $record_size;
    
    /**
     * Inicializa la instancia.
     *
     * @param mixed $filepath Parametro de entrada.
     * @return mixed Resultado de la funcion.
     */
    public function __construct($filepath) {
        if (!file_exists($filepath)) {
            throw new Exception("Archivo no encontrado: $filepath");
        }
        
        $this->fp = fopen($filepath, 'rb');
        if (!$this->fp) {
            throw new Exception("No se puede abrir el archivo: $filepath");
        }
        
        $this->read_header();
    }
    
    /**
     * Lee y procesa la cabecera del archivo.
     * @return mixed Resultado de la funcion.
     */
    private function read_header() {
        // Leer bloque de cabecera (32 bytes)
        $buf = fread($this->fp, 32);
        
        // Unpack header info
        // V: unsigned long (32 bit, little endian) - Record Count
        // v: unsigned short (16 bit, little endian) - Header Length
        // v: unsigned short (16 bit, little endian) - Record Length
        $data = unpack("Vrecord_count/vheader_len/vrecord_len", substr($buf, 4, 8));
        
        $this->header = $data;
        $this->record_size = $data['record_len'];
        
        // Moverse al inicio de descriptores de campo
        fseek($this->fp, 32);
        
        // Leer campos
        while (!feof($this->fp)) {
            $buf = fread($this->fp, 32);
            if (strlen($buf) < 32) break;
            
            // Terminador de cabecera (CR)
            if (ord($buf[0]) === 0x0D) break;
            
            $field = unpack("A11name/A1type/Voffset/Csubver/Ctype2", substr($buf, 0, 18));
            // Length y decimals están en offsets 16 y 17
            $field['length'] = ord($buf[16]);
            $field['decimal'] = ord($buf[17]);
            
            // Limpieza agresiva del nombre del campo: Solo permite letras, números y guiones bajos.
            // Esto elimina basura binaria o caracteres extraños que se colaron.
            $clean_name = preg_replace('/[^A-Za-z0-9_]/', '', trim($field['name']));

            $this->fields[] = array(
                'name' => $clean_name,
                'type' => $field['type'],
                'length' => $field['length'],
                'decimal' => $field['decimal']
            );
        }
        
        // Mover el puntero al inicio de los datos
        fseek($this->fp, $this->header['header_len']);
    }
    
    /**
     * Ejecuta get record count.
     * @return mixed Resultado de la funcion.
     */
    public function get_record_count() {
        return $this->header['record_count'];
    }
    
    /**
     * Ejecuta get fields.
     * @return mixed Resultado de la funcion.
     */
    public function get_fields() {
        return $this->fields;
    }
    
    /**
     * Ejecuta next record.
     * @return mixed Resultado de la funcion.
     */
    public function next_record() {
        if (feof($this->fp)) return false;
        
        $buf = fread($this->fp, $this->record_size);
        if (strlen($buf) < $this->record_size) return false;
        
        // El primer byte es el marcador de borrado ('*', o espacio ' ')
        // Si es '*', el registro está borrado
        $is_deleted = ($buf[0] === '*');
        
        if ($is_deleted) {
            // Recursivamente llamar al siguiente si queremos saltar borrados.
            // Se omiten registros marcados para procesar solo datos validos.
            return $this->next_record(); 
        }
        
        $record = array();
        $offset = 1; // Empezar después del marcador de borrado
        
        foreach ($this->fields as $field) {
            $raw_data = substr($buf, $offset, $field['length']);
            $val = trim($raw_data);
            
            // Conversión básica de tipos
            switch($field['type']) {
                case 'N': // Numeric
                case 'F': // Float
                    $val = is_numeric($val) ? (float)$val : 0;
                    break;
                case 'D': // Date YYYYMMDD
                    // Dejar como string YYYYMMDD o convertir si es necesario
                    break;
                case 'L': // Logical
                    $val = in_array(strtoupper($val), array('Y', 'T', '1'));
                    break;
                // 'C' (Char) se queda como string
            }
            
            // Detectar encoding si fuera necesario (asumiendo CP1252 o similar por ser Windows legacy)
            if (function_exists('mb_convert_encoding') && !empty($val) && is_string($val)) {
                $val = mb_convert_encoding($val, 'UTF-8', 'CP1252'); // Ajustar según encoding origen
            }
            
            $record[$field['name']] = $val;
            $offset += $field['length'];
        }
        
        return $record;
    }
    
    /**
     * Cierra los recursos abiertos.
     * @return mixed Resultado de la funcion.
     */
    public function close() {
        if ($this->fp) fclose($this->fp);
    }
}
?>


