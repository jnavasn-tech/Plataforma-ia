<?php

/*
| Catálogo de áreas de competencia (línea base del Capítulo V).
| La correspondencia con DigComp 2.2 es una propuesta del autor sujeta a validación por expertos
| (hallazgo H-10: las cinco áreas operativas no coinciden literalmente con las áreas de DigComp).
*/
return [
    ['codigo' => 'OFI', 'nombre' => 'Ofimática', 'orden' => 1,
        'descripcion' => 'Procesador de texto, hojas de cálculo y presentaciones aplicados al trabajo de oficina.',
        'referencia_digcomp' => 'DigComp 2.2, área 3: Creación de contenidos digitales'],
    ['codigo' => 'RED', 'nombre' => 'Redes e Internet', 'orden' => 2,
        'descripcion' => 'Uso del navegador, conceptos básicos de redes, Wi-Fi y solución de problemas de conexión.',
        'referencia_digcomp' => 'DigComp 2.2, área 5: Resolución de problemas (5.1 problemas técnicos)'],
    ['codigo' => 'SEG', 'nombre' => 'Ciberseguridad', 'orden' => 3,
        'descripcion' => 'Contraseñas seguras, phishing, malware, navegación segura y protección de datos personales.',
        'referencia_digcomp' => 'DigComp 2.2, área 4: Seguridad'],
    ['codigo' => 'COL', 'nombre' => 'Colaboración digital', 'orden' => 4,
        'descripcion' => 'Correo electrónico profesional, documentos compartidos, videoconferencias y netiqueta.',
        'referencia_digcomp' => 'DigComp 2.2, área 2: Comunicación y colaboración'],
    ['codigo' => 'PRO', 'nombre' => 'Productividad digital', 'orden' => 5,
        'descripcion' => 'Gestión de archivos, almacenamiento en la nube, búsqueda de información y organización del trabajo.',
        'referencia_digcomp' => 'DigComp 2.2, área 1: Búsqueda y gestión de información y datos'],
];
