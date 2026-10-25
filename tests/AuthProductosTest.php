<?php
/** Módulos Autenticación y Productos. */
class AuthProductosTest extends BaseTest
{
    private const LOGIN = 'modulos/auth/procesar_login.php';
    private const PROD  = 'modulos/productos/procesar.php';

    public function test_TC13_login_con_credenciales_validas_redirige_al_dashboard(): void
    {
        $r = $this->request(self::LOGIN, 'POST', ['email'=>'admin@t.co','password'=>'Clave123']);
        $this->assertSame('../dashboard.php', $r['location']);
    }

    public function test_TC14_login_con_clave_incorrecta_es_rechazado(): void
    {
        $r = $this->request(self::LOGIN, 'POST', ['email'=>'admin@t.co','password'=>'mala']);
        $this->assertSame('login.php?error=credenciales', $r['location']);
    }

    public function test_TC15_login_de_usuario_inactivo_es_rechazado(): void
    {
        $r = $this->request(self::LOGIN, 'POST', ['email'=>'off@t.co','password'=>'Clave123']);
        $this->assertSame('login.php?error=credenciales', $r['location']);
    }

    public function test_TC16_login_con_intento_de_inyeccion_sql_es_rechazado(): void
    {
        $r = $this->request(self::LOGIN, 'POST', ['email'=>"' OR '1'='1' -- ",'password'=>'x']);
        $this->assertSame('login.php?error=credenciales', $r['location']);
    }

    public function test_TC17_admin_crea_producto_con_stock_inicial(): void
    {
        $r = $this->request(self::PROD, 'POST', ['accion'=>'crear','codigo_barras'=>'7700002','nombre'=>'Teclado','descripcion'=>'USB','precio'=>'80000','stock_minimo'=>'3','bodega_id'=>[1,2],'stock'=>[10,5]], $this->sesion(1));
        $this->assertSame('lista.php?exito=creado', $r['location']);
        $this->assertSame(2, $this->contar('productos'));
        $this->assertSame(15, (int)self::$db->query("SELECT SUM(cantidad) s FROM stock_bodega WHERE id_producto=2")->fetch_assoc()['s']);
    }

    public function test_TC18_codigo_de_barras_duplicado_es_rechazado(): void
    {
        $r = $this->request(self::PROD, 'POST', ['accion'=>'crear','codigo_barras'=>'7700001','nombre'=>'Otro','descripcion'=>'','precio'=>'1','stock_minimo'=>'0','bodega_id'=>[1],'stock'=>[1]], $this->sesion(1));
        $this->assertSame('crear.php?error=codigo_duplicado', $r['location']);
        $this->assertSame(1, $this->contar('productos'));
    }

    public function test_TC19_usuario_no_administrador_no_puede_crear_productos(): void
    {
        $r = $this->request(self::PROD, 'POST', ['accion'=>'crear','codigo_barras'=>'7700003','nombre'=>'X','descripcion'=>'','precio'=>'1','stock_minimo'=>'0','bodega_id'=>[1],'stock'=>[1]], $this->sesion(2));
        $this->assertSame('lista.php', $r['location']);
        $this->assertSame(1, $this->contar('productos'));
    }
}
