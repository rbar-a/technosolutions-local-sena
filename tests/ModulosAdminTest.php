<?php
/** Usuarios, bodegas, edición/baja de productos, búsqueda, historial y reportes. */
class ModulosAdminTest extends BaseTest
{
    private function usuario(string $email): ?array
    {
        return self::$db->query("SELECT * FROM usuarios WHERE email='$email'")->fetch_assoc() ?: null;
    }

    private function nuevoUsuario(array $extra = []): array
    {
        return array_merge(['accion'=>'crear','nombre'=>'Nuevo','email'=>'nuevo@t.co','id_rol'=>2,'password'=>'secreto1','password_confirm'=>'secreto1'], $extra);
    }

    public function test_TC20_admin_crea_usuario_con_clave_cifrada(): void
    {
        $r = $this->request('modulos/usuarios/procesar_usuario.php', 'POST', $this->nuevoUsuario(), $this->sesion(1));
        $this->assertSame('lista.php?exito=creado', $r['location']);
        $u = $this->usuario('nuevo@t.co');
        $this->assertNotNull($u);
        $this->assertNotSame('secreto1', $u['password']);
        $this->assertTrue(password_verify('secreto1', $u['password']));
    }

    public function test_TC21_clave_menor_a_6_caracteres_es_rechazada(): void
    {
        $r = $this->request('modulos/usuarios/procesar_usuario.php', 'POST', $this->nuevoUsuario(['password'=>'abc','password_confirm'=>'abc']), $this->sesion(1));
        $this->assertSame('crear.php?error=password_corta', $r['location']);
        $this->assertNull($this->usuario('nuevo@t.co'));
    }

    public function test_TC22_correo_duplicado_es_rechazado(): void
    {
        $r = $this->request('modulos/usuarios/procesar_usuario.php', 'POST', $this->nuevoUsuario(['email'=>'admin@t.co']), $this->sesion(1));
        $this->assertSame('crear.php?error=email_duplicado', $r['location']);
        $this->assertSame(4, $this->contar('usuarios'));
    }

    public function test_TC23_confirmacion_de_clave_distinta_es_rechazada(): void
    {
        $r = $this->request('modulos/usuarios/procesar_usuario.php', 'POST', $this->nuevoUsuario(['password_confirm'=>'otra123']), $this->sesion(1));
        $this->assertSame('crear.php?error=campos_vacios', $r['location']);
        $this->assertNull($this->usuario('nuevo@t.co'));
    }

    public function test_TC24_no_administrador_no_puede_crear_usuarios(): void
    {
        $r = $this->request('modulos/usuarios/procesar_usuario.php', 'POST', $this->nuevoUsuario(), $this->sesion(2));
        $this->assertSame('../dashboard.php', $r['location']);
        $this->assertNull($this->usuario('nuevo@t.co'));
    }

    public function test_TC25_admin_desactiva_a_otro_usuario(): void
    {
        $this->request('modulos/usuarios/toggle_estado.php?id=2', 'GET', [], $this->sesion(1));
        $this->assertSame('0', $this->usuario('vende@t.co')['activo']);
    }

    public function test_TC26_admin_no_puede_desactivarse_a_si_mismo(): void
    {
        $this->request('modulos/usuarios/toggle_estado.php?id=1', 'GET', [], $this->sesion(1));
        $this->assertSame('1', $this->usuario('admin@t.co')['activo']);
    }

    public function test_TC27_admin_crea_bodega(): void
    {
        $r = $this->request('modulos/bodegas/procesar_bodega.php', 'POST', ['accion'=>'crear','nombre'=>'Sur','direccion'=>'Calle 1'], $this->sesion(1));
        $this->assertSame('lista.php?exito=creada', $r['location']);
        $this->assertSame(3, $this->contar('bodegas'));
    }

    public function test_TC28_admin_desactiva_bodega_editandola(): void
    {
        $this->request('modulos/bodegas/procesar_bodega.php', 'POST', ['accion'=>'editar','id'=>2,'nombre'=>'Norte','direccion'=>'','activa'=>0], $this->sesion(1));
        $this->assertSame('0', self::$db->query('SELECT activa FROM bodegas WHERE id=2')->fetch_assoc()['activa']);
    }

    public function test_TC29_bodega_sin_nombre_es_rechazada(): void
    {
        $r = $this->request('modulos/bodegas/procesar_bodega.php', 'POST', ['accion'=>'crear','nombre'=>'  ','direccion'=>'x'], $this->sesion(1));
        $this->assertSame('crear.php?error=campos_vacios', $r['location']);
        $this->assertSame(2, $this->contar('bodegas'));
    }

