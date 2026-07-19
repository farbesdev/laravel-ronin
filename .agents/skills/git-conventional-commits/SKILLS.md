# 🎯 Skill: Conventional Commits, Git Flow, Git Tags y Git Releases (Estandarización de Commits y Lanzamientos)

**Cuándo usar:** Utilice esta habilidad siempre que la tarea requiera sugerir, generar o validar un mensaje de commit en Git, trabajar bajo la estructura de ramas de Git Flow, o gestionar etiquetas (git tags) y lanzamientos (git releases).

**Contexto que provee:** Convenciones de nombres de ramas, reglas de nomenclatura, mapeo de tipos de commits, emojis oficiales, redacción en inglés de commits, versionado semántico (SemVer), creación de git tags y preparación de notas de release (git releases).

---

## 🏗️ Estructura Estándar del Commit

Todos los mensajes de commit generados por la IA en este proyecto deben seguir estrictamente el estándar **Conventional Commits 1.0.0**, adaptado con iconos (emojis) y redactado **100% en inglés**:

```text
<tipo>(<alcance>): <emoji> <short description in lowercase, imperative/present tense>

- [Detailed description of changes in English]
- [Clear explanation of the "what" and "why" of the change, max 96 characters per line, in English]

[Footer with BREAKING CHANGES or references to tickets/issues]
```

---

## 🏷️ Tipos de Commits y Emojis Oficiales

| Tipo | Emoji | Descripción |
| :--- | :---: | :--- |
| **`feat`** | ✨ | Nueva característica o funcionalidad para el usuario. |
| **`fix`** | 🐛 | Corrección de un error o fallo (bug). |
| **`docs`** | 📝 | Cambios o adiciones en la documentación del sistema. |
| **`style`** | 💄 | Cambios de formato, estilos (CSS), espaciados (sin afectar la lógica). |
| **`refactor`** | ♻️ | Reestructuración de código de producción que no corrige bugs ni añade características. |
| **`perf`** | ⚡ | Cambios de código orientados a mejorar el rendimiento o velocidad. |
| **`test`** | 🧪 | Añadir o corregir pruebas unitarias o de integración. |
| **`build`** | 📦 | Cambios en el sistema de construcción o dependencias externas (npm, composer). |
| **`ci`** | 🚀 | Modificación en la configuración o scripts de CI/CD (GitHub Actions, etc.). |
| **`chore`** | 🔧 | Tareas de mantenimiento general o configuraciones secundarias. |
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

Para garantizar la coherencia en el flujo de integración continua, cada tipo de rama dentro de Git Flow requiere un formato y tipo de commit específico (siempre 100% en inglés):

### 1. Ramas de Funcionalidad (`feature/*`)
*   **Origen:** `develop` | **Destino:** `develop`
*   **Commits sugeridos:** Utilizar mayormente el tipo `feat` para la implementación principal.
*   **Ejemplo de título:** `feat(api-core): ✨ implement stripe payment gateway`

### 2. Ramas de Corrección (`bugfix/*`)
*   **Origen:** `develop` | **Destino:** `develop`
*   **Commits sugeridos:** Utilizar el tipo `fix` para corregir errores detectados en desarrollo.
*   **Ejemplo de título:** `fix(saas-portal): 🐛 fix login redirection on safari`

### 3. Ramas de Corrección Crítica (`hotfix/*`)
*   **Origen:** `main` | **Destino:** `main` y `develop`
*   **Commits sugeridos:** Utilizar el tipo `fix` y acompañar con un incremento de versión parche en el pie de página (ej. `v1.0.1`).
*   **Ejemplo de título:** `fix(api-sunat): 🐛 resolve timeout in sunat digital signature`

### 4. Ramas de Lanzamiento (`release/*`)
*   **Origen:** `develop` | **Destino:** `main` y `develop`
*   **Commits sugeridos:** Utilizar el tipo `chore` o `build` para preparar la compilación final y actualizar las versiones.
*   **Ejemplo de título:** `chore(monorepo): 🔧 prepare release v1.1.0`

### 5. Ramas de Soporte (`support/*`)
*   **Origen:** `main` o commits históricos específicos.
*   **Commits sugeridos:** Según corresponda (`feat`, `fix`), limitando los cambios al soporte de versiones antiguas.

---

## 🏷️ Gestión de Git Tags y Git Releases

Como experto en etiquetas y publicaciones de Git, se siguen estas pautas estrictas:

### 1. Versionado Semántico (SemVer)
Se utiliza el esquema `vMAJOR.MINOR.PATCH` (ej. `v1.2.3`):
*   **`MAJOR`**: Cambios incompatibles con versiones anteriores (Breaking Changes).
*   **`MINOR`**: Nueva funcionalidad retrocompatible.
*   **`PATCH`**: Corrección de errores retrocompatible.

### 2. Creación de Git Tags
Las etiquetas de versión deben crearse como anotadas (`annotated tags`) para registrar la fecha, autor y un mensaje descriptivo:
```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

### 3. Formato de Git Releases (Notas de Lanzamiento)
Al publicar una release (ej. en GitHub), se genera un registro de cambios claro clasificado por categorías y redactado 100% en inglés:
```markdown
# Release v1.0.0 (YYYY-MM-DD)

## ✨ Features
- **scope**: Short description of new feature in English.

## 🐛 Bug Fixes
- **scope**: Short description of fix in English.

## 🔧 Chore & Maintenance
- **scope**: Maintenance details in English.

## ⚠️ Breaking Changes
- Detailed description of breaking changes and migration steps if applicable.
```