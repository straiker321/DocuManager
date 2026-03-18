package com.documanager.app.categoria.service;

import com.documanager.app.categoria.model.Categoria;
import com.documanager.app.categoria.repository.CategoriaRepository;
import org.springframework.stereotype.Service;
import java.util.List;

@Service
public class CategoriaService {

    private final CategoriaRepository repo;

    public CategoriaService(CategoriaRepository repo) {
        this.repo = repo;
    }

    public List<Categoria> listar() {
        return repo.findAll();
    }

    public Categoria obtener(Long id) {
        return repo.findById(id)
            .orElseThrow(() -> new RuntimeException("Categoría no encontrada: " + id));
    }

    public Categoria crear(Categoria cat) {
        if (repo.existsByNombre(cat.getNombre()))
            throw new RuntimeException("Ya existe esa categoría");
        return repo.save(cat);
    }

    public Categoria actualizar(Long id, Categoria datos) {
        Categoria cat = obtener(id);
        if (datos.getNombre() != null) cat.setNombre(datos.getNombre());
        if (datos.getColor()  != null) cat.setColor(datos.getColor());
        return repo.save(cat);
    }

    public void eliminar(Long id) {
        obtener(id);
        repo.deleteById(id);
    }
}
