package com.documanager.app.documento.model;

import jakarta.persistence.*;
import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;

@Entity
@Table(name = "documentos")
public class Documento {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private String titulo;

    @Column(columnDefinition = "TEXT")
    private String descripcion;

    private String tipo;
    private String estado = "BORRADOR";

    @Column(name = "categoria_id")
    private Long categoriaId;

    @Column(name = "autor_id")
    private Long autorId;

    private String cliente;

    @Column(name = "fecha_doc")
    private LocalDate fechaDoc;

    private BigDecimal monto;
    private Boolean confidencial = false;
    private String version = "1.0";
    private String etiquetas;

    @Column(name = "archivo_nombre")
    private String archivoNombre;

    @Column(name = "archivo_ruta")
    private String archivoRuta;

    private Integer vistas = 0;

    @Column(name = "created_at")
    private LocalDateTime createdAt = LocalDateTime.now();

    @Column(name = "updated_at")
    private LocalDateTime updatedAt = LocalDateTime.now();

    public Long getId()                 { return id; }
    public String getTitulo()           { return titulo; }
    public String getDescripcion()      { return descripcion; }
    public String getTipo()             { return tipo; }
    public String getEstado()           { return estado; }
    public Long getCategoriaId()        { return categoriaId; }
    public Long getAutorId()            { return autorId; }
    public String getCliente()          { return cliente; }
    public LocalDate getFechaDoc()      { return fechaDoc; }
    public BigDecimal getMonto()        { return monto; }
    public Boolean getConfidencial()    { return confidencial; }
    public String getVersion()          { return version; }
    public String getEtiquetas()        { return etiquetas; }
    public String getArchivoNombre()    { return archivoNombre; }
    public String getArchivoRuta()      { return archivoRuta; }
    public Integer getVistas()          { return vistas; }
    public LocalDateTime getCreatedAt() { return createdAt; }
    public LocalDateTime getUpdatedAt() { return updatedAt; }

    public void setId(Long id)                      { this.id = id; }
    public void setTitulo(String titulo)            { this.titulo = titulo; }
    public void setDescripcion(String descripcion)  { this.descripcion = descripcion; }
    public void setTipo(String tipo)                { this.tipo = tipo; }
    public void setEstado(String estado)            { this.estado = estado; }
    public void setCategoriaId(Long categoriaId)    { this.categoriaId = categoriaId; }
    public void setAutorId(Long autorId)            { this.autorId = autorId; }
    public void setCliente(String cliente)          { this.cliente = cliente; }
    public void setFechaDoc(LocalDate fechaDoc)     { this.fechaDoc = fechaDoc; }
    public void setMonto(BigDecimal monto)          { this.monto = monto; }
    public void setConfidencial(Boolean c)          { this.confidencial = c; }
    public void setVersion(String version)          { this.version = version; }
    public void setEtiquetas(String etiquetas)      { this.etiquetas = etiquetas; }
    public void setArchivoNombre(String n)          { this.archivoNombre = n; }
    public void setArchivoRuta(String r)            { this.archivoRuta = r; }
    public void setVistas(Integer vistas)           { this.vistas = vistas; }
    public void setCreatedAt(LocalDateTime t)       { this.createdAt = t; }
    public void setUpdatedAt(LocalDateTime t)       { this.updatedAt = t; }
}
