package com.documanager.app.documento.repository;

import com.documanager.app.documento.model.Documento;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;
import java.time.LocalDate;
import java.util.List;

public interface DocumentoRepository extends JpaRepository<Documento, Long> {

    @Query("SELECT d FROM Documento d WHERE " +
            "LOWER(d.titulo) LIKE LOWER(CONCAT('%',:q,'%')) OR " +
            "LOWER(d.descripcion) LIKE LOWER(CONCAT('%',:q,'%'))")
    List<Documento> buscar(@Param("q") String texto);

    List<Documento> findByCategoriaId(Long categoriaId);
    List<Documento> findByEstado(String estado);
    List<Documento> findByTipo(String tipo);
    List<Documento> findTop5ByEstadoOrderByVistasDesc(String estado);

    // Búsqueda avanzada con múltiples filtros opcionales
    @Query("SELECT d FROM Documento d WHERE " +
            "(:titulo IS NULL OR LOWER(d.titulo) LIKE LOWER(CONCAT('%',:titulo,'%'))) AND " +
            "(:tipo IS NULL OR d.tipo = :tipo) AND " +
            "(:estado IS NULL OR d.estado = :estado) AND " +
            "(:cliente IS NULL OR LOWER(d.cliente) LIKE LOWER(CONCAT('%',:cliente,'%'))) AND " +
            "(:categoriaId IS NULL OR d.categoriaId = :categoriaId) AND " +
            "(:fechaDesde IS NULL OR d.fechaDoc >= :fechaDesde) AND " +
            "(:fechaHasta IS NULL OR d.fechaDoc <= :fechaHasta)")
    List<Documento> busquedaAvanzada(
            @Param("titulo")      String titulo,
            @Param("tipo")        String tipo,
            @Param("estado")      String estado,
            @Param("cliente")     String cliente,
            @Param("categoriaId") Long categoriaId,
            @Param("fechaDesde")  LocalDate fechaDesde,
            @Param("fechaHasta")  LocalDate fechaHasta
    );
}