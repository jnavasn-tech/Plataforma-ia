<?php

/*
| Banco de preguntas de DEMOSTRACIÓN para el diagnóstico: 12 por área (4 por nivel de dificultad).
| [PENDIENTE: validación de contenido por el panel de expertos y ampliación a 18 ítems por área.]
| Formato: código de área => [dificultad => [[enunciado, [opción correcta, distractor, distractor, distractor]], ...]]
| El seeder rota el orden de las opciones para que la respuesta correcta no ocupe siempre la misma posición.
*/
return [
    'OFI' => [
        1 => [
            ['¿Qué opción permite cambiar la orientación de la página de vertical a horizontal en un procesador de texto?', ['Diseño (o Formato) de página > Orientación', 'Insertar > Imagen', 'Revisar > Ortografía', 'Vista > Zoom']],
            ['En una hoja de cálculo, ¿cómo se identifica la celda ubicada en la columna B y la fila 3?', ['B3', '3B', 'C2', 'B-3']],
            ['¿Qué herramienta de un procesador de texto ayuda a detectar errores de escritura?', ['Revisión ortográfica y gramatical', 'Combinar correspondencia', 'Saltos de sección', 'Tabla de contenido']],
            ['¿Qué se crea normalmente con un programa de presentaciones?', ['Un conjunto de diapositivas', 'Una hoja de cálculo con filas y columnas', 'Una base de datos relacional', 'Un archivo de audio']],
        ],
        2 => [
            ['En una hoja de cálculo, ¿qué fórmula suma los valores de las celdas A1 a A10?', ['=SUMA(A1:A10)', '=A1+A10', '=CONTAR(A1:A10)', '=PROMEDIO(A1:A10)']],
            ['¿Para qué sirve la función PROMEDIO en una hoja de cálculo?', ['Calcula la media aritmética de un rango', 'Cuenta las celdas vacías', 'Devuelve el valor máximo', 'Ordena los datos']],
            ['En un procesador de texto, ¿qué función genera automáticamente un índice a partir de los títulos?', ['Tabla de contenido basada en estilos de título', 'Insertar hipervínculo', 'Notas al pie', 'Columnas']],
            ['Si copia la fórmula =A1*2 de la celda B1 a la celda B2, ¿qué fórmula resulta?', ['=A2*2', '=A1*2', '=B2*2', '=A1*3']],
        ],
        3 => [
            ['¿Qué referencia mantiene fija la celda C1 al copiar una fórmula a otras filas y columnas?', ['$C$1', 'C1', 'C$1$', '#C1']],
            ['¿Qué función devuelve un dato de una tabla buscando una coincidencia en su primera columna?', ['BUSCARV (o BUSCARX)', 'CONCATENAR', 'REDONDEAR', 'HOY']],
            ['Una tabla registra ventas por vendedor y por mes. ¿Qué herramienta las resume por vendedor sin escribir fórmulas?', ['Tabla dinámica', 'Combinar celdas', 'Inmovilizar paneles', 'Ajustar texto']],
            ['¿Qué resultado produce =SI(B2>=70,"Aprobado","Reprobado") cuando B2 vale 65?', ['Reprobado', 'Aprobado', '65', 'Error de fórmula']],
        ],
    ],
    'RED' => [
        1 => [
            ['¿Qué programa se utiliza para visitar sitios web?', ['Un navegador web, como Chrome, Edge o Firefox', 'Un procesador de texto', 'Un antivirus', 'Un reproductor de música']],
            ['¿Qué indica el candado junto a la dirección de un sitio web?', ['Que la conexión con el sitio está cifrada (HTTPS)', 'Que el sitio es oficial del gobierno', 'Que el sitio no tiene anuncios', 'Que la página está bloqueada']],
            ['¿Qué es una red Wi-Fi?', ['Una red que conecta dispositivos de forma inalámbrica', 'Un cable para cargar el teléfono', 'Un tipo de virus', 'Un programa de ofimática']],
            ['En la dirección www.ejemplo.com, ¿qué parte corresponde al dominio de nivel superior?', ['.com', 'www', 'ejemplo', 'www.ejemplo']],
        ],
        2 => [
            ['¿Qué dispositivo distribuye la conexión a Internet a los equipos de una casa u oficina?', ['El enrutador (router)', 'El monitor', 'La impresora', 'El teclado']],
            ['¿Qué significa que un dispositivo tenga una dirección IP?', ['Que tiene un identificador numérico dentro de la red', 'Que tiene antivirus instalado', 'Que está apagado', 'Que tiene licencia de software']],
            ['Si una página no carga pero otras sí, ¿cuál es la primera acción razonable?', ['Verificar la dirección escrita y volver a cargar la página', 'Formatear la computadora', 'Desinstalar el navegador', 'Cambiar la contraseña del correo']],
            ['¿Qué diferencia principal hay entre una red Wi-Fi pública y una red privada de confianza?', ['En la red pública comparten la conexión personas desconocidas y aumenta el riesgo', 'Las redes públicas siempre son más rápidas', 'Las redes privadas no usan Internet', 'No existe ninguna diferencia']],
        ],
        3 => [
            ['¿Cuál es la función del servicio DNS?', ['Traducir nombres de dominio a direcciones IP', 'Cifrar los archivos del disco', 'Asignar contraseñas a los usuarios', 'Acelerar el procesador']],
            ['Un equipo está conectado al Wi-Fi pero no navega, mientras otros equipos sí. ¿Qué verificación es más pertinente?', ['Revisar si obtuvo una dirección IP válida y reiniciar su adaptador de red', 'Cambiar el router de lugar', 'Actualizar el procesador de texto', 'Borrar el historial de documentos']],
            ['¿Qué comando permite comprobar si un equipo responde en la red?', ['ping', 'copy', 'format', 'print']],
            ['¿Qué ventaja ofrece una VPN al conectarse desde una red pública?', ['Cifra el tráfico entre el dispositivo y el servidor de la VPN', 'Aumenta el espacio en disco', 'Elimina la necesidad de contraseñas', 'Garantiza que ningún sitio tenga virus']],
        ],
    ],
    'SEG' => [
        1 => [
            ['¿Cuál de estas contraseñas es más segura?', ['Lluvia-Azul!Marzo27', '123456', 'juan2005', 'password']],
            ['¿Qué es el phishing?', ['Un engaño para obtener datos personales haciéndose pasar por una entidad confiable', 'Una técnica para acelerar Internet', 'Un tipo de cable de red', 'Un programa de diseño']],
            ['¿Es recomendable usar la misma contraseña en todas sus cuentas?', ['No, porque si se filtra una se comprometen todas', 'Sí, así es más fácil recordarla', 'Solo si la contraseña es larga', 'Solo en redes sociales']],
            ['¿Qué debe hacer antes de abrir un archivo adjunto de un correo inesperado?', ['Verificar el remitente y desconfiar si no lo esperaba', 'Abrirlo de inmediato', 'Reenviarlo a todos sus contactos', 'Responder con sus datos personales']],
        ],
        2 => [
            ['¿Qué es la verificación en dos pasos?', ['Un segundo factor, como un código temporal, además de la contraseña', 'Escribir la contraseña dos veces', 'Tener dos cuentas de correo', 'Usar dos navegadores']],
            ['Recibe un mensaje urgente de su “banco” que le pide confirmar su clave en un enlace. ¿Qué hace?', ['Ignora el enlace y contacta al banco por sus canales oficiales', 'Ingresa la clave rápidamente para evitar el bloqueo', 'Responde el mensaje con sus datos', 'Reenvía el mensaje a sus amigos']],
            ['¿Por qué es importante instalar las actualizaciones del sistema operativo?', ['Porque corrigen vulnerabilidades de seguridad', 'Porque cambian el fondo de pantalla', 'Porque eliminan los archivos personales', 'No es importante']],
            ['¿Qué es el malware?', ['Software diseñado para dañar un sistema o acceder a él sin autorización', 'Un accesorio de computadora', 'Una red social', 'Un formato de imagen']],
        ],
        3 => [
            ['¿Qué combinación de señales indica con mayor probabilidad un correo fraudulento?', ['El dominio del remitente no coincide con el de la organización y el mensaje genera urgencia', 'Incluye el logotipo de la empresa', 'Está escrito en español', 'Llegó en horario laboral']],
            ['¿Qué práctica protege mejor la información ante un ataque de ransomware?', ['Mantener copias de seguridad periódicas y desconectadas', 'Usar un fondo de pantalla oscuro', 'Guardar todo en el escritorio', 'Desactivar el antivirus para ganar velocidad']],
            ['Si sospecha que su cuenta de trabajo fue comprometida, ¿cuál es la acción prioritaria?', ['Cambiar la contraseña, cerrar sesiones activas y notificar al área de TI', 'Esperar a ver si ocurre algo', 'Borrar todos sus correos', 'Crear una cuenta nueva sin avisar']],
            ['¿Qué principio indica que solo deben recopilarse los datos personales estrictamente necesarios?', ['Minimización de datos', 'Redundancia de datos', 'Compresión de datos', 'Duplicación de datos']],
        ],
    ],
    'COL' => [
        1 => [
            ['¿Qué campo de un correo electrónico describe brevemente el tema del mensaje?', ['Asunto', 'CCO', 'Firma', 'Adjunto']],
            ['¿Qué herramienta permite realizar reuniones por video a distancia?', ['Una plataforma de videoconferencias, como Meet, Teams o Zoom', 'Una hoja de cálculo', 'Un antivirus', 'Un editor de fotografías']],
            ['¿Qué saludo es apropiado en un correo profesional dirigido a una persona desconocida?', ['Estimado(a) señor(a):', '¿Qué onda?', 'Hola, mi amor', 'No incluir saludo']],
            ['¿Qué significa compartir un documento en la nube?', ['Permitir que otras personas lo vean o editen en línea', 'Imprimirlo', 'Borrarlo', 'Enviarlo por fax']],
        ],
        2 => [
            ['¿Para qué sirve el campo CCO (copia oculta) en un correo?', ['Enviar copia a destinatarios sin que los demás vean sus direcciones', 'Cifrar el mensaje', 'Programar el envío', 'Adjuntar archivos']],
            ['Varias personas editan un documento compartido. ¿Qué función permite revisar quién hizo cada cambio?', ['Historial de versiones o control de cambios', 'Corrector ortográfico', 'Zoom', 'Orientación de página']],
            ['En una videollamada de trabajo con ruido en su entorno, ¿qué es lo adecuado?', ['Silenciar el micrófono cuando no esté hablando', 'Subir el volumen de los parlantes', 'Desactivar la cámara de los demás', 'Salir sin avisar']],
            ['¿Qué permiso conviene otorgar a una persona que solo debe leer un documento?', ['Lector (solo lectura)', 'Propietario', 'Editor', 'Administrador']],
        ],
        3 => [
            ['Debe enviar un archivo de 80 MB que excede el límite del correo. ¿Qué opción es más adecuada?', ['Compartir un enlace desde un servicio de almacenamiento en la nube con los permisos correctos', 'Dividir el texto en 40 correos', 'Enviar el archivo por mensaje de texto', 'Imprimir y escanear el archivo']],
            ['¿Qué práctica mejora la claridad de un correo con varios temas pendientes?', ['Usar un asunto específico, puntos numerados y una solicitud de acción clara', 'Escribir todo en mayúsculas', 'Omitir el saludo y la firma', 'Enviar varios correos sin asunto']],
            ['En un documento colaborativo, ¿cómo se propone un cambio sin modificar directamente el texto?', ['Usando el modo sugerencia o agregando un comentario', 'Borrando el párrafo', 'Descargando e imprimiendo el documento', 'Cambiando el título del archivo']],
            ['¿Qué es la netiqueta?', ['El conjunto de normas de cortesía en la comunicación en línea', 'Una etiqueta adhesiva para equipos', 'Un tipo de red inalámbrica', 'Un programa de correo']],
        ],
    ],
    'PRO' => [
        1 => [
            ['¿Qué es una carpeta en una computadora?', ['Un contenedor para organizar archivos', 'Un programa antivirus', 'Un tipo de impresora', 'Una red social']],
            ['¿Qué indica la extensión .pdf en el nombre de un archivo?', ['Que es un documento en formato PDF', 'Que es una hoja de cálculo', 'Que es un programa ejecutable', 'Que es un archivo de audio']],
            ['¿Qué ocurre al aplicar “Cortar” y luego “Pegar” a un archivo?', ['Se mueve a otra ubicación', 'Se duplica', 'Se elimina definitivamente', 'Cambia de formato']],
            ['¿Qué es el almacenamiento en la nube?', ['Guardar archivos en servidores accesibles por Internet', 'Guardar archivos en una memoria USB', 'Imprimir documentos', 'Comprimir fotografías']],
        ],
        2 => [
            ['¿Qué nombre de archivo facilita encontrarlo y entender su contenido?', ['2026-05_Informe_ventas_v2.docx', 'documento1.docx', 'nuevo.docx', 'aaa.docx']],
            ['Para buscar una frase exacta en un buscador web, ¿qué se recomienda?', ['Escribirla entre comillas', 'Escribirla en mayúsculas', 'Agregar signos de admiración', 'Escribir solo la primera palabra']],
            ['¿Para qué sirve comprimir varios archivos en un .zip?', ['Para agruparlos en un solo archivo, generalmente de menor tamaño', 'Para protegerlos contra todo virus', 'Para convertirlos en PDF', 'Para editarlos en línea']],
            ['¿Qué herramienta ayuda a no olvidar una reunión programada?', ['Un calendario digital con recordatorios', 'Una calculadora', 'Un reproductor de video', 'La papelera de reciclaje']],
        ],
        3 => [
            ['¿Qué criterio es más importante para evaluar la confiabilidad de una fuente en Internet?', ['La autoría, la fecha y la institución que respalda la información', 'La cantidad de imágenes', 'El color del sitio', 'Que aparezca en el primer resultado']],
            ['¿Qué estrategia reduce el riesgo de perder archivos importantes?', ['Aplicar la regla 3-2-1 de copias de seguridad', 'Guardar todo en el escritorio', 'Tener un solo archivo por carpeta', 'Borrar las versiones anteriores']],
            ['En un buscador, ¿qué operador limita los resultados a un sitio específico?', ['site:', 'find:', 'only:', 'web:']],
            ['¿Qué técnica ayuda a priorizar tareas según su urgencia e importancia?', ['La matriz de Eisenhower', 'La regla de tres', 'La tabla periódica', 'El código ASCII']],
        ],
    ],
];
