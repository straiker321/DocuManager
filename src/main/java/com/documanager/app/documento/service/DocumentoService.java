package com.documanager.app.documento.service;

import com.documanager.app.actividad.service.ActividadService;
import com.documanager.app.documento.dto.DocumentoDTO;
import com.documanager.app.documento.model.Documento;
import com.documanager.app.documento.repository.DocumentoRepository;
import org.springframework.stereotype.Service;

import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.List;

@Service
public class DocumentoService {

    private final DocumentoRepository repo;

    private final ActividadService actividadService;

    public DocumentoService(DocumentoRepository repo, ActividadService actividadService) {
        this.repo = repo;
        this.actividadService = actividadService;
    }

    public List<Documento> listar() { return repo.findAll(); }

    public List<Documento> buscar(String q) { return repo.buscar(q); }

    public List<Documento> porCategoria(Long id) { return repo.findByCategoriaId(id); }

    public List<Documento> porEstado(String estado) { return repo.findByEstado(estado); }

    public Documento obtener(Long id) {
        Documento doc = repo.findById(id)
            .orElseThrow(() -> new RuntimeException("Documento no encontrado: " + id));
        doc.setVistas(doc.getVistas() + 1);
        return repo.save(doc);
    }

    public Documento crear(DocumentoDTO dto) {
        Documento doc = new Documento();
        doc.setTitulo(dto.getTitulo());
        doc.setDescripcion(dto.getDescripcion());
        doc.setTipo(dto.getTipo());
        doc.setEstado(dto.getEstado() != null ? dto.getEstado() : "BORRADOR");
        doc.setCategoriaId(dto.getCategoriaId());
        doc.setAutorId(dto.getAutorId());
        doc.setCliente(dto.getCliente());
        doc.setFechaDoc(dto.getFechaDoc());
        doc.setMonto(dto.getMonto());
        doc.setConfidencial(dto.getConfidencial() != null ? dto.getConfidencial() : false);
        doc.setVersion(dto.getVersion() != null ? dto.getVersion() : "1.0");
        doc.setEtiquetas(dto.getEtiquetas());
        doc.setCreatedAt(LocalDateTime.now());
        doc.setUpdatedAt(LocalDateTime.now());
        actividadService.registrar(
                dto.getAutorId(),
                "CREAR",
                doc.getId(),
                "Documento creado: " + doc.getTitulo()
        );
        return repo.save(doc);
    }

    public Documento actualizar(Long id, DocumentoDTO dto) {
        Documento doc = repo.findById(id)
            .orElseThrow(() -> new RuntimeException("Documento no encontrado: " + id));
        if (dto.getTitulo()       != null) doc.setTitulo(dto.getTitulo());
        if (dto.getDescripcion()  != null) doc.setDescripcion(dto.getDescripcion());
        if (dto.getTipo()         != null) doc.setTipo(dto.getTipo());
        if (dto.getEstado()       != null) doc.setEstado(dto.getEstado());
        if (dto.getCliente()      != null) doc.setCliente(dto.getCliente());
        if (dto.getFechaDoc()     != null) doc.setFechaDoc(dto.getFechaDoc());
        if (dto.getMonto()        != null) doc.setMonto(dto.getMonto());
        if (dto.getEtiquetas()    != null) doc.setEtiquetas(dto.getEtiquetas());
        if (dto.getConfidencial() != null) doc.setConfidencial(dto.getConfidencial());
        doc.setUpdatedAt(LocalDateTime.now());

        actividadService.registrar(
                null,
                "EDITAR",
                id,
                "Documento actualizado: " + doc.getTitulo()
        );
        return repo.save(doc);
    }

    public void archivar(Long id) {
        Documento doc = repo.findById(id)
            .orElseThrow(() -> new RuntimeException("Documento no encontrado: " + id));
        doc.setEstado("ARCHIVADO");
        doc.setUpdatedAt(LocalDateTime.now());
        actividadService.registrar(
                null,
                "ARCHIVAR",
                id,
                "Documento archivado: " + doc.getTitulo()
        );
        repo.save(doc);
    }

    public long total() { return repo.count(); }

    public List<Documento> masVistos() {
        return repo.findTop5ByEstadoOrderByVistasDesc("PUBLICADO");
    }

    public List<Documento> busquedaAvanzada(
            String titulo, String tipo, String estado,
            String cliente, Long categoriaId,
            String fechaDesde, String fechaHasta) {

        LocalDate desde = fechaDesde != null && !fechaDesde.isEmpty()
                ? LocalDate.parse(fechaDesde) : null;
        LocalDate hasta = fechaHasta != null && !fechaHasta.isEmpty()
                ? LocalDate.parse(fechaHasta) : null;

        return repo.busquedaAvanzada(
                titulo   != null && !titulo.isEmpty()   ? titulo   : null,
                tipo     != null && !tipo.isEmpty()     ? tipo     : null,
                estado   != null && !estado.isEmpty()   ? estado   : null,
                cliente  != null && !cliente.isEmpty()  ? cliente  : null,
                categoriaId,
                desde,
                hasta
        );
    }
}
