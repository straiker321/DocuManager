package com.documanager.app.categoria.model;

import jakarta.persistence.*;

@Entity
@Table(name = "categorias")
public class Categoria {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private String nombre;

    private String color = "#3B82F6";

    public Long getId()       { return id; }
    public String getNombre() { return nombre; }
    public String getColor()  { return color; }

    public void setId(Long id)           { this.id = id; }
    public void setNombre(String nombre) { this.nombre = nombre; }
    public void setColor(String color)   { this.color = color; }
}
