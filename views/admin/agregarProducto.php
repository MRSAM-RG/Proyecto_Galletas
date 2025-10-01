<?php
require_once '../../config/security.php';
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
  header('Location: ../index.php'); exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Agregar Producto</title>
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

<div class="login-container" style="max-width:480px;">
  <h1 style="color:#a14a7f;font-size:2.3rem;margin-bottom:1.2rem;">Agregar Producto</h1>

  <form action="../../controllers/agregarProducto.php" method="POST" enctype="multipart/form-data" id="productoForm" style="text-align:left;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

    <div class="form-group">
      <label for="nombre">Nombre del Producto</label>
      <input type="text" id="nombre" name="nombre" required placeholder="Ej: Galleta de chocolate">
    </div>

    <div class="form-group">
      <label for="descripcion">Descripción</label>
      <textarea id="descripcion" name="descripcion" required placeholder="Describe el producto..." style="resize:vertical;"></textarea>
    </div>

    <div class="form-group">
      <h3>Precios por Presentación</h3>
      <div class="form-group">
        <label>Precio Paquete de 6</label>
        <input type="number" name="precios[paquete6]" min="1" step="0.01" required>
      </div>
      <div class="form-group">
        <label>Precio Paquete de 9</label>
        <input type="number" name="precios[paquete9]" min="1" step="0.01" required>
      </div>
      <div class="form-group">
        <label>Precio Paquete de 12</label>
        <input type="number" name="precios[paquete12]" min="1" step="0.01" required>
      </div>
      <small style="color:#666;">El <b>Paquete Mixto</b> mantiene precio global (se edita en la lista de productos si lo manejas así).</small>
    </div>

    <div class="form-group">
      <label for="imagen">Imagen</label>
      <input type="file" id="imagen" name="imagen" accept="image/*" required onchange="previewImg(event)">
      <div id="preview" style="margin-top:10px;"></div>
      <small style="color:#a14a7f;">JPG/PNG/WebP máx 5MB. Recomendado: 400x400px.</small>
    </div>

    <button type="submit" class="btn btn-agregar" style="width:100%;margin-top:18px;">Agregar Producto</button>
  </form>
</div>

<script>
function previewImg(e){const f=e.target.files[0],p=document.getElementById('preview');p.innerHTML='';if(f&&f.type.match('image.*')){const r=new FileReader();r.onload=ev=>p.innerHTML='<img src="'+ev.target.result+'" style="max-width:120px;max-height:120px;border-radius:10px;border:2px solid #ff92b2;box-shadow:0 2px 8px #ffd1e0;">';r.readAsDataURL(f);}}
document.getElementById('hamburger-btn').addEventListener('click',()=>document.querySelector('.nav-links').classList.toggle('open'));
function updateCartCount(){const el=document.getElementById('cart-count'); if(!el)return; fetch('../carrito.php?count=1').then(r=>r.json()).then(d=>{el.textContent=d.count>0?'('+d.count+')':'';});}
updateCartCount();

document.getElementById('productoForm').addEventListener('submit',function(e){
  e.preventDefault();
  const nombre = document.getElementById('nombre').value.trim();
  const descripcion = document.getElementById('descripcion').value.trim();
  const imagen = document.getElementById('imagen').files[0];
  if (nombre.length<3) return Swal.fire({title:'Error',text:'El nombre debe tener al menos 3 caracteres',icon:'error',confirmButtonColor:'#a14a7f'});
  if (descripcion.length<10) return Swal.fire({title:'Error',text:'La descripción debe tener al menos 10 caracteres',icon:'error',confirmButtonColor:'#a14a7f'});
  if (!imagen) return Swal.fire({title:'Error',text:'Debes seleccionar una imagen',icon:'error',confirmButtonColor:'#a14a7f'});
  const ok=['image/jpeg','image/png','image/webp']; if(!ok.includes(imagen.type)) return Swal.fire({title:'Error',text:'Solo JPG, PNG o WebP',icon:'error',confirmButtonColor:'#a14a7f'});
  if (imagen.size>5*1024*1024) return Swal.fire({title:'Error',text:'La imagen no debe superar 5MB',icon:'error',confirmButtonColor:'#a14a7f'});
  this.submit();
});
<?php if (isset($_GET['error'])): ?>
Swal.fire({title:'Error', text:'<?php echo htmlspecialchars($_GET['error']); ?>', icon:'error', confirmButtonColor:'#a14a7f'});
<?php endif; ?>
</script>
</body>
</html>
