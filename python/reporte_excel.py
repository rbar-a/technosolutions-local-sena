#!/usr/bin/env python3
# ============================================================
# ARCHIVO: reporte_excel.py
# UBICACIÓN: /technosolutions/python/reporte_excel.py
# FUNCIÓN: Genera un archivo Excel (.xlsx) con el inventario
#          completo, con formato profesional y múltiples hojas.
#
# LIBRERÍAS USADAS:
#   - openpyxl: crea y da formato a archivos Excel
#   - json: lee los datos enviados por PHP
#   - sys, os: manejo de argumentos y rutas
#
# AUTOR: TechnoSolutions Colombia - SENA ADSO
# ============================================================

import json
import sys
import os
from datetime import datetime

# Importamos openpyxl y sus módulos de formato
from openpyxl import Workbook
from openpyxl.styles import (
    Font,           # Fuente (negrita, color, tamaño)
    PatternFill,    # Relleno de celda
    Alignment,      # Alineación del texto
    Border,         # Bordes de celda
    Side            # Estilo de cada lado del borde
)
from openpyxl.utils import get_column_letter  # Convierte número a letra (1→A, 2→B)

# ── LEER ARGUMENTOS ──────────────────────────────────────────────────────────
if len(sys.argv) < 3:
    print("Error: faltan argumentos.")
    sys.exit(1)

archivo_json  = sys.argv[1]
archivo_excel = sys.argv[2]

# ── LEER DATOS JSON ──────────────────────────────────────────────────────────
try:
    with open(archivo_json, 'r', encoding='utf-8') as f:
        datos = json.load(f)
except Exception as e:
    print(f"Error al leer JSON: {e}")
    sys.exit(1)

productos = datos.get('productos', [])
empresa   = datos.get('empresa', 'TechnoSolutions Colombia')
fecha_gen = datos.get('fecha', datetime.now().strftime('%d/%m/%Y %H:%M'))

# ── DEFINIR ESTILOS ───────────────────────────────────────────────────────────
# Colores en formato ARGB (Alpha + RGB)
COLOR_AZUL_OSCURO = '1F3864'   # Header principal
COLOR_AZUL_MEDIO  = '2E75B6'   # Subheader
COLOR_AZUL_CLARO  = 'D6E4F0'   # Filas alternas
COLOR_VERDE       = 'd4edda'   # Stock normal
COLOR_NARANJA     = 'fff3cd'   # Stock bajo
COLOR_ROJO        = 'fde8e8'   # Sin stock
COLOR_GRIS        = 'F5F7FA'   # Fondo alterno

# Función auxiliar para crear un borde sencillo en una celda
def borde_simple():
    lado = Side(style='thin', color='CCCCCC')
    return Border(left=lado, right=lado, top=lado, bottom=lado)

# Función auxiliar para crear un relleno sólido de color
def relleno(color_hex):
    return PatternFill(start_color=color_hex, end_color=color_hex, fill_type='solid')

# ── CREAR LIBRO DE EXCEL ──────────────────────────────────────────────────────
wb = Workbook()  # Crea un nuevo libro de Excel vacío

# ════════════════════════════════════════════════════════════════════════════
#  HOJA 1: INVENTARIO GENERAL
# ════════════════════════════════════════════════════════════════════════════
ws1 = wb.active          # La primera hoja viene creada por defecto
ws1.title = 'Inventario'  # Le damos un nombre descriptivo

# ── TÍTULO PRINCIPAL ──────────────────────────────────────────────────────────
# Fila 1: Título de la empresa
ws1['A1'] = empresa
ws1['A1'].font      = Font(name='Arial', size=16, bold=True, color='FFFFFF')
ws1['A1'].fill      = relleno(COLOR_AZUL_OSCURO)
ws1['A1'].alignment = Alignment(horizontal='center', vertical='center')
# merge_cells une las celdas A1 hasta G1 en una sola celda grande
ws1.merge_cells('A1:G1')
ws1.row_dimensions[1].height = 35  # Altura de la fila en puntos

# Fila 2: Subtítulo del reporte
ws1['A2'] = 'Reporte de Inventario General'
ws1['A2'].font      = Font(name='Arial', size=12, bold=True, color='FFFFFF')
ws1['A2'].fill      = relleno(COLOR_AZUL_MEDIO)
ws1['A2'].alignment = Alignment(horizontal='center', vertical='center')
ws1.merge_cells('A2:G2')
ws1.row_dimensions[2].height = 25

# Fila 3: Fecha de generación
ws1['A3'] = f'Generado el: {fecha_gen}'
ws1['A3'].font      = Font(name='Arial', size=9, italic=True, color='888888')
ws1['A3'].alignment = Alignment(horizontal='center')
ws1.merge_cells('A3:G3')

# Fila 4: vacía como separador
ws1.row_dimensions[4].height = 8

# ── ENCABEZADOS DE LA TABLA ───────────────────────────────────────────────────
# Fila 5: encabezados de columnas
encabezados = [
    'Código de Barras',
    'Nombre del Producto',
    'Descripción',
    'Precio',
    'Stock Total',
    'Stock Mínimo',
    'Estado'
]

for col, titulo in enumerate(encabezados, start=1):
    celda = ws1.cell(row=5, column=col)
    celda.value     = titulo
    celda.font      = Font(name='Arial', size=10, bold=True, color='FFFFFF')
    celda.fill      = relleno(COLOR_AZUL_OSCURO)
    celda.alignment = Alignment(horizontal='center', vertical='center', wrap_text=True)
    celda.border    = borde_simple()

ws1.row_dimensions[5].height = 30

