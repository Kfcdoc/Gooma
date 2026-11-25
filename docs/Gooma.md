TECNOLÓGICO NACIONAL DE MÉXICO

**INSTITUTO TECNOLÓGICO DE MILPA ALTA II**

<img src="media/image1.png" style="width:1.7in;height:1.63194in"
alt="Logotipo del Instituto Tecnológico de Milpa Alta II" />

**PROPUESTA DE PROYECTO FINAL**

**DE**

**GooMaps**

**Sistema de Gestión de Costos de Proyectos de Software con Mapa del
Dinero**

**Integrantes del equipo:**

Gonzalez Gaona Sergio Alfredo, Cruz Hernandez Isay,Alvarez cornelio
Kevin,

Emanuel De La Rosa Montiel,Paredes muñóz Brayan

Carrera: **Ingeniería en Sistemas Computacionales**

Materia: **Aplicaciones Web Back End**

25 de noviembre de 2026

### 1.1 Antecedentes

Las empresas de software suelen controlar sus costos en hojas de cálculo
dispersas. Esto provoca errores de conversión de divisas, anticipos sin
rastrear, costos extra no documentados y clientes que no saben en qué se
gasta su dinero.

### 1.2 Objetivo general

Desarrollar un sistema web que permita a los gestores organizar y
controlar los costos de proyectos de software, y a los clientes
consultar su proyecto, mediante una visualización llamada **Mapa del
Dinero**: una gráfica de flujo premium que muestra de dónde viene el
dinero (anticipos, facturas) y a quién y a qué se destina (personas del
equipo, herramientas, impuestos y extras).

### 1.3 Objetivos específicos

- Gestionar costos totales, facturados, anticipos, tiempos y tecnologías
  de cada proyecto.

- Convertir cifras entre divisas (MXN, USD, EUR) conservando el tipo de
  cambio de cada movimiento.

- Agregar porcentajes de costos extra, marcados como necesarios u
  opcionales.

- Generar reportes aptos para auditoría con bitácora de cambios.

- Ofrecer a los clientes una consulta de solo lectura de su proyecto.

### 1.4 Alcance

**Incluye:** gestión de clientes, proyectos, personas del equipo,
tarifas por rol, registro de horas, facturas, anticipos, tecnologías,
costos extra, conversión de divisas, Mapa del Dinero, reportes de
auditoría, portal del cliente.

**No incluye:** nómina legal, timbrado fiscal real (CFDI), pasarelas de
pago, aplicación móvil nativa.

### 1.5 Requisitos técnicos obligatorios

| Componente    | Tecnología              |
|---------------|-------------------------|
| Backend       | Laravel                 |
| Frontend      | React                   |
| Base de datos | MongoDB                 |
| Despliegue    | Docker (docker-compose) |
| Documentación | README en GitHub        |

### 1.6 Entregables

1.  Sistema funcional dockerizado.

<!-- -->

1.  Repositorio en GitHub con README (instalación, uso, variables de
    entorno).

2.  Documentación: licitación, reglas de negocio, diagrama de procesos,
    casos de uso y cronograma.

3.  Reporte de investigación sobre valoración de software y salarios.

### 1.7 Estimación de costo actualizada

Presupuesto académico con tarifas propuestas por el equipo y referencias
salariales externas identificadas en la sección 6.3. Las tarifas de
cobro y las 600 horas son supuestos de planeación, no valores publicados
por las fuentes. Los porcentajes se aplican conforme a RN-15; el
escenario incluye el riesgo opcional.

| Rol                    | Horas | Tarifa/hora (MXN) | Costo      |
|------------------------|-------|-------------------|------------|
| Gestor de proyecto     | 80    | 400.00            | 32,000.00  |
| Desarrollador backend  | 200   | 400.00            | 80,000.00  |
| Desarrollador frontend | 160   | 375.00            | 60,000.00  |
| Base de datos / DevOps | 60    | 450.00            | 27,000.00  |
| QA / pruebas           | 60    | 300.00            | 18,000.00  |
| Documentación          | 40    | 250.00            | 10,000.00  |
| Costo base             | 600   |                   | 227,000.00 |

