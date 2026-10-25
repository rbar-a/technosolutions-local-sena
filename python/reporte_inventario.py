#!/usr/bin/env python3
# ============================================================
# ARCHIVO: reporte_inventario.py
# UBICACIÓN: /technosolutions/python/reporte_inventario.py
# FUNCIÓN: Genera un reporte PDF profesional del inventario
#          actual por bodega, usando los datos que PHP le
#          pasa en un archivo JSON temporal.
#
# LIBRERÍAS USADAS:
#   - reportlab: genera documentos PDF desde Python
#   - json: lee el archivo de datos enviado por PHP
#   - sys: lee argumentos de la línea de comandos
#   - os: maneja rutas de archivos
#
# AUTOR: TechnoSolutions Colombia - SENA ADSO
# ============================================================

# Importamos las librerías necesarias
import json      # Para leer el archivo JSON con los datos
import sys       # Para leer argumentos del comando
import os        # Para manejar rutas de archivos
from datetime import datetime  # Para formatear fechas

# Importamos las clases de ReportLab para construir el PDF
from reportlab.lib.pagesizes import A4, letter
from reportlab.lib import colors
from reportlab.lib.units import cm
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT
from reportlab.platypus import (
    SimpleDocTemplate,  # Plantilla base del documento
    Table,              # Para crear tablas
    TableStyle,         # Para dar estilo a las tablas
    Paragraph,          # Para texto con formato
    Spacer,             # Para espacios en blanco
    HRFlowable         # Para líneas horizontales
)

# ── LEER ARGUMENTOS ──────────────────────────────────────────────────────────
# PHP llama este script así:
#   python3 reporte_inventario.py /ruta/datos.json /ruta/salida.pdf
#
# sys.argv[0] = nombre del script (reporte_inventario.py)
# sys.argv[1] = ruta del archivo JSON con los datos
# sys.argv[2] = ruta donde guardar el PDF generado

if len(sys.argv) < 3:
    print("Error: faltan argumentos. Uso: python3 reporte_inventario.py datos.json salida.pdf")
    sys.exit(1)

archivo_json = sys.argv[1]  # Archivo con los datos del inventario
archivo_pdf  = sys.argv[2]  # Archivo PDF de salida

# ── LEER DATOS JSON ──────────────────────────────────────────────────────────
# PHP genera este archivo con los datos de la base de datos
# Aquí lo leemos y lo convertimos a diccionarios Python
try:
    with open(archivo_json, 'r', encoding='utf-8') as f:
        datos = json.load(f)
except Exception as e:
    print(f"Error al leer JSON: {e}")
    sys.exit(1)

# Extraemos los datos del JSON
productos = datos.get('productos', [])   # Lista de productos
empresa   = datos.get('empresa', 'TechnoSolutions Colombia')
fecha_gen = datos.get('fecha', datetime.now().strftime('%d/%m/%Y %H:%M'))

# ── DEFINIR COLORES DE LA EMPRESA ─────────────────────────────────────────────
# Usamos los mismos colores azules del sistema web para consistencia visual
AZUL_OSCURO  = colors.HexColor('#1F3864')  # Azul oscuro del header
AZUL_MEDIO   = colors.HexColor('#2E75B6')  # Azul medio
AZUL_CLARO   = colors.HexColor('#D6E4F0')  # Azul claro para filas alternas
VERDE        = colors.HexColor('#28a745')  # Verde para stock normal
NARANJA      = colors.HexColor('#fd7e14')  # Naranja para stock bajo
ROJO         = colors.HexColor('#dc3545')  # Rojo para sin stock
GRIS_CLARO   = colors.HexColor('#f8f9fa')  # Fondo filas alternas
BLANCO       = colors.white
NEGRO        = colors.HexColor('#333333')

# ── CREAR DOCUMENTO PDF ──────────────────────────────────────────────────────
# SimpleDocTemplate configura el documento base
doc = SimpleDocTemplate(
    archivo_pdf,
    pagesize=A4,
    # Márgenes del documento (izq, der, arriba, abajo)
    leftMargin   = 2 * cm,
    rightMargin  = 2 * cm,
    topMargin    = 2 * cm,
    bottomMargin = 2 * cm
)

# Lista de elementos que conformarán el PDF (se agregan en orden)
elementos = []

# ── ESTILOS DE TEXTO ──────────────────────────────────────────────────────────
estilos = getSampleStyleSheet()

