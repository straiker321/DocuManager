package com.documanager.app.documento.dto;

import java.math.BigDecimal;
import java.time.LocalDate;

public class DocumentoDTO {

    private Long id;
    private String titulo;
    private String descripcion;
    private String tipo;
    private String estado;
    private Long categoriaId;
    private Long autorId;
    private String cliente;
    private LocalDate fechaDoc;
    private BigDecimal monto;
    private Boolean confidencial;
    private String version;
    private String etiquetas;

    public Long getId()              { return id; }
    public String getTitulo()        { return titulo; }
    public String getDescripcion()   { return descripcion; }
    public String getTipo()          { return tipo; }
    public String getEstado()        { return estado; }
    public Long getCategoriaId()     { return categoriaId; }
    public Long getAutorId()         { return autorId; }
    public String getCliente()       { return cliente; }
    public LocalDate getFechaDoc()   { return fechaDoc; }
    public BigDecimal getMonto()     { return monto; }
    public Boolean getConfidencial() { return confidencial; }
    public String getVersion()       { return version; }
    public String getEtiquetas()     { return etiquetas; }

    public void setId(Long id)                 { this.id = id; }
    public void setTitulo(String titulo)       { this.titulo = titulo; }
    public void setDescripcion(String d)       { this.descripcion = d; }
    public void setTipo(String tipo)           { this.tipo = tipo; }
    public void setEstado(String estado)       { this.estado = estado; }
    public void setCategoriaId(Long c)         { this.categoriaId = c; }
    public void setAutorId(Long a)             { this.autorId = a; }
    public void setCliente(String cliente)     { this.cliente = cliente; }
    public void setFechaDoc(LocalDate f)       { this.fechaDoc = f; }
    public void setMonto(BigDecimal monto)     { this.monto = monto; }
    public void setConfidencial(Boolean c)     { this.confidencial = c; }
    public void setVersion(String version)     { this.version = version; }
    public void setEtiquetas(String etiquetas) { this.etiquetas = etiquetas; }
}
