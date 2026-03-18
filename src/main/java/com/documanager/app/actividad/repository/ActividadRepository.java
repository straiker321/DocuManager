package com.documanager.app.actividad.repository;

import com.documanager.app.actividad.model.Actividad;
import org.springframework.data.jpa.repository.JpaRepository;
import java.util.List;

public interface ActividadRepository
        extends JpaRepository<Actividad, Long> {

    List<Actividad> findByDocumentoIdOrderByCreatedAtDesc(Long documentoId);
    List<Actividad> findTop20ByOrderByCreatedAtDesc();
}