# Estilo personalizado para el título principal
estilo_titulo = ParagraphStyle(
    'titulo',
    fontName  = 'Helvetica-Bold',
    fontSize  = 20,
    textColor = AZUL_OSCURO,
    alignment = TA_CENTER,
    spaceAfter = 6
)

# Estilo para el subtítulo
estilo_subtitulo = ParagraphStyle(
    'subtitulo',
    fontName  = 'Helvetica',
    fontSize  = 11,
    textColor = AZUL_MEDIO,
    alignment = TA_CENTER,
    spaceAfter = 4
)

# Estilo para texto pequeño de metadatos (fecha, usuario)
estilo_meta = ParagraphStyle(
    'meta',
    fontName  = 'Helvetica',
    fontSize  = 9,
    textColor = colors.HexColor('#888888'),
    alignment = TA_CENTER,
    spaceAfter = 2
)

# Estilo para los títulos de sección
estilo_seccion = ParagraphStyle(
    'seccion',
    fontName  = 'Helvetica-Bold',
    fontSize  = 12,
    textColor = AZUL_OSCURO,
    spaceBefore = 14,
    spaceAfter  = 6
)

# ── ENCABEZADO DEL REPORTE ────────────────────────────────────────────────────
# Agregamos los elementos del encabezado a la lista
elementos.append(Paragraph(empresa, estilo_titulo))
elementos.append(Paragraph('Reporte de Inventario General', estilo_subtitulo))
elementos.append(Paragraph(f'Generado el: {fecha_gen}', estilo_meta))

# Línea separadora decorativa
elementos.append(HRFlowable(
    width     = '100%',
    thickness = 2,
    color     = AZUL_MEDIO,
    spaceAfter = 12
))

# ── RESUMEN ESTADÍSTICO ───────────────────────────────────────────────────────
# Calculamos estadísticas generales para el resumen
total_productos  = len(productos)
total_unidades   = sum(p.get('stock_total', 0) for p in productos)
productos_ok     = sum(1 for p in productos if p.get('stock_total', 0) > p.get('stock_minimo', 0))
productos_bajos  = sum(1 for p in productos if 0 < p.get('stock_total', 0) <= p.get('stock_minimo', 0))
productos_sin    = sum(1 for p in productos if p.get('stock_total', 0) == 0)

elementos.append(Paragraph('Resumen General', estilo_seccion))

# Tabla de resumen con 4 columnas
datos_resumen = [
    # Fila de encabezados
    ['Total Productos', 'Total Unidades', 'Stock Normal', 'Requieren Atención'],
    # Fila de valores
    [
        str(total_productos),
        str(total_unidades),
        str(productos_ok),
        str(productos_bajos + productos_sin)
    ]
]

tabla_resumen = Table(datos_resumen, colWidths=[4.2*cm, 4.2*cm, 4.2*cm, 4.2*cm])
tabla_resumen.setStyle(TableStyle([
    # Encabezado azul oscuro con texto blanco
    ('BACKGROUND', (0,0), (-1,0), AZUL_OSCURO),
    ('TEXTCOLOR',  (0,0), (-1,0), BLANCO),
    ('FONTNAME',   (0,0), (-1,0), 'Helvetica-Bold'),
    ('FONTSIZE',   (0,0), (-1,0), 10),
    ('ALIGN',      (0,0), (-1,-1), 'CENTER'),
    ('VALIGN',     (0,0), (-1,-1), 'MIDDLE'),
    ('PADDING',    (0,0), (-1,-1), 10),
    # Fila de valores con fondo azul claro
    ('BACKGROUND', (0,1), (-1,1), AZUL_CLARO),
    ('FONTNAME',   (0,1), (-1,1), 'Helvetica-Bold'),
    ('FONTSIZE',   (0,1), (-1,1), 16),
    ('TEXTCOLOR',  (0,1), (-1,1), AZUL_OSCURO),
    # Bordes de toda la tabla
    ('GRID',       (0,0), (-1,-1), 0.5, AZUL_MEDIO),
    ('ROUNDEDCORNERS', [4]),
]))

elementos.append(tabla_resumen)
elementos.append(Spacer(1, 16))

# ── TABLA PRINCIPAL DE INVENTARIO ────────────────────────────────────────────
elementos.append(Paragraph('Detalle del Inventario por Producto', estilo_seccion))

# Encabezados de la tabla principal
encabezados = [
    'Código', 'Nombre del Producto', 'Precio', 'Stock Total', 'Mín.', 'Estado'
]

# Filas de datos de productos
filas = [encabezados]

