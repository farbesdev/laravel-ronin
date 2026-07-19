# 🎯 Skill: Conventional Commits y Git Flow (Mensajes de Commit Estandarizados)

**Cuándo usar:** Utilice esta habilidad siempre que la tarea requiera sugerir, generar o validar un mensaje de commit en Git, o bien al trabajar bajo la estructura de ramas del estándar Git Flow.

**Contexto que provee:** Convenciones de nombres de ramas, reglas de nomenclatura, mapeo de tipos de commits, emojis oficiales en español y la relación directa con el modelo de desarrollo Git Flow.

---

## 🏗️ Estructura Estándar del Commit

Todos los mensajes de commit generados por la IA en este proyecto deben seguir estrictamente el estándar **Conventional Commits 1.0.0**, adaptado con iconos (emojis) y redactado en **español**:

```text
<tipo>(<alcance>): <emoji> <título breve en infinitivo/imperativo y en minúsculas>

- [Descripción detallada de cada uno de los cambios por guiones en español]
- [Explicación clara del "qué" y el "por qué" del cambio, manteniendo líneas de máximo 96 caracteres]

[Pie de página con BREAKING CHANGES o referencias a tickets/issues]
```

---

## 🏷️ Tipos de Commits y Emojis Oficiales

| Tipo | Emoji | Descripción |
| :--- | :---: | :--- |
| **`feat`** | ✨ | Nueva característica o funcionalidad para el usuario. |
| **`fix`** | 🐛 | Corrección de un error o fallo (bug). |
| **`docs`** | 📝 | Cambios o adiciones en la documentación del sistema. |
| **`style`** | 💄 | Cambios de formato, estilos (CSS), espaciados (sin afectar la lógica del código). |
| **`refactor`** | ♻️ | Reestructuración de código de producción que no corrige bugs ni añade características. |
| **`perf`** | ⚡ | Cambios de código orientados a mejorar el rendimiento o velocidad. |
| **`test`** | 🧪 | Añadir o corregir pruebas unitarias o de integración (Pest/Vitest). |
| **`build`** | 📦 | Cambios en el sistema de construcción de la aplicación o dependencias externas (npm, composer). |
| **`ci`** | 🚀 | Modificación en la configuración o scripts de CI/CD (GitHub Actions, Dockerfiles de producción). |
| **`chore`** | 🔧 | Tareas de mantenimiento general, actualizaciones de configuración o refactorización no productiva. |
| **`revert`** | ⏪ | Reversión de un commit previo. |

---

## 🎯 Alcances (Scopes) Sugeridos del Monorepo

Para identificar fácilmente a qué componente del monorepo pertenece el cambio, se definen los siguientes alcances obligatorios:

*   **`api-core`**: Lógica, modelos, acciones o controladores del backend en `apps/api-core`.
*   **`saas-subscriptions`**: Portal de suscripciones del cliente en `apps/saas-subscriptions`.
*   **`saas-admin`**: Portal Central/dashboard administrativo en `apps/saas-admin`.
*   **`saas-tenant`**: Portal tenant/dashboard en `apps/saas-tenant`.
*   **`saas-website`**: Portal web en `apps/saas-website`.
*   **`api-decolecta`**: Api para consultas Reniec, SUNAT, SBS, AFP `packages/api-decolecta`.
*   **`specs`**: Documentación de especificaciones funcionales en `.specs/`.
*   **`docker`**: Configuración de infraestructura local en `packages/docker` o compose.
*   **`saas-platform`**: Configuraciones generales de la raíz (`package.json`, workspaces, pnpm).

---

## 🌊 Relación de Commits y Ramas con Git Flow

Para garantizar la coherencia en el flujo de integración continua, cada tipo de rama dentro de Git Flow requiere un formato y tipo de commit específico:

### 1. Ramas de Funcionalidad (`feature/*`)
*   **Origen:** `develop` | **Destino:** `develop`
*   **Commits sugeridos:** Utilizar mayormente el tipo `feat` para la implementación principal.
*   **Ejemplo de título:** `feat(api-core): ✨ implementar pasarela de pagos stripe`

### 2. Ramas de Corrección (`bugfix/*`)
*   **Origen:** `develop` | **Destino:** `develop`
*   **Commits sugeridos:** Utilizar el tipo `fix` para corregir errores detectados en desarrollo.
*   **Ejemplo de título:** `fix(saas-portal): 🐛 corregir redirección del login en safari`

### 3. Ramas de Corrección Crítica (`hotfix/*`)
*   **Origen:** `main` | **Destino:** `main` y `develop`
*   **Commits sugeridos:** Utilizar el tipo `fix` y acompañar con un incremento de versión parche en el pie de página (ej. `v1.0.1`).
*   **Ejemplo de título:** `fix(api-sunat): 🐛 resolver timeout en firma digital sunat`

### 4. Ramas de Lanzamiento (`release/*`)
*   **Origen:** `develop` | **Destino:** `main` y `develop`
*   **Commits sugeridos:** Utilizar el tipo `chore` o `build` para preparar la compilación final y actualizar las versiones.
*   **Ejemplo de título:** `chore(monorepo): 🔧 preparar lanzamiento de versión v1.1.0`

### 5. Ramas de Soporte (`support/*`)
*   **Origen:** `main` o commits históricos específicos.
*   **Commits sugeridos:** Según corresponda (`feat`, `fix`), limitando los cambios al soporte de versiones antiguas.