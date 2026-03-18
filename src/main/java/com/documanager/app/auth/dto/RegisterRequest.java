package com.documanager.app.auth.dto;

public class RegisterRequest {
    private String nombre;
    private String email;
    private String password;
    private String rol = "VIEWER";

    public String getNombre()   { return nombre; }
    public String getEmail()    { return email; }
    public String getPassword() { return password; }
    public String getRol()      { return rol; }

    public void setNombre(String nombre)     { this.nombre = nombre; }
    public void setEmail(String email)       { this.email = email; }
    public void setPassword(String password) { this.password = password; }
    public void setRol(String rol)           { this.rol = rol; }
}