for p in productos:
    stock       = p.get('stock_total', 0)
    stock_min   = p.get('stock_minimo', 5)
    precio      = p.get('precio', 0)

    # Determinamos el estado del stock
    if stock == 0:
        estado = 'SIN STOCK'
    elif stock <= stock_min:
        estado = 'STOCK BAJO'
    else:
        estado = 'NORMAL'

    # Formateamos el precio con separador de miles
    precio_fmt = f"${precio:,.0f}".replace(',', '.')

    filas.append([
        p.get('codigo_barras', ''),
        p.get('nombre', ''),
        precio_fmt,
        str(stock),
        str(stock_min),
        estado
    ])

# Ancho de cada columna (debe sumar el ancho disponible)
tabla_inv = Table(
    filas,
    colWidths=[3*cm, 6.5*cm, 2.5*cm, 2.2*cm, 1.5*cm, 2.8*cm]
)

# Construimos el estilo base de la tabla
estilo_tabla = [
    # ── Encabezado ──
    ('BACKGROUND', (0,0), (-1,0), AZUL_OSCURO),
    ('TEXTCOLOR',  (0,0), (-1,0), BLANCO),
    ('FONTNAME',   (0,0), (-1,0), 'Helvetica-Bold'),
    ('FONTSIZE',   (0,0), (-1,0), 9),
    ('ALIGN',      (0,0), (-1,0), 'CENTER'),
    ('VALIGN',     (0,0), (-1,-1), 'MIDDLE'),
    ('PADDING',    (0,0), (-1,-1), 6),
    # ── Datos ──
    ('FONTNAME',   (0,1), (-1,-1), 'Helvetica'),
    ('FONTSIZE',   (0,1), (-1,-1), 8),
    ('TEXTCOLOR',  (0,1), (-1,-1), NEGRO),
    # Alineación centrada para columnas numéricas
    ('ALIGN',      (2,1), (-1,-1), 'CENTER'),
    # Bordes
    ('GRID',       (0,0), (-1,-1), 0.3, colors.HexColor('#CCCCCC')),
    ('LINEBELOW',  (0,0), (-1,0), 1.5, AZUL_MEDIO),
]

# Aplicamos colores alternos a las filas y color al estado
for i, fila in enumerate(filas[1:], start=1):
    # Filas alternas con fondo gris muy claro
    if i % 2 == 0:
        estilo_tabla.append(('BACKGROUND', (0,i), (-1,i), GRIS_CLARO))

    # Color de la celda de estado según el valor
    estado_val = fila[5]
    if estado_val == 'SIN STOCK':
        # Fondo rojo claro para sin stock
        estilo_tabla.append(('BACKGROUND', (5,i), (5,i), colors.HexColor('#fde8e8')))
        estilo_tabla.append(('TEXTCOLOR',  (5,i), (5,i), ROJO))
        estilo_tabla.append(('FONTNAME',   (5,i), (5,i), 'Helvetica-Bold'))
    elif estado_val == 'STOCK BAJO':
        # Fondo naranja claro para stock bajo
        estilo_tabla.append(('BACKGROUND', (5,i), (5,i), colors.HexColor('#fff3cd')))
        estilo_tabla.append(('TEXTCOLOR',  (5,i), (5,i), colors.HexColor('#856404')))
        estilo_tabla.append(('FONTNAME',   (5,i), (5,i), 'Helvetica-Bold'))
    else:
        # Fondo verde claro para stock normal
        estilo_tabla.append(('BACKGROUND', (5,i), (5,i), colors.HexColor('#d4edda')))
        estilo_tabla.append(('TEXTCOLOR',  (5,i), (5,i), colors.HexColor('#155724')))

tabla_inv.setStyle(TableStyle(estilo_tabla))
elementos.append(tabla_inv)

# ── PIE DEL REPORTE ───────────────────────────────────────────────────────────
elementos.append(Spacer(1, 20))
elementos.append(HRFlowable(width='100%', thickness=0.5, color=colors.HexColor('#CCCCCC')))
elementos.append(Paragraph(
    f'Reporte generado automáticamente por el Sistema de Gestión de Inventario — {empresa} — SENA ADSO 2025',
    estilo_meta
))

# ── GENERAR EL PDF ────────────────────────────────────────────────────────────
# build() toma la lista de elementos y genera el archivo PDF
try:
    doc.build(elementos)
    # Imprimimos OK para que PHP sepa que todo salió bien
    print("OK")
except Exception as e:
    print(f"Error al generar PDF: {e}")
    sys.exit(1)