package com.documanager.app.auth.dto;

public class AuthResponse {
    private String token;
    private String nombre;
    private String email;
    private String rol;
    private String mensaje;

    public AuthResponse(String token, String nombre,
                        String email, String rol, String mensaje) {
        this.token   = token;
        this.nombre  = nombre;
        this.email   = email;
        this.rol     = rol;
        this.mensaje = mensaje;
    }

    public String getToken()   { return token; }
    public String getNombre()  { return nombre; }
    public String getEmail()   { return email; }
    public String getRol()     { return rol; }
    public String getMensaje() { return mensaje; }
}
