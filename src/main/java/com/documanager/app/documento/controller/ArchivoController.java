package com.documanager.app.documento.controller;

import com.documanager.app.documento.model.Documento;
import com.documanager.app.documento.repository.DocumentoRepository;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import java.io.File;
import java.io.IOException;
import java.nio.file.*;
import java.util.Map;
import java.util.UUID;

@RestController
@RequestMapping("/archivos")
public class ArchivoController {

    private final DocumentoRepository repo;

    @Value("${archivos.ruta}")
    private String rutaArchivos;

    public ArchivoController(DocumentoRepository repo) {
        this.repo = repo;
    }

    @PostMapping("/subir/{documentoId}")
    public ResponseEntity<Map<String, Object>> subir(
            @PathVariable Long documentoId,
            @RequestParam("archivo") MultipartFile archivo) {

        Documento doc = repo.findById(documentoId)
                .orElseThrow(() -> new RuntimeException("Documento no encontrado"));

        try {
            // Crear carpeta si no existe
            File carpeta = new File(rutaArchivos);
            if (!carpeta.exists()) carpeta.mkdirs();

            // Nombre único para evitar conflictos
            String extension = "";
            String nombreOriginal = archivo.getOriginalFilename();
            if (nombreOriginal != null && nombreOriginal.contains(".")) {
                extension = nombreOriginal.substring(nombreOriginal.lastIndexOf("."));
            }
            String nombreArchivo = UUID.randomUUID().toString() + extension;

            // Guardar archivo
            Path destino = Paths.get(rutaArchivos, nombreArchivo);
            Files.copy(archivo.getInputStream(), destino,
                    StandardCopyOption.REPLACE_EXISTING);

            // Actualizar documento
            doc.setArchivoNombre(nombreArchivo);
            doc.setArchivoRuta(rutaArchivos + nombreArchivo);
            repo.save(doc);

            return ResponseEntity.ok(Map.of(
                    "mensaje",         "Archivo subido correctamente",
                    "nombreArchivo",   nombreArchivo,
                    "nombreOriginal",  nombreOriginal,
                    "tamanio",         archivo.getSize()
            ));

        } catch (IOException e) {
            return ResponseEntity.internalServerError()
                    .body(Map.of("error", "Error al subir: " + e.getMessage()));
        }
    }

    @GetMapping("/descargar/{documentoId}")
    public ResponseEntity<org.springframework.core.io.Resource> descargar(
            @PathVariable Long documentoId) {

        Documento doc = repo.findById(documentoId)
                .orElseThrow(() -> new RuntimeException("Documento no encontrado"));

        if (doc.getArchivoNombre() == null)
            return ResponseEntity.notFound().build();

        try {
            Path rutaArchivo = Paths.get(rutaArchivos, doc.getArchivoNombre());
            org.springframework.core.io.Resource recurso =
                    new org.springframework.core.io.UrlResource(rutaArchivo.toUri());

            if (!recurso.exists())
                return ResponseEntity.notFound().build();

            return ResponseEntity.ok()
                    .header("Content-Disposition",
                            "attachment; filename=\"" + doc.getArchivoNombre() + "\"")
                    .body(recurso);

        } catch (Exception e) {
            return ResponseEntity.internalServerError().build();
        }
    }

    @DeleteMapping("/eliminar/{documentoId}")
    public ResponseEntity<Map<String, String>> eliminar(
            @PathVariable Long documentoId) {

        Documento doc = repo.findById(documentoId)
                .orElseThrow(() -> new RuntimeException("Documento no encontrado"));

        if (doc.getArchivoNombre() != null) {
            try {
                Path ruta = Paths.get(rutaArchivos, doc.getArchivoNombre());
                Files.deleteIfExists(ruta);
                doc.setArchivoNombre(null);
                doc.setArchivoRuta(null);
                repo.save(doc);
            } catch (IOException e) {
                return ResponseEntity.internalServerError()
                        .body(Map.of("error", "Error al eliminar archivo"));
            }
        }
        return ResponseEntity.ok(Map.of("mensaje", "Archivo eliminado"));
    }
}