| Concepto extra     | %   | Tipo      | Monto (MXN) |
|--------------------|-----|-----------|-------------|
| Imprevistos        | 10% | Necesario | 22,700.00   |
| Riesgo tecnológico | 5%  | Opcional  | 11,350.00   |
| Margen de ganancia | 15% | Necesario | 34,050.00   |
| Subtotal           |     |           | 295,100.00  |
| IVA                | 16% | Necesario | 47,216.00   |
| Total estimado     |     |           | 342,316.00  |

En USD y EUR el sistema recalcula el total con el tipo de cambio vigente
y lo congela al facturar.

### 1.8 Criterios de evaluación de la propuesta

- Cumplimiento de requisitos funcionales y técnicos.

<!-- -->

- Claridad de la documentación.

<!-- -->

- Originalidad (Mapa del Dinero) y calidad visual.

<!-- -->

- Que todos los integrantes conozcan y puedan explicar todo el sistema.

## 2. Reglas de negocio

**Proyectos y costos**

- **RN-01.** Todo proyecto pertenece a un cliente y tiene al menos un
  gestor responsable.

<!-- -->

- **RN-02.** Todo proyecto tiene un presupuesto inicial, una divisa base
  y fechas de inicio y fin estimadas.

<!-- -->

- **RN-03.** El costo base de un proyecto es la suma de horas
  registradas × tarifa por hora del rol de cada persona.

<!-- -->

- **RN-04.** Las tarifas por rol se guardan con fecha; un cambio de
  tarifa no altera registros anteriores.

<!-- -->

- **RN-05.** Cada proyecto puede tener tecnologías asociadas (por
  ejemplo Laravel, React, MariaDB).

**Anticipos y facturación**

- **RN-06.** Un anticipo se registra con monto, divisa, fecha y tipo de
  cambio del día.

<!-- -->

- **RN-07.** Una factura no puede exceder el saldo pendiente del
  proyecto (total menos facturado).

<!-- -->

- **RN-08.** Los anticipos se descuentan de la facturación final; el
  sistema calcula saldo a favor o por pagar.

<!-- -->

- **RN-09.** Una factura emitida no se edita; solo se cancela con motivo
  y se genera otra.

**Divisas**

- **RN-10.** Divisas soportadas: MXN, USD y EUR.

<!-- -->

- **RN-11.** Todo movimiento (anticipo, factura, gasto) guarda su tipo
  de cambio al momento del registro y ese valor **no cambia**.

<!-- -->

- **RN-12.** La visualización en otra divisa usa el tipo de cambio
  guardado de cada movimiento; para totales sin movimiento se usa el
  tipo de cambio vigente.

**Costos extra**

- **RN-13.** Un costo extra es un porcentaje con nombre y se clasifica
  como *necesario* u *opcional*.

<!-- -->

- **RN-14.** Los extras necesarios siempre se aplican; los opcionales
  pueden activarse o desactivarse y el total se recalcula.

<!-- -->

- **RN-15.** Los porcentajes se calculan sobre el costo base, salvo el
  IVA, que se calcula sobre base más extras.

**Auditoría**

- **RN-16.** Toda creación, modificación o cancelación de un registro
  financiero genera una entrada en la bitácora (usuario, acción, valor
  anterior, valor nuevo, fecha y hora).

<!-- -->

- **RN-17.** La bitácora no se puede editar ni borrar desde el sistema.

<!-- -->

- **RN-18.** Los reportes de auditoría se pueden exportar a PDF y Excel
  e incluyen divisa y tipo de cambio de cada movimiento.

**Roles y acceso**

- **RN-19.** Existen tres roles: administrador, gestor y cliente.

<!-- -->

- **RN-20.** El gestor solo ve y edita sus proyectos; el cliente solo
  consulta sus proyectos en modo lectura.

<!-- -->

