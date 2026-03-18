package com.documanager.app.auth.model;

import jakarta.persistence.*;
import java.time.LocalDateTime;

@Entity
@Table(name = "usuarios")
public class Usuario {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false)
    private String nombre;

    @Column(nullable = false, unique = true)
    private String email;

    @Column(nullable = false)
    private String password;

    @Enumerated(EnumType.STRING)
    private Rol rol = Rol.VIEWER;

    private Boolean activo = true;

    @Column(name = "created_at")
    private LocalDateTime createdAt = LocalDateTime.now();

    public enum Rol { ADMIN, EDITOR, VIEWER }

    public Long getId()                   { return id; }
    public String getNombre()             { return nombre; }
    public String getEmail()              { return email; }
    public String getPassword()           { return password; }
    public Rol getRol()                   { return rol; }
    public Boolean getActivo()            { return activo; }
    public LocalDateTime getCreatedAt()   { return createdAt; }

    public void setId(Long id)                         { this.id = id; }
    public void setNombre(String nombre)               { this.nombre = nombre; }
    public void setEmail(String email)                 { this.email = email; }
    public void setPassword(String password)           { this.password = password; }
    public void setRol(Rol rol)                        { this.rol = rol; }
    public void setActivo(Boolean activo)              { this.activo = activo; }
    public void setCreatedAt(LocalDateTime createdAt)  { this.createdAt = createdAt; }
}
