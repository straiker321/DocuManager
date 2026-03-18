package com.documanager.app.actividad.controller;

import com.documanager.app.actividad.model.Actividad;
import com.documanager.app.actividad.service.ActividadService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import java.util.List;

@RestController
@RequestMapping("/actividad")
public class ActividadController {

    private final ActividadService service;

    public ActividadController(ActividadService service) {
        this.service = service;
    }

    @GetMapping("/documento/{id}")
    public ResponseEntity<List<Actividad>> porDocumento(
            @PathVariable Long id) {
        return ResponseEntity.ok(service.porDocumento(id));
    }

    @GetMapping("/recientes")
    public ResponseEntity<List<Actividad>> recientes() {
        return ResponseEntity.ok(service.recientes());
    }
}