- **RN-21.** El cliente nunca ve tarifas internas por persona, solo
  montos agregados, a menos que el gestor lo habilite.

**Mapa del Dinero**

- **RN-22.** El mapa muestra tres columnas: origen (cliente), proyecto y
  destinos (personas, herramientas, impuestos, extras).

<!-- -->

- **RN-23.** El grosor de cada flujo es proporcional a su monto en la
  divisa seleccionada.

## 3. Diagrama de procesos

### 3.1 Proceso principal (por actor)

| Paso | Gestor                                                    | Sistema                                        | Cliente                                     |
|------|-----------------------------------------------------------|------------------------------------------------|---------------------------------------------|
| 1    | Registra cliente y crea proyecto con presupuesto y divisa | Valida y guarda; escribe en bitácora           |                                             |
| 2    | Asigna personas, roles y tecnologías                      | Calcula costo base estimado                    |                                             |
| 3    | Configura costos extra (necesarios / opcionales)          | Recalcula total con extras                     |                                             |
| 4    | Registra anticipo                                         | Guarda monto y tipo de cambio del día          |                                             |
| 5    | Registra horas y gastos durante el desarrollo             | Actualiza costos reales y Mapa del Dinero      |                                             |
| 6    | Emite factura                                             | Verifica saldo (RN-07), congela tipo de cambio |                                             |
| 7    | Comparte enlace de consulta                               | Genera acceso de solo lectura                  | Consulta avance, facturas, anticipos y Mapa |
| 8    | Solicita reporte de auditoría                             | Genera PDF/Excel con bitácora                  |                                             |
| 9    | Cierra el proyecto                                        | Calcula saldo final y bloquea ediciones        | Consulta resumen final                      |

Representación visual del proceso anterior: cada fila es un paso y las
flechas muestran quién actúa y cómo responde el sistema.

<img src="media/image2.png" style="width:6in;height:7.93889in"
alt="Diagrama de flujo por carriles: Gestor, Sistema y Cliente, pasos 1 a 9" />

*Figura 1. Proceso principal por actor (pasos 1 a 9).*

### 3.2 Flujo de registro de un movimiento financiero

1.  El gestor captura monto, divisa y concepto.

<!-- -->

1.  El sistema obtiene el tipo de cambio del día.

<!-- -->

1.  Valida reglas (saldo disponible, divisa soportada, permisos).

<!-- -->

1.  Si falla, muestra el error y termina.

<!-- -->

1.  Si pasa, guarda el movimiento con su tipo de cambio.

<!-- -->

1.  Registra la entrada en la bitácora.

<!-- -->

1.  Actualiza totales y el Mapa del Dinero.

Diagrama de flujo del registro de un movimiento financiero (anticipo,
factura o gasto), con su punto de decisión.

<img src="media/image3.png" style="width:6in;height:6.55417in"
alt="Diagrama de flujo con decisión de validación: si falla muestra error, si pasa guarda, registra bitácora y actualiza totales" />

*Figura 2. Flujo de registro de un movimiento financiero.*

## 4. Casos de uso

### 4.1 Actores

- **Administrador:** gestiona usuarios, roles, catálogos y divisas.

<!-- -->

- **Gestor de proyecto:** administra los proyectos y sus finanzas.

<!-- -->

- **Cliente:** consulta su proyecto.

### 4.2 Lista de casos de uso

