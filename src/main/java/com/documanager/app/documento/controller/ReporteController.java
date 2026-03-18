package com.documanager.app.documento.controller;

import com.documanager.app.documento.model.Documento;
import com.documanager.app.documento.repository.DocumentoRepository;
import org.apache.poi.ss.usermodel.*;
import org.apache.poi.ss.util.CellRangeAddress;
import org.apache.poi.xssf.usermodel.*;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.io.ByteArrayOutputStream;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.List;

@RestController
@RequestMapping("/reportes")
public class ReporteController {

    private final DocumentoRepository repo;

    public ReporteController(DocumentoRepository repo) {
        this.repo = repo;
    }

    @GetMapping("/excel")
    public ResponseEntity<byte[]> exportarExcel(
            @RequestParam(required = false) String estado,
            @RequestParam(required = false) String tipo,
            @RequestParam(required = false) String cliente) {

        List<Documento> docs;
        if (estado != null && !estado.isEmpty())
            docs = repo.findByEstado(estado);
        else if (tipo != null && !tipo.isEmpty())
            docs = repo.findByTipo(tipo);
        else
            docs = repo.findAll();

        try (XSSFWorkbook wb = new XSSFWorkbook();
             ByteArrayOutputStream out = new ByteArrayOutputStream()) {

            XSSFSheet sheet = wb.createSheet("Documentos");

            // ── Estilo título ──
            XSSFCellStyle estiloTitulo = wb.createCellStyle();
            XSSFFont fuenteTitulo = wb.createFont();
            fuenteTitulo.setBold(true);
            fuenteTitulo.setFontHeightInPoints((short) 14);
            fuenteTitulo.setColor(new XSSFColor(new byte[]{(byte)255,(byte)255,(byte)255}, null));
            estiloTitulo.setFont(fuenteTitulo);
            estiloTitulo.setFillForegroundColor(
                    new XSSFColor(new byte[]{(byte)79,(byte)70,(byte)229}, null));
            estiloTitulo.setFillPattern(FillPatternType.SOLID_FOREGROUND);
            estiloTitulo.setAlignment(HorizontalAlignment.CENTER);

            // ── Estilo cabecera ──
            XSSFCellStyle estiloHeader = wb.createCellStyle();
            XSSFFont fuenteHeader = wb.createFont();
            fuenteHeader.setBold(true);
            fuenteHeader.setColor(new XSSFColor(new byte[]{(byte)255,(byte)255,(byte)255}, null));
            estiloHeader.setFont(fuenteHeader);
            estiloHeader.setFillForegroundColor(
                    new XSSFColor(new byte[]{(byte)99,(byte)102,(byte)241}, null));
            estiloHeader.setFillPattern(FillPatternType.SOLID_FOREGROUND);
            estiloHeader.setAlignment(HorizontalAlignment.CENTER);

            // ── Estilo fila par ──
            XSSFCellStyle estiloPar = wb.createCellStyle();
            estiloPar.setFillForegroundColor(
                    new XSSFColor(new byte[]{(byte)238,(byte)242,(byte)255}, null));
            estiloPar.setFillPattern(FillPatternType.SOLID_FOREGROUND);

            // ── Estilo total ──
            XSSFCellStyle estiloTotal = wb.createCellStyle();
            XSSFFont fTotal = wb.createFont();
            fTotal.setBold(true);
            estiloTotal.setFont(fTotal);
            estiloTotal.setFillForegroundColor(
                    new XSSFColor(new byte[]{(byte)238,(byte)242,(byte)255}, null));
            estiloTotal.setFillPattern(FillPatternType.SOLID_FOREGROUND);

            // ── Fila título ──
            Row rowTitulo = sheet.createRow(0);
            rowTitulo.setHeightInPoints(28);
            Cell celdaTitulo = rowTitulo.createCell(0);
            celdaTitulo.setCellValue("REPORTE DE DOCUMENTOS — DocuManager");
            celdaTitulo.setCellStyle(estiloTitulo);
            sheet.addMergedRegion(new CellRangeAddress(0, 0, 0, 8));

            // ── Fila subtítulo ──
            Row rowSub = sheet.createRow(1);
            Cell celdaSub = rowSub.createCell(0);
            celdaSub.setCellValue("Generado el " +
                    LocalDateTime.now().format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm")));
            sheet.addMergedRegion(new CellRangeAddress(1, 1, 0, 8));

            // ── Cabeceras ──
            String[] cabeceras = {"#","Título","Tipo","Estado",
                    "Cliente","Fecha","Confidencial","Vistas","Etiquetas"};
            Row rowHeader = sheet.createRow(2);
            rowHeader.setHeightInPoints(20);
            for (int i = 0; i < cabeceras.length; i++) {
                Cell c = rowHeader.createCell(i);
                c.setCellValue(cabeceras[i]);
                c.setCellStyle(estiloHeader);
            }

            // ── Datos ──
            DateTimeFormatter fmt = DateTimeFormatter.ofPattern("dd/MM/yyyy");
            int fila = 3;
            for (Documento d : docs) {
                Row row = sheet.createRow(fila);

                // Aplicar estilo par
                if (fila % 2 == 0) {
                    for (int col = 0; col < 9; col++) {
                        row.createCell(col).setCellStyle(estiloPar);
                    }
                }

                setCelda(row, 0, String.valueOf(d.getId()));
                setCelda(row, 1, d.getTitulo()    != null ? d.getTitulo()    : "");
                setCelda(row, 2, d.getTipo()      != null ? d.getTipo()      : "");
                setCelda(row, 3, d.getEstado()    != null ? d.getEstado()    : "");
                setCelda(row, 4, d.getCliente()   != null ? d.getCliente()   : "");
                setCelda(row, 5, d.getFechaDoc()  != null ? d.getFechaDoc().format(fmt) : "");
                setCelda(row, 6, Boolean.TRUE.equals(d.getConfidencial()) ? "Sí" : "No");
                setCelda(row, 7, String.valueOf(d.getVistas() != null ? d.getVistas() : 0));
                setCelda(row, 8, d.getEtiquetas() != null ? d.getEtiquetas() : "");

                fila++;
            }

            // ── Fila total ──
            Row rowTotal = sheet.createRow(fila);
            Cell celdaTotal = rowTotal.createCell(0);
            celdaTotal.setCellValue("Total: " + docs.size() + " documentos");
            celdaTotal.setCellStyle(estiloTotal);
            sheet.addMergedRegion(new CellRangeAddress(fila, fila, 0, 8));

            // ── Anchos de columna ──
            sheet.setColumnWidth(0, 1500);
            sheet.setColumnWidth(1, 9000);
            sheet.setColumnWidth(2, 4000);
            sheet.setColumnWidth(3, 4000);
            sheet.setColumnWidth(4, 6000);
            sheet.setColumnWidth(5, 3500);
            sheet.setColumnWidth(6, 3500);
            sheet.setColumnWidth(7, 2500);
            sheet.setColumnWidth(8, 5000);

            wb.write(out);

            String nombreArchivo = "documentos_" + LocalDate.now() + ".xlsx";

            return ResponseEntity.ok()
                    .header(HttpHeaders.CONTENT_DISPOSITION,
                            "attachment; filename=" + nombreArchivo)
                    .contentType(MediaType.parseMediaType(
                            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"))
                    .body(out.toByteArray());

        } catch (Exception e) {
            return ResponseEntity.internalServerError().build();
        }
    }

    private void setCelda(Row row, int col, String valor) {
        Cell c = row.getCell(col);
        if (c == null) c = row.createCell(col);
        c.setCellValue(valor);
    }
}