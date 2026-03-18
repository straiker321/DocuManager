package com.documanager.app.auth.service;

import com.documanager.app.auth.dto.*;
import com.documanager.app.auth.model.Usuario;
import com.documanager.app.auth.repository.UsuarioRepository;
import com.documanager.app.security.JwtUtil;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;

@Service
public class AuthService {

    private final UsuarioRepository repo;
    private final PasswordEncoder encoder;
    private final JwtUtil jwtUtil;

    public AuthService(UsuarioRepository repo,
                       PasswordEncoder encoder,
                       JwtUtil jwtUtil) {
        this.repo    = repo;
        this.encoder = encoder;
        this.jwtUtil = jwtUtil;
    }

    public AuthResponse login(LoginRequest req) {
        Usuario u = repo.findByEmail(req.getEmail())
            .orElseThrow(() -> new RuntimeException("Usuario no encontrado"));

        if (!u.getActivo())
            throw new RuntimeException("Usuario desactivado");

        if (!encoder.matches(req.getPassword(), u.getPassword()))
            throw new RuntimeException("Contraseña incorrecta");

        String token = jwtUtil.generarToken(u.getEmail(), u.getRol().name());
        return new AuthResponse(token, u.getNombre(), u.getEmail(), u.getRol().name(), "Login exitoso");
    }

    public AuthResponse register(RegisterRequest req) {
        if (repo.existsByEmail(req.getEmail()))
            throw new RuntimeException("Email ya registrado");

        Usuario u = new Usuario();
        u.setNombre(req.getNombre());
        u.setEmail(req.getEmail());
        u.setPassword(encoder.encode(req.getPassword()));
        u.setRol(Usuario.Rol.valueOf(req.getRol().toUpperCase()));
        repo.save(u);

        String token = jwtUtil.generarToken(u.getEmail(), u.getRol().name());
        return new AuthResponse(token, u.getNombre(), u.getEmail(), u.getRol().name(), "Registro exitoso");
    }
}
