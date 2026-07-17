---
name: programming-principles
description: >
  Experto en Principios de Programación aplicados a PHP 8.5, JavaScript y TypeScript.
  Usar cuando se evalúe la calidad del código, se planifique la arquitectura de módulos,
  se analicen dependencias entre componentes, se discuta cohesión y acoplamiento, se apliquen
  principios filosóficos de diseño o se optimice el rendimiento con criterio técnico.
  Activa en "principios", "cohesión", "acoplamiento", "SoC", "SOLID", "REP", "CCP", "CRP",
  "Worse is Better", "Premature Optimization", "Mechanical Sympathy" o "PoLA".
---

# Principios de Programación — PHP 8.5, JavaScript y TypeScript

Perfil de **Filósofo del Código y Arquitecto de Software** con dominio de los principios
fundamentales de diseño de software — cohesión, acoplamiento, filosofía de diseño y
optimización de rendimiento — aplicados al stack ERP SaaS.

> **Cómo usar este skill:** Carga los archivos de referencia relevantes según el contexto.
> No es necesario cargar todos — solo los principios que aplican a la situación actual.

---

## Categorías de Principios

### 1. Cohesión y Acoplamiento (Principios de Paquetes)
**Referencia:** [references/cohesion-coupling.md](references/cohesion-coupling.md)

Principios de Robert C. Martin para organizar el código en paquetes/módulos:

| Principio | Nombre Completo | Categoría |
|---|---|---|
| **REP** | Release Reuse Equivalency Principle | Cohesión |
| **CCP** | Common Closure Principle | Cohesión |
| **CRP** | Common Reuse Principle | Cohesión |
| **ADP** | Acyclic Dependencies Principle | Acoplamiento |
| **SDP** | Stable Dependencies Principle | Acoplamiento |
| **SAP** | Stable Abstractions Principle | Acoplamiento |
| **SOLID** | SRP, OCP, LSP, ISP, DIP | Clases/Módulos |

### 2. Filosofía de Programación y Diseño
**Referencia:** [references/philosophy.md](references/philosophy.md)

| Principio | Descripción |
|---|---|
| **Worse is Better** | Simplicidad sobre completitud (Gabriel, 1991) |
| **EDD** | Error-Driven Development |
| **SoC** | Separation of Concerns |
| **PoLA** | Principle of Least Astonishment |
| **YAGNI** | You Aren't Gonna Need It |
| **DRY** | Don't Repeat Yourself |
| **KISS** | Keep It Simple, Stupid |
| **PIE** | Program Intently and Expressively |
| **Postel's Law** | Be conservative in what you send, liberal in what you accept |

### 3. Optimización y Rendimiento
**Referencia:** [references/performance-principles.md](references/performance-principles.md)

| Principio | Descripción |
|---|---|
| **Premature Optimization** | "La raíz de todos los males" (Knuth) |
| **Mechanical Sympathy** | Alinear el software con el hardware |
| **Amdahl's Law** | Límites del paralelismo |
| **Little's Law** | Relación entre throughput, latencia y concurrencia |

---

## Guía Rápida de Aplicación

### ¿Dónde va este código?

```
¿Cambia al mismo tiempo por la misma razón?
├── SÍ → Mismo módulo/clase (CCP — Common Closure Principle)
└── NO → Módulos/clases separados

¿Se reutiliza junto con otros componentes?
├── SÍ → Empaquetarlos juntos (REP — Release Reuse Equivalency)
└── NO → Separar para no forzar dependencias innecesarias (CRP)
```

### ¿Optimizo ahora?

```
¿Tenemos un problema de rendimiento medible?
├── NO → No optimizar (Premature Optimization)
└── SÍ → ¿Sabemos el cuello de botella exacto?
          ├── NO → Medir primero (profiling)
          └── SÍ → Optimizar el cuello de botella (Amdahl's Law)
```

---

## Cargar Referencias

Según la tarea, lee el archivo de referencia correspondiente:

```bash
# Para evaluar la organización de módulos:
.agents/skills/programming-principles/references/cohesion-coupling.md

# Para evaluar decisiones de diseño:
.agents/skills/programming-principles/references/philosophy.md

# Para decisiones de optimización:
.agents/skills/programming-principles/references/performance-principles.md
```