# ── DATOS DE PRODUCTOS ────────────────────────────────────────────────────────
for i, p in enumerate(productos):
    fila_excel = i + 6  # Empezamos en la fila 6 (después de los encabezados)
    stock     = p.get('stock_total', 0)
    stock_min = p.get('stock_minimo', 5)
    precio    = p.get('precio', 0)

    # Determinamos el estado y el color de fondo para esa fila
    if stock == 0:
        estado      = 'SIN STOCK'
        color_fondo = COLOR_ROJO
    elif stock <= stock_min:
        estado      = 'STOCK BAJO'
        color_fondo = COLOR_NARANJA
    else:
        estado      = 'NORMAL'
        color_fondo = COLOR_VERDE

    # Color alterno para filas sin alerta (gris o blanco)
    color_fila = COLOR_GRIS if (i % 2 == 0 and estado == 'NORMAL') else \
                 'FFFFFF'   if (i % 2 != 0 and estado == 'NORMAL') else color_fondo

    # Datos de cada columna
    datos_fila = [
        p.get('codigo_barras', ''),
        p.get('nombre', ''),
        p.get('descripcion', ''),
        precio,          # Número para poder formatear
        stock,           # Número para poder formatear
        stock_min,
        estado
    ]

    for col, valor in enumerate(datos_fila, start=1):
        celda           = ws1.cell(row=fila_excel, column=col)
        celda.value     = valor
        celda.border    = borde_simple()
        celda.alignment = Alignment(vertical='center', wrap_text=True)
        celda.font      = Font(name='Arial', size=9)

        # La columna de estado (7) siempre lleva el color del estado
        if col == 7:
            celda.fill      = relleno(color_fondo)
            celda.font      = Font(name='Arial', size=9, bold=True)
            celda.alignment = Alignment(horizontal='center', vertical='center')
        elif estado != 'NORMAL':
            # Si hay alerta, toda la fila lleva el color del estado
            celda.fill = relleno(color_fondo)
        else:
            # Filas normales alternas
            celda.fill = relleno(color_fila)

    # Formato de precio como moneda en la columna D
    ws1.cell(row=fila_excel, column=4).number_format = '$#,##0'
    # Alineación centrada para las columnas numéricas
    ws1.cell(row=fila_excel, column=4).alignment = Alignment(horizontal='right',  vertical='center')
    ws1.cell(row=fila_excel, column=5).alignment = Alignment(horizontal='center', vertical='center')
    ws1.cell(row=fila_excel, column=6).alignment = Alignment(horizontal='center', vertical='center')

    ws1.row_dimensions[fila_excel].height = 18

# ── AJUSTAR ANCHO DE COLUMNAS ─────────────────────────────────────────────────
# Definimos el ancho de cada columna manualmente para mejor presentación
anchos = [18, 35, 40, 14, 12, 12, 14]
for col, ancho in enumerate(anchos, start=1):
    # get_column_letter convierte 1→'A', 2→'B', etc.
    ws1.column_dimensions[get_column_letter(col)].width = ancho

# Congelamos la fila de encabezados para que siempre sea visible al hacer scroll
ws1.freeze_panes = 'A6'  # Todo lo que esté encima de A6 queda fijo

# ════════════════════════════════════════════════════════════════════════════
#  HOJA 2: RESUMEN POR ESTADO
# ════════════════════════════════════════════════════════════════════════════
ws2 = wb.create_sheet(title='Resumen')

ws2['A1'] = 'Resumen del Inventario'
ws2['A1'].font      = Font(name='Arial', size=14, bold=True, color='FFFFFF')
ws2['A1'].fill      = relleno(COLOR_AZUL_OSCURO)
ws2['A1'].alignment = Alignment(horizontal='center')
ws2.merge_cells('A1:C1')
ws2.row_dimensions[1].height = 30

# Calculamos los totales para el resumen
total_normal = sum(1 for p in productos if p.get('stock_total',0) > p.get('stock_minimo',0))
total_bajo   = sum(1 for p in productos if 0 < p.get('stock_total',0) <= p.get('stock_minimo',0))
total_sin    = sum(1 for p in productos if p.get('stock_total',0) == 0)
total_general= len(productos)
unidades_tot = sum(p.get('stock_total',0) for p in productos)

# Datos del resumen
resumen = [
    ('Categoría', 'Cantidad de Productos', 'Color'),
    ('Stock Normal',    total_normal,   COLOR_VERDE),
    ('Stock Bajo',      total_bajo,     COLOR_NARANJA),
    ('Sin Stock',       total_sin,      COLOR_ROJO),
    ('TOTAL GENERAL',   total_general,  COLOR_AZUL_CLARO),
    ('Total Unidades',  unidades_tot,   COLOR_AZUL_CLARO),
]

for i, (cat, val, color) in enumerate(resumen, start=2):
    ws2.cell(row=i, column=1).value     = cat
    ws2.cell(row=i, column=2).value     = val
    ws2.cell(row=i, column=1).font      = Font(name='Arial', size=10,
                                               bold=(i in [2,6,7]))
    ws2.cell(row=i, column=2).font      = Font(name='Arial', size=10,
                                               bold=True)
    ws2.cell(row=i, column=2).alignment = Alignment(horizontal='center')
    if i > 2:  # No colorear la fila de encabezado
        ws2.cell(row=i, column=1).fill = relleno(color)
        ws2.cell(row=i, column=2).fill = relleno(color)
    ws2.cell(row=i, column=1).border = borde_simple()
    ws2.cell(row=i, column=2).border = borde_simple()

ws2.column_dimensions['A'].width = 25
ws2.column_dimensions['B'].width = 22

# ── GUARDAR ARCHIVO ───────────────────────────────────────────────────────────
try:
    wb.save(archivo_excel)
    print("OK")
except Exception as e:
    print(f"Error al guardar Excel: {e}")
    sys.exit(1)