    public function test_TC30_eliminar_producto_es_baja_logica_y_conserva_la_fila(): void
    {
        $r = $this->request('modulos/productos/eliminar.php?id=1', 'GET', [], $this->sesion(1));
        $this->assertSame('lista.php?exito=eliminado', $r['location']);
        $this->assertSame('0', self::$db->query('SELECT activo FROM productos WHERE id=1')->fetch_assoc()['activo']);
        $this->assertSame(1, $this->contar('productos'));
    }

    public function test_TC31_no_administrador_no_puede_eliminar_productos(): void
    {
        $this->request('modulos/productos/eliminar.php?id=1', 'GET', [], $this->sesion(2));
        $this->assertSame('1', self::$db->query('SELECT activo FROM productos WHERE id=1')->fetch_assoc()['activo']);
    }

    public function test_TC32_editar_producto_actualiza_el_precio(): void
    {
        $r = $this->request('modulos/productos/procesar.php', 'POST', ['accion'=>'editar','id'=>1,'codigo_barras'=>'7700001','nombre'=>'Mouse','descripcion'=>'','precio'=>'65000','stock_minimo'=>'5','bodega_id'=>[],'stock'=>[]], $this->sesion(1));
        $this->assertSame('lista.php?exito=editado', $r['location']);
        $this->assertSame(65000.0, (float)self::$db->query('SELECT precio FROM productos WHERE id=1')->fetch_assoc()['precio']);
    }

    public function test_TC33_busqueda_por_codigo_devuelve_producto_y_stock_total(): void
    {
        $this->setStock(1, 1, 4); $this->setStock(1, 2, 6);
        $r = $this->request('modulos/movimientos/buscar_producto.php?codigo=7700001', 'GET', [], $this->sesion(3));
        $j = json_decode($r['body'], true);
        $this->assertTrue($j['encontrado']);
        $stock = $j['producto']['stock_total'] ?? $j['stock_total'];
        $this->assertSame(10, (int)$stock);
    }

    public function test_TC34_busqueda_de_producto_desactivado_no_lo_encuentra(): void
    {
        self::$db->query('UPDATE productos SET activo=0 WHERE id=1');
        $r = $this->request('modulos/movimientos/buscar_producto.php?codigo=7700001', 'GET', [], $this->sesion(3));
        $this->assertFalse(json_decode($r['body'], true)['encontrado']);
    }

    public function test_TC35_busqueda_sin_sesion_no_expone_datos(): void
    {
        $r = $this->request('modulos/movimientos/buscar_producto.php?codigo=7700001', 'GET', [], null);
        $this->assertFalse(json_decode($r['body'], true)['encontrado']);
    }

    public function test_TC36_historial_muestra_el_movimiento_registrado(): void
    {
        $this->request('modulos/movimientos/procesar_movimiento.php', 'POST', ['tipo'=>'entrada','id_producto'=>1,'cantidad'=>9,'id_bodega_destino'=>1,'observacion'=>'lote-xyz'], $this->sesion(1));
        $r = $this->request('modulos/movimientos/historial.php', 'GET', [], $this->sesion(3));
        $this->assertStringContainsString('Mouse', $r['body']);
        $this->assertStringContainsString('lote-xyz', $r['body']);
    }

    public function test_TC37_reporte_pdf_se_genera_con_sesion_activa(): void
    {
        $this->setStock(1, 1, 3);
        $r = $this->request('modulos/reportes/inventario_pdf.php', 'GET', [], $this->sesion(1));
        $this->assertStringStartsWith('%PDF', ltrim($r['body']));
    }

    public function test_TC38_reporte_excel_se_genera_con_sesion_activa(): void
    {
        $this->setStock(1, 1, 3);
        $r = $this->request('modulos/reportes/inventario_excel.php', 'GET', [], $this->sesion(1));
        $this->assertStringStartsWith('PK', ltrim($r['body']));
    }

    public function test_TC39_reportes_sin_sesion_redirigen_al_login(): void
    {
        $r = $this->request('modulos/reportes/inventario_pdf.php', 'GET', [], null);
        $this->assertSame('../auth/login.php', $r['location']);
    }

    public function test_TC40_dashboard_lista_en_alertas_el_producto_con_stock_bajo(): void
    {
        $this->setStock(1, 1, 2); // stock_minimo del producto = 5
        $r = $this->request('modulos/dashboard.php', 'GET', [], $this->sesion(1));
        $this->assertStringContainsString('Mouse', $r['body']);
    }
}
