package com.documanager.app.documento.controller;

import com.documanager.app.documento.dto.DocumentoDTO;
import com.documanager.app.documento.model.Documento;
import com.documanager.app.documento.service.DocumentoService;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/documentos")
public class DocumentoController {

    private final DocumentoService service;

    public DocumentoController(DocumentoService service) {
        this.service = service;
    }

    @GetMapping("/ping")
    public ResponseEntity<String> ping() {
        return ResponseEntity.ok("Documentos OK");
    }

    @GetMapping
    public ResponseEntity<List<Documento>> listar(
            @RequestParam(required = false) String buscar,
            @RequestParam(required = false) Long categoriaId,
            @RequestParam(required = false) String estado,
            @RequestParam(required = false) String tipo,
            @RequestParam(required = false) String cliente,
            @RequestParam(required = false) String fechaDesde,
            @RequestParam(required = false) String fechaHasta) {

        // Búsqueda avanzada si viene algún filtro avanzado
        if (tipo != null || cliente != null ||
                fechaDesde != null || fechaHasta != null) {
            return ResponseEntity.ok(service.busquedaAvanzada(
                    buscar, tipo, estado, cliente,
                    categoriaId, fechaDesde, fechaHasta));
        }

        // Filtros simples
        if (buscar      != null) return ResponseEntity.ok(service.buscar(buscar));
        if (categoriaId != null) return ResponseEntity.ok(service.porCategoria(categoriaId));
        if (estado      != null) return ResponseEntity.ok(service.porEstado(estado));
        return ResponseEntity.ok(service.listar());
    }

    @GetMapping("/{id}")
    public ResponseEntity<Documento> obtener(@PathVariable Long id) {
        return ResponseEntity.ok(service.obtener(id));
    }

    @PostMapping
    public ResponseEntity<Documento> crear(@RequestBody DocumentoDTO dto) {
        return ResponseEntity.status(HttpStatus.CREATED).body(service.crear(dto));
    }

    @PutMapping("/{id}")
    public ResponseEntity<Documento> actualizar(@PathVariable Long id,
                                                  @RequestBody DocumentoDTO dto) {
        return ResponseEntity.ok(service.actualizar(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> archivar(@PathVariable Long id) {
        service.archivar(id);
        return ResponseEntity.noContent().build();
    }

    @GetMapping("/estadisticas")
    public ResponseEntity<Map<String, Object>> estadisticas() {
        return ResponseEntity.ok(Map.of(
            "total",     service.total(),
            "masVistos", service.masVistos()
        ));
    }
}
