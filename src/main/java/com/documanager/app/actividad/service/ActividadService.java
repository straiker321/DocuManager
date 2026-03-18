package com.documanager.app.actividad.service;

import com.documanager.app.actividad.model.Actividad;
import com.documanager.app.actividad.repository.ActividadRepository;
import org.springframework.stereotype.Service;
import java.util.List;

@Service
public class ActividadService {

    private final ActividadRepository repo;

    public ActividadService(ActividadRepository repo) {
        this.repo = repo;
    }

    public void registrar(Long usuarioId, String accion,
                          Long documentoId, String descripcion) {
        repo.save(new Actividad(usuarioId, accion, documentoId, descripcion));
    }

    public List<Actividad> porDocumento(Long documentoId) {
        return repo.findByDocumentoIdOrderByCreatedAtDesc(documentoId);
    }

    public List<Actividad> recientes() {
        return repo.findTop20ByOrderByCreatedAtDesc();
    }
}
