<?php
require_once '../../models/MySQL.php';
require_once '../../models/QueryManager.php';
require_once '../../config/security.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { header('Location: ../index.php'); exit(); }
if (!isset($_GET['id'])) { header('Location: admin.php?error=ID de producto no especificado'); exit(); }

$id = (int)$_GET['id'];

$db = new MySQL(); $db->conectar();
$qm = new QueryManager($db);
$producto = $qm->getProductById($id);
if (!$producto) { $db->desconectar(); header('Location: admin.php?error=Producto no encontrado'); exit(); }

/* precios actuales (solo 6/9/12) */
$preciosMap = ['paquete6'=>'','paquete9'=>'','paquete12'=>''];
$q = $db->conexion->prepare("SELECT presentacion, precio FROM precios_productos WHERE producto_id=? AND presentacion IN ('paquete6','paquete9','paquete12')");
$q->bind_param("i",$id); $q->execute();
$r = $q->get_result();
while($x=$r->fetch_assoc()){ $preciosMap[$x['presentacion']] = $x['precio']; }
$db->desconectar();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Editar Producto</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/pedidos.css">
  <script src="../../assets/js/sweetalert2.all.min.js"></script>
</head>
<body>
<nav class="navbar">
  <div class="logo"><img src="../../assets/img/Logo.png" alt="Logo Empresa"><a href="../index.php"><span style="color:#ff92b2;font-size:1.5rem;font-weight:bold;">Dulce Tentación</span></a></div>
  <button class="hamburger" id="hamburger-btn" aria-label="Abrir menú"><span></span><span></span><span></span></button>
  <ul class="nav-links">
    <li><a href="admin.php">Admin</a></li>
    <li><a href="../../controllers/logout.php">Cerrar Sesión</a></li>
  </ul>
</nav>

<div class="login-container">
  <h1 style="color:#a14a7f;font-size:2.3rem;margin-bottom:1.2rem;">Editar Producto</h1>

  <form action="../../controllers/editarProducto.php" method="POST" enctype="multipart/form-data" id="editarForm">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
    <input type="hidden" name="id" value="<?php echo $id; ?>">

    <div class="form-group">
      <label for="nombre">Nombre:</label>
      <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
    </div>

    <div class="form-group">
      <label for="descripcion">Descripción:</label>
      <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
    </div>

    <div class="form-group">
      <h3 style="color:#a14a7f;">Precios por Presentación</h3>
      <div class="form-group"><label>Precio Paquete de 6</label>
        <input type="number" name="precios[paquete6]" min="1" step="0.01" required value="<?php echo htmlspecialchars($preciosMap['paquete6']); ?>">
      </div>
      <div class="form-group"><label>Precio Paquete de 9</label>
        <input type="number" name="precios[paquete9]" min="1" step="0.01" required value="<?php echo htmlspecialchars($preciosMap['paquete9']); ?>">
      </div>
      <div class="form-group"><label>Precio Paquete de 12</label>
        <input type="number" name="precios[paquete12]" min="1" step="0.01" required value="<?php echo htmlspecialchars($preciosMap['paquete12']); ?>">
      </div>
      <small style="color:#666;">El <b>Paquete Mixto</b> sigue siendo precio global (no se edita aquí).</small>
    </div>

    <div class="form-group">
      <label>Imagen actual:</label>
      <img src="../../assets/img/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="Imagen actual" style="max-width:200px;margin:10px 0;">
      <label for="imagen">Nueva imagen (opcional):</label>
      <input type="file" id="imagen" name="imagen" accept="image/*">
      <small style="color:#a14a7f;">JPG/PNG/WebP máx 5MB. Recomendado: 400x400px.</small>
    </div>

    <button type="submit" class="btn btn-agregar" style="width:100%;margin-top:18px;">Guardar Cambios</button>
  </form>
</div>

<script>
document.getElementById('editarForm').addEventListener('submit',function(e){
  e.preventDefault();
  const nombre=document.getElementById('nombre').value.trim();
  const descripcion=document.getElementById('descripcion').value.trim();
  const imagen=document.getElementById('imagen').files[0];
  if (nombre.length<3) return Swal.fire({title:'Error',text:'El nombre debe tener al menos 3 caracteres',icon:'error',confirmButtonColor:'#a14a7f'});
  if (descripcion.length<10) return Swal.fire({title:'Error',text:'La descripción debe tener al menos 10 caracteres',icon:'error',confirmButtonColor:'#a14a7f'});
  if (imagen){
    const ok=['image/jpeg','image/png','image/webp'];
    if(!ok.includes(imagen.type)) return Swal.fire({title:'Error',text:'Solo JPG, PNG o WebP',icon:'error',confirmButtonColor:'#a14a7f'});
    if (imagen.size>5*1024*1024) return Swal.fire({title:'Error',text:'La imagen no debe superar 5MB',icon:'error',confirmButtonColor:'#a14a7f'});
  }
  Swal.fire({title:'¿Guardar cambios?',icon:'question',showCancelButton:true,confirmButtonColor:'#a14a7f',cancelButtonColor:'#6c757d',confirmButtonText:'Sí, guardar',cancelButtonText:'Cancelar'})
    .then(r=>{ if(r.isConfirmed) this.submit(); });
});
document.getElementById('hamburger-btn').addEventListener('click',()=>document.querySelector('.nav-links').classList.toggle('open'));
function updateCartCount(){const el=document.getElementById('cart-count'); if(!el)return; fetch('../carrito.php?count=1').then(r=>r.json()).then(d=>{el.textContent=d.count>0?'('+d.count+')':'';});}
updateCartCount();
<?php if (isset($_GET['error'])): ?>
Swal.fire({title:'Error', text:'<?php echo htmlspecialchars($_GET['error']); ?>', icon:'error', confirmButtonColor:'#a14a7f'});
<?php endif; ?>
</script>
</body>
</html>
