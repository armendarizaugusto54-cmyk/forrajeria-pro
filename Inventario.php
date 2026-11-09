<?php
declare(strict_types=1);
final class Inventario {
  public function __construct(private PDO $db) { $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); }
  public function instalar(): void {
    $this->db->exec('CREATE TABLE productos (id INTEGER PRIMARY KEY, nombre TEXT NOT NULL, precio_centavos INTEGER NOT NULL CHECK(precio_centavos>=0), stock INTEGER NOT NULL CHECK(stock>=0))');
    $this->db->exec('CREATE TABLE ventas (id INTEGER PRIMARY KEY, producto_id INTEGER NOT NULL REFERENCES productos(id), cantidad INTEGER NOT NULL CHECK(cantidad>0), total_centavos INTEGER NOT NULL)');
  }
  public function agregar(string $nombre,int $precio,int $stock): int {
    if(trim($nombre)==='' || $precio<0 || $stock<0) throw new InvalidArgumentException('Datos inválidos');
    $q=$this->db->prepare('INSERT INTO productos(nombre,precio_centavos,stock) VALUES(?,?,?)');
    $q->execute([trim($nombre),$precio,$stock]); return (int)$this->db->lastInsertId();
  }
  public function buscar(string $texto): array {
    $q=$this->db->prepare('SELECT * FROM productos WHERE nombre LIKE ? ORDER BY nombre LIMIT 25');
    $q->execute(['%'.$texto.'%']); return $q->fetchAll(PDO::FETCH_ASSOC);
  }
  public function vender(int $id,int $cantidad): int {
    if($id<=0 || $cantidad<=0) throw new InvalidArgumentException('Cantidad inválida');
    $this->db->beginTransaction();
    try {
      $q=$this->db->prepare('SELECT precio_centavos FROM productos WHERE id=?'); $q->execute([$id]);
      $precio=$q->fetchColumn(); if($precio===false) throw new RuntimeException('Producto inexistente');
      $q=$this->db->prepare('UPDATE productos SET stock=stock-? WHERE id=? AND stock>=?');
      $q->execute([$cantidad,$id,$cantidad]); if($q->rowCount()!==1) throw new RuntimeException('Stock insuficiente');
      $total=(int)$precio*$cantidad;
      $q=$this->db->prepare('INSERT INTO ventas(producto_id,cantidad,total_centavos) VALUES(?,?,?)');
      $q->execute([$id,$cantidad,$total]); $this->db->commit(); return $total;
    } catch(Throwable $e) { $this->db->rollBack(); throw $e; }
  }
}