| ID    | Caso de uso                             | Actor                 |
|-------|-----------------------------------------|-----------------------|
| CU-01 | Iniciar sesión                          | Todos                 |
| CU-02 | Gestionar usuarios y roles              | Administrador         |
| CU-03 | Gestionar catálogo de roles y tarifas   | Administrador         |
| CU-04 | Gestionar clientes                      | Gestor                |
| CU-05 | Crear y editar proyecto                 | Gestor                |
| CU-06 | Asignar personas y tecnologías          | Gestor                |
| CU-07 | Registrar horas                         | Gestor                |
| CU-08 | Registrar anticipo                      | Gestor                |
| CU-09 | Emitir y cancelar factura               | Gestor                |
| CU-10 | Configurar costos extra                 | Gestor                |
| CU-11 | Cambiar divisa de visualización         | Gestor, Cliente       |
| CU-12 | Ver Mapa del Dinero                     | Gestor, Cliente       |
| CU-13 | Generar reporte de auditoría            | Gestor, Administrador |
| CU-14 | Consultar bitácora de cambios           | Administrador, Gestor |
| CU-15 | Compartir enlace de consulta al cliente | Gestor                |
| CU-16 | Consultar proyecto (solo lectura)       | Cliente               |

### 4.3 Descripción detallada de casos clave

**CU-08 Registrar anticipo**

- Actor: gestor. Precondición: proyecto activo.

<!-- -->

- Flujo normal: el gestor elige el proyecto, captura monto, divisa y
  fecha; el sistema consulta el tipo de cambio, guarda el anticipo,
  escribe en bitácora y actualiza el Mapa del Dinero.

<!-- -->

- Alterno: la divisa no está soportada y el sistema rechaza el registro.

<!-- -->

- Postcondición: el saldo del proyecto disminuye.

**CU-09 Emitir factura**

- Actor: gestor. Precondición: existe saldo pendiente.

<!-- -->

- Flujo normal: el gestor captura concepto y monto; el sistema valida
  RN-07, congela el tipo de cambio, genera folio y registra en bitácora.

<!-- -->

- Alterno: el monto excede el saldo y el sistema muestra error.

<!-- -->

- Postcondición: factura no editable.

**CU-10 Configurar costos extra**

- Actor: gestor.

<!-- -->

- Flujo normal: agrega un extra con nombre y porcentaje, lo marca
  necesario u opcional, activa o desactiva los opcionales y el sistema
  recalcula el total en vivo.

**CU-12 Ver Mapa del Dinero**

- Actores: gestor y cliente.

<!-- -->

- Flujo normal: el usuario abre el proyecto, el sistema dibuja el flujo
  con personas, herramientas, impuestos y extras; al pasar el cursor
  sobre un nodo muestra horas, tarifa (solo gestor) y total.

<!-- -->

- Alterno: el cliente no ve tarifas internas (RN-21).

**CU-13 Generar reporte de auditoría**

- Actor: gestor o administrador.

<!-- -->

- Flujo normal: elige proyecto y rango de fechas, el sistema compila
  movimientos, tipos de cambio y bitácora, y permite descargar en PDF o
  Excel.

## 5. Cronograma de desarrollo

Fechas relativas; se ajustan al calendario que indique el profesor. La
semana 1 inicia al aprobarse la propuesta.

| Semana     | Actividad                                                          | Responsables       | Entregable                 |
|------------|--------------------------------------------------------------------|--------------------|----------------------------|
| 0 (25 nov) | Revisión de propuesta con el profesor                              | Todo el equipo     | Propuesta aceptada         |
| 1          | Investigación de costos y salarios; licitación y reglas de negocio | Todo el equipo     | Documentos v1              |
| 1          | Diagrama de procesos y casos de uso                                | Todo el equipo     | Diagramas v1               |
| 2          | Modelo de datos, repositorio GitHub, Docker base                   | Backend / DevOps   | docker-compose funcionando |
| 3          | API Laravel: autenticación, roles, clientes, proyectos             | Backend            | Endpoints básicos          |
| 4          | API: horas, anticipos, facturas, extras, divisas                   | Backend            | Módulo financiero          |
| 4          | Frontend: login, listado y formulario de proyectos                 | Frontend           | Pantallas base             |
| 5          | Mapa del Dinero (gráfica Sankey, tema dorado, selector de divisa)  | Frontend           | Gráfica funcional          |
| 6          | Bitácora de auditoría y reportes PDF/Excel                         | Backend            | Reportes                   |
| 6          | Portal de consulta del cliente (enlace de solo lectura)            | Frontend + Backend | Portal cliente             |
| 7          | Pruebas, correcciones, README completo                             | Todo el equipo     | Versión candidata          |
| 8          | Revisión cruzada, ensayo y entrega final                           | Todo el equipo     | Proyecto final             |

