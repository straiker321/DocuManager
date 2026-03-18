package com.documanager.app.actividad.model;

import jakarta.persistence.*;
import java.time.LocalDateTime;

@Entity
@Table(name = "actividad")
public class Actividad {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "usuario_id")
    private Long usuarioId;

    @Column(nullable = false)
    private String accion;

    @Column(name = "documento_id")
    private Long documentoId;

    @Column(columnDefinition = "TEXT")
    private String descripcion;

    @Column(name = "created_at")
    private LocalDateTime createdAt = LocalDateTime.now();

    public Actividad() {}

    public Actividad(Long usuarioId, String accion,
                     Long documentoId, String descripcion) {
        this.usuarioId   = usuarioId;
        this.accion      = accion;
        this.documentoId = documentoId;
        this.descripcion = descripcion;
        this.createdAt   = LocalDateTime.now();
    }

    public Long getId()                 { return id; }
    public Long getUsuarioId()          { return usuarioId; }
    public String getAccion()           { return accion; }
    public Long getDocumentoId()        { return documentoId; }
    public String getDescripcion()      { return descripcion; }
    public LocalDateTime getCreatedAt() { return createdAt; }

    public void setId(Long id)                     { this.id = id; }
    public void setUsuarioId(Long usuarioId)       { this.usuarioId = usuarioId; }
    public void setAccion(String accion)           { this.accion = accion; }
    public void setDocumentoId(Long documentoId)   { this.documentoId = documentoId; }
    public void setDescripcion(String descripcion) { this.descripcion = descripcion; }
    public void setCreatedAt(LocalDateTime t)      { this.createdAt = t; }
}