/root (Tu servidor)
├── super_admin/            <-- Nuevo: Panel Superior
│   ├── index.php           <-- Dashboard Global (Métricas SEO/C-Level)
│   ├── instituciones.php   <-- Gestión de Clientes/Municipios
│   ├── login.php           <-- Acceso robusto
│   ├── rescue.php          <-- Reparador de acceso (emergencia)
│   └── css/                <-- Estética moderna (Glassmorphism/Dark Mode)
├── master_api/             <-- Lógica global
│   ├── super_db.php        <-- Gestión de la "Base de Datos" de instituciones
│   └── auth.php            <-- Seguridad (JWT o Sessions seguras)
├── instituciones/          <-- Aquí viven las instancias
│   ├── pitrufquen/         <-- (Tu proyecto actual se mueve aquí)
│   ├── tolten/             <-- Nueva institución
│   └── villarrica/         <-- Nueva institución
└── global_data/            <-- JSON central con la lista de instituciones