Revisión cruzada: cada semana, cada integrante revisa el trabajo de otro
y lo explica en el equipo, como pide el proyecto.

## 6. Investigación: cómo se valora el software y cuánto se paga

### 6.1 Métodos de valoración

- **Estimación por horas × tarifa:** el más común; se suman horas por
  rol y se multiplican por la tarifa.

<!-- -->

- **Puntos de función:** mide el tamaño por funcionalidades (entradas,
  salidas, consultas, archivos) y asigna un costo por punto.

<!-- -->

- **COCOMO:** modelo que estima esfuerzo en personas-mes a partir de las
  líneas de código estimadas.

<!-- -->

- **Precio fijo vs. tiempo y materiales:** en precio fijo el proveedor
  asume el riesgo; en tiempo y materiales se cobra lo trabajado.

<!-- -->

- **Valor para el cliente:** el precio se basa en el ahorro o ingreso
  que genera el sistema.

### 6.2 Costos que se deben considerar

Sueldos o honorarios, herramientas y licencias, infraestructura y
hosting, pruebas, mantenimiento posterior, impuestos, imprevistos y
margen de ganancia.

### 6.3 Salarios y tarifas por rol

Fecha de recopilación de referencias: 24 de noviembre de 2026. Mercado
de referencia: México. Moneda del presupuesto: pesos mexicanos (MXN).

La investigación distingue entre salario de una persona contratada y
tarifa de un servicio. Las referencias salariales sirven para
contextualizar el presupuesto; no determinan automáticamente cuánto debe
cobrarse por hora. Se evita atribuir a los portales tarifas que no
publican.

### 6.3.1 Referencias salariales identificadas

| Puesto publicado    | Rango mensual mostrado               | Fuente                 | Alcance                                                                                         |
|---------------------|--------------------------------------|------------------------|-------------------------------------------------------------------------------------------------|
| Software Developer  | \$23,000–\$42,000 MXN                | Glassdoor México \[1\] | Referencia general de desarrollo; no desglosa backend/frontend ni acredita un nivel específico. |
| Software Engineer I | \$29,000–\$52,000 MXN de sueldo base | Glassdoor México \[2\] | Referencia complementaria; corresponde al título publicado, no a todos los niveles.             |

Los rangos corresponden a la información visible en los resultados
consultados. Las páginas son dinámicas y pueden actualizar sus cifras.
No se presentan como una encuesta propia, una cotización contractual ni
una garantía salarial. No se verificó en esta recopilación el tamaño de
muestra o la equivalencia exacta entre los puestos.

Corrección de la información preliminar: no se incorporan como hechos
confirmados los rangos previamente mencionados para gestión de
proyectos, DevOps, QA o documentación, ni las tarifas freelance
específicas, porque no quedaron comprobados con suficiente detalle. Para
esos roles se conserva una propuesta presupuestaria explícita; su
validación salarial por rol permanece pendiente. Por tanto, esta sección
aporta referencias reales de desarrollo, pero no constituye una
investigación salarial exhaustiva de los seis roles.

### 6.3.2 Tarifas propuestas 

El escenario de planeación asume perfiles intermedios para gestión,
desarrollo, DevOps y QA; para documentación, junior/intermedio. Esta es
una decisión del proyecto, no una clasificación inferida de las fuentes.
Las siguientes tarifas son propuestas del presupuesto y no promedios de
mercado.

