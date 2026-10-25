<?php
/** Módulo Movimientos: entrada, salida y traslado (RF de inventario). */
class MovimientosTest extends BaseTest
{
    private const SCRIPT = 'modulos/movimientos/procesar_movimiento.php';

    public function test_TC01_entrada_crea_stock_en_bodega_sin_registro_previo(): void
    {
        $r = $this->request(self::SCRIPT, 'POST', ['tipo'=>'entrada','id_producto'=>1,'cantidad'=>10,'id_bodega_destino'=>1], $this->sesion(1));
        $this->assertSame('entrada.php?exito=1', $r['location']);
        $this->assertSame(10, $this->stock(1, 1));
        $this->assertSame(1, $this->contar('movimientos'));
    }

    public function test_TC02_entrada_suma_a_stock_existente(): void
    {
        $this->setStock(1, 1, 7);
        $this->request(self::SCRIPT, 'POST', ['tipo'=>'entrada','id_producto'=>1,'cantidad'=>5,'id_bodega_destino'=>1], $this->sesion(1));
        $this->assertSame(12, $this->stock(1, 1));
    }

    public function test_TC03_salida_con_stock_suficiente_descuenta(): void
    {
        $this->setStock(1, 1, 10);
        $r = $this->request(self::SCRIPT, 'POST', ['tipo'=>'salida','id_producto'=>1,'cantidad'=>4,'id_bodega_origen'=>1], $this->sesion(2));
        $this->assertSame('salida.php?exito=1', $r['location']);
        $this->assertSame(6, $this->stock(1, 1));
    }

    public function test_TC04_salida_con_stock_insuficiente_se_rechaza_sin_alterar_datos(): void
    {
        $this->setStock(1, 1, 3);
        $r = $this->request(self::SCRIPT, 'POST', ['tipo'=>'salida','id_producto'=>1,'cantidad'=>4,'id_bodega_origen'=>1], $this->sesion(2));
        $this->assertSame('salida.php?error=stock_insuficiente', $r['location']);
        $this->assertSame(3, $this->stock(1, 1));
        $this->assertSame(0, $this->contar('movimientos'));
    }

    public function test_TC05_salida_por_el_total_del_stock_deja_cero_no_negativo(): void
    {
        $this->setStock(1, 1, 5);
        $this->request(self::SCRIPT, 'POST', ['tipo'=>'salida','id_producto'=>1,'cantidad'=>5,'id_bodega_origen'=>1], $this->sesion(2));
        $this->assertSame(0, $this->stock(1, 1));
    }

    public function test_TC06_salida_en_bodega_sin_registro_de_stock_se_rechaza(): void
    {
        $r = $this->request(self::SCRIPT, 'POST', ['tipo'=>'salida','id_producto'=>1,'cantidad'=>1,'id_bodega_origen'=>2], $this->sesion(2));
        $this->assertSame('salida.php?error=stock_insuficiente', $r['location']);
    }

    public function test_TC07_traslado_mueve_stock_entre_bodegas_conservando_el_total(): void
    {
        $this->setStock(1, 1, 10);
        $r = $this->request(self::SCRIPT, 'POST', ['tipo'=>'traslado','id_producto'=>1,'cantidad'=>4,'id_bodega_origen'=>1,'id_bodega_destino'=>2], $this->sesion(3));
        $this->assertSame('traslado.php?exito=1', $r['location']);
        $this->assertSame(6, $this->stock(1, 1));
        $this->assertSame(4, $this->stock(1, 2));
        $this->assertSame(1, $this->contar('movimientos'));
    }

    public function test_TC08_traslado_a_la_misma_bodega_se_rechaza(): void
    {
        $this->setStock(1, 1, 10);
        $r = $this->request(self::SCRIPT, 'POST', ['tipo'=>'traslado','id_producto'=>1,'cantidad'=>2,'id_bodega_origen'=>1,'id_bodega_destino'=>1], $this->sesion(3));
        $this->assertSame('traslado.php?error=misma_bodega', $r['location']);
        $this->assertSame(10, $this->stock(1, 1));
    }

    public function test_TC09_traslado_con_stock_insuficiente_no_modifica_ninguna_bodega(): void
    {
        $this->setStock(1, 1, 2);
        $r = $this->request(self::SCRIPT, 'POST', ['tipo'=>'traslado','id_producto'=>1,'cantidad'=>5,'id_bodega_origen'=>1,'id_bodega_destino'=>2], $this->sesion(3));
        $this->assertSame('traslado.php?error=stock_insuficiente', $r['location']);
        $this->assertSame(2, $this->stock(1, 1));
        $this->assertNull($this->stock(1, 2));
    }

    /** @dataProvider cantidadesInvalidas */
    public function test_TC10_cantidad_cero_o_negativa_se_rechaza($cantidad): void
    {
        $r = $this->request(self::SCRIPT, 'POST', ['tipo'=>'entrada','id_producto'=>1,'cantidad'=>$cantidad,'id_bodega_destino'=>1], $this->sesion(1));
        $this->assertSame('entrada.php?error=campos_vacios', $r['location']);
        $this->assertSame(0, $this->contar('movimientos'));
    }
    public static function cantidadesInvalidas(): array { return [['0'], ['-5'], ['abc']]; }

    public function test_TC11_sin_sesion_redirige_al_login(): void
    {
        $r = $this->request(self::SCRIPT, 'POST', ['tipo'=>'entrada','id_producto'=>1,'cantidad'=>5,'id_bodega_destino'=>1], null);
        $this->assertSame('../auth/login.php', $r['location']);
        $this->assertSame(0, $this->contar('movimientos'));
    }

    public function test_TC12_acceso_por_GET_redirige_al_dashboard(): void
    {
        $r = $this->request(self::SCRIPT, 'GET', [], $this->sesion(3));
        $this->assertSame('../dashboard.php', $r['location']);
    }

    /** @group hallazgos */
    public function test_H01_tipo_de_movimiento_invalido_debe_rechazarse_con_redireccion(): void
    {
        $r = $this->request(self::SCRIPT, 'POST', ['tipo'=>'ajuste','id_producto'=>1,'cantidad'=>5], $this->sesion(3));
        $this->assertNotNull($r['location'], 'El sistema no responde con redirección ni error ante un tipo desconocido (pantalla en blanco).');
    }

    /** @group hallazgos */
    public function test_H02_rol_vendedor_no_deberia_poder_registrar_entradas(): void
    {
        $this->request(self::SCRIPT, 'POST', ['tipo'=>'entrada','id_producto'=>1,'cantidad'=>5,'id_bodega_destino'=>1], $this->sesion(2));
        $this->assertNull($this->stock(1, 1), 'Un Vendedor logró registrar una entrada: el endpoint no valida el rol.');
    }
}
