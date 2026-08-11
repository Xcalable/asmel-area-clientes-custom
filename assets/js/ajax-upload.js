jQuery(document).ready(function($) {
    var dropzone = $('#asmel-uploader-dropzone');
    var fileInput = $('#asmel-uploader-input');
    var progressBar = $('#asmel-uploader-progress');
    var progressFill = $('#asmel-uploader-progress-fill');
    var progressText = $('#asmel-uploader-progress-text');
    var resultDiv = $('#asmel-uploader-result');
    var attachmentIdInput = $('#asmel-attachment-id');
    
    // Click en dropzone para abrir selector de archivos
    dropzone.on('click', function() {
        fileInput.click();
    });
    
    // Arrastrar y soltar
    dropzone.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    dropzone.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });
    
    dropzone.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleFileUpload(files[0]);
        }
    });
    
    // Selección de archivo
    fileInput.on('change', function() {
        if (this.files.length > 0) {
            handleFileUpload(this.files[0]);
        }
    });
    
    // Función para manejar la subida del archivo
    function handleFileUpload(file) {
        // Verificar tipo de archivo
        var allowedExtensions = ['.doc', '.docx', '.zip']; // Añadir .zip
        var fileName = file.name.toLowerCase();
        var fileExt = '.' + fileName.split('.').pop();
        
        if (allowedExtensions.indexOf(fileExt) === -1) {
            showError('Tipo de archivo no permitido. Solo se permiten .doc, .docx y .zip.');
            return;
        }
        
        // Mostrar barra de progreso
        progressBar.show();
        progressFill.css('width', '0%');
        progressText.text('Subiendo... 0%');
        resultDiv.hide();
        
        // Crear FormData
        var formData = new FormData();
        formData.append('action', 'asmel_upload_informe_documento');
        formData.append('nonce', asmel_ajax_upload.upload_nonce);
        formData.append('informe_documento', file);
        
        // Realizar la petición AJAX
        $.ajax({
            url: asmel_ajax_upload.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                // Escuchar el evento de progreso
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percentComplete = Math.round((e.loaded / e.total) * 100);
                        progressFill.css('width', percentComplete + '%');
                        progressText.text('Subiendo... ' + percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    showSuccess(response.data.message);
                    
                    // Lógica diferenciada: Si es carga ZIP masiva o archivo individual
                    if (response.data.is_zip_process === true) {
                        // Es un ZIP: No hay attachment_id único ni post al cual asociar.
                        // El PHP ya hizo todo el trabajo de generar PDFs y mover archivos.
                        // Solo mostramos mensaje de éxito final y quizás limpiamos el input.
                        fileInput.val(''); 
                        // El mensaje de resultado confirma la finalizacion del proceso ZIP.
                        // El flujo no requiere redireccion adicional en este punto.
                    } else {
                        // Es un archivo individual (.doc/.docx): Flujo normal
                        // Guardar el ID del attachment
                        attachmentIdInput.val(response.data.attachment_id);
                        
                        // Asociar el archivo al informe
                        associateFileToInforme(response.data.attachment_id);
                    }

                } else {
                    // Mostrar mensaje de error
                    showError(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                showError('Error en la subida: ' + error);
            },
            complete: function() {
                // Ocultar barra de progreso después de un breve delay
                setTimeout(function() {
                    progressBar.hide();
                }, 1000);
            }
        });
    }
    
    // Función para asociar el archivo al informe
    function associateFileToInforme(attachmentId) {
        var formData = new FormData();
        formData.append('action', 'asmel_associate_informe_documento');
        formData.append('nonce', asmel_ajax_upload.associate_nonce);
        formData.append('post_id', asmel_ajax_upload.post_id);
        formData.append('attachment_id', attachmentId);
        
        $.ajax({
            url: asmel_ajax_upload.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccess('Archivo asociado correctamente al informe.');
                } else {
                    showError('Error al asociar archivo: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                showError('Error al asociar archivo: ' + error);
            }
        });
    }
    
    // Funciones para mostrar mensajes
    function showSuccess(message) {
        resultDiv.removeClass('error').addClass('success').html('<p>' + message + '</p>').show();
    }
    
    function showError(message) {
        resultDiv.removeClass('success').addClass('error').html('<p>' + message + '</p>').show();
    }
});