| Rol                    | Perfil supuesto   | Tarifa propuesta MXN/h | Estado del respaldo                                           |
|------------------------|-------------------|------------------------|---------------------------------------------------------------|
| Gestor de proyecto     | Intermedio        | \$400.00               | Propuesta; falta referencia salarial específica               |
| Desarrollador backend  | Intermedio        | \$400.00               | Referencia general de desarrollo \[1\]\[2\]; tarifa propuesta |
| Desarrollador frontend | Intermedio        | \$375.00               | Referencia general de desarrollo \[1\]\[2\]; tarifa propuesta |
| Base de datos / DevOps | Intermedio        | \$450.00               | Propuesta; falta referencia salarial específica               |
| QA / pruebas           | Intermedio        | \$300.00               | Propuesta; falta referencia salarial específica               |
| Documentación          | Junior/intermedio | \$250.00               | Propuesta; falta referencia salarial específica               |

### 6.3.3 Criterio de conversión y costos

Como comparación aritmética, se adopta una base convencional de 160
horas mensuales. Es un supuesto de cálculo, no una jornada legal ni una
cantidad garantizada de horas facturables. El rango de Software
Developer de \$23,000–\$42,000 mensuales equivale a \$143.75–\$262.50
MXN por hora bajo ese supuesto \[1\].

Ejemplo metodológico, no salario observado: \$40,000 mensuales / 160
horas = \$250 MXN/h de salario equivalente. Una tarifa de servicio
requiere definir por separado los costos incluidos, el tiempo no
facturable y las condiciones contractuales; no debe confundirse con
salario bruto o neto.

Para evitar duplicar conceptos, en este presupuesto las tarifas de la
tabla se utilizan como costos base propuestos, sin utilidad, IVA ni
reservas por imprevistos o riesgo. Esos conceptos se agregan una sola
vez en la sección 1.7. No se afirma que estas tarifas sean un costo
patronal completo: prestaciones, cargas y herramientas necesitan
desglosarse si se requiere una cotización contractual.

### 6.3.4 Resultado del presupuesto

Se mantienen las horas del documento original: 600 horas totales. No se
presentan como horas reales investigadas; deben validarse mediante una
descomposición de tareas y criterios de estimación. El costo base
actualizado es de \$227,000.00 MXN.

Con imprevistos del 10%, riesgo tecnológico opcional del 5% y margen del
15%, todos calculados sobre el costo base conforme a RN-15, el subtotal
es \$295,100.00 MXN. Aplicando el IVA del 16% previsto en el escenario
original, el total es \$342,316.00 MXN. El 15% es un recargo sobre
costo, no un margen del 15% sobre ventas.

Si se desactiva el riesgo tecnológico opcional, el subtotal es
\$283,750.00 MXN, el IVA es \$45,400.00 MXN y el total es \$329,150.00
MXN. El tratamiento fiscal debe validarse para una contratación real.

## 6.4 Fuentes y trazabilidad

\[1\] Glassdoor. Sueldo: Software Developer en México. Referencia
consultada: 24 de noviembre de 2026. Rango mensual visible: \$23
mil–\$42 mil MXN.
https://www.glassdoor.com.mx/Sueldos/mexico-software-developer-sueldo-SRCH_IL.0,6_IS5738_KO7,25.htm

\[2\] Glassdoor. Sueldo: Software Engineer I en México. Referencia
consultada: 24 de noviembre de 2026. Rango de sueldo base mensual
visible: \$29 mil–\$52 mil MXN.
https://www.glassdoor.com.mx/Sueldos/m%C3%A9xico-software-engineer-i-sueldo-SRCH_IL.0,6_IS5738_KO7,26.htm

Fuentes adicionales por completar: vacantes con salario explícito para
gestor de proyectos, DevOps, QA y documentación; encuestas salariales de
TI con metodología y nivel de experiencia; y perfiles freelance con
tarifas publicadas si se requiere comparar honorarios. Estas categorías
son líneas de investigación pendientes, no evidencia ya comprobada.

Para cada nueva evidencia registrar: puesto exacto, nivel declarado por
la fuente, ubicación, moneda, periodicidad, salario bruto/neto o
modalidad de pago cuando se indique, URL, fecha de publicación o
actualización y fecha de consulta. No mezclar sueldo base, compensación
total y tarifa freelance sin explicar sus diferencias.

## 
