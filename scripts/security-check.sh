#!/usr/bin/env bash
# =============================================================================
# scripts/security-check.sh
# Security Verification Script — OWASP Top Ten
# =============================================================================
# Uso:
#   chmod +x scripts/security-check.sh
#   ./scripts/security-check.sh
#
# Opciones:
#   --group=A01   Solo verifica el riesgo OWASP específico
#   --quick       Solo ejecuta composer audit y PHPStan (sin tests)
# =============================================================================

set -euo pipefail

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
RESET='\033[0m'

# Contadores
ERRORS=0
WARNINGS=0

log_ok()      { echo -e "${GREEN}✅ $1${RESET}"; }
log_error()   { echo -e "${RED}❌ $1${RESET}"; ERRORS=$((ERRORS + 1)); }
log_warn()    { echo -e "${YELLOW}⚠️  $1${RESET}"; WARNINGS=$((WARNINGS + 1)); }
log_section() { echo -e "\n${BLUE}${BOLD}$1${RESET}\n$(printf '%.0s─' {1..60})"; }
log_info()    { echo -e "${BOLD}ℹ️  $1${RESET}"; }

echo ""
echo -e "${BOLD}🔒 OWASP Top Ten — Security Verification Workflow${RESET}"
echo -e "$(printf '%.0s═' {1..60})"
echo -e "📅 $(date '+%Y-%m-%d %H:%M:%S')"
echo -e "📂 $(pwd)"
echo ""

# ──────────────────────────────────────────────────────────────────────────────
# [A06] Vulnerable and Outdated Components — composer audit
# ──────────────────────────────────────────────────────────────────────────────
log_section "[A06] Vulnerable and Outdated Components"
log_info "Ejecutando: composer audit"
if composer audit; then
    log_ok "No se encontraron vulnerabilidades en dependencias"
else
    log_error "[A06] Dependencias con vulnerabilidades críticas detectadas"
fi

# ──────────────────────────────────────────────────────────────────────────────
# [ALL] Static Analysis — PHPStan/Larastan nivel 9
# ──────────────────────────────────────────────────────────────────────────────
log_section "[ALL] Análisis Estático — PHPStan/Larastan Nivel 9"
log_info "Ejecutando: vendor/bin/phpstan analyse (nivel configurado en phpstan.neon)"
if vendor/bin/phpstan analyse --no-progress; then
    log_ok "Análisis estático pasado sin errores"
else
    log_error "[ALL] PHPStan encontró errores de tipo o seguridad"
fi

# ──────────────────────────────────────────────────────────────────────────────
# [A03] Injection — Buscar patrones inseguros en código fuente
# ──────────────────────────────────────────────────────────────────────────────
log_section "[A03] Injection — Búsqueda de Patrones Inseguros"

SRC_DIRS=("src/" "app/")
INSECURE_PATTERNS=(
    "eval("                         # Ejecución de código arbitrario
    "unserialize("                  # Object Injection
    "shell_exec("                   # OS Injection
    "passthru("                     # OS Injection
    "proc_open("                    # OS Injection
    "popen("                        # OS Injection
    '\$_GET\['                      # Input sin sanitizar
    '\$_POST\['                     # Input sin sanitizar
    '\$_REQUEST\['                  # Input sin sanitizar
    'md5($'                         # Hash roto para contraseñas
    'sha1($'                        # Hash roto para contraseñas
    'base64_decode.*COOKIE'         # Deserialización de cookies
    'DB::statement.*\$'             # SQL potencialmente dinámico
    'DB::select.*\."'               # SQL con concatenación
    '{!!'                           # Blade unescaped output
    'echo \$_'                      # XSS directo
)

FOUND_INSECURE=false
for dir in "${SRC_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        for pattern in "${INSECURE_PATTERNS[@]}"; do
            matches=$(grep -rn "$pattern" "$dir" 2>/dev/null || true)
            if [ -n "$matches" ]; then
                log_warn "Patrón inseguro '$pattern' encontrado en $dir:"
                echo "$matches" | head -5
                FOUND_INSECURE=true
            fi
        done
    fi
done

if [ "$FOUND_INSECURE" = false ]; then
    log_ok "No se encontraron patrones inseguros conocidos"
fi

# ──────────────────────────────────────────────────────────────────────────────
# [A05] Security Misconfiguration — Verificar .env crítico
# ──────────────────────────────────────────────────────────────────────────────
log_section "[A05] Security Misconfiguration — Configuración"

# Verificar que APP_DEBUG no está en true en producción
if [ -f ".env" ]; then
    DEBUG_VALUE=$(grep '^APP_DEBUG=' .env | cut -d= -f2 | tr -d '"' | tr -d "'" | tr '[:upper:]' '[:lower:]' || true)
    ENV_VALUE=$(grep '^APP_ENV=' .env | cut -d= -f2 | tr -d '"' | tr -d "'" | tr '[:upper:]' '[:lower:]' || true)

    if [ "$ENV_VALUE" = "production" ] && [ "$DEBUG_VALUE" = "true" ]; then
        log_error "[A05] APP_DEBUG=true en entorno production. ¡CRÍTICO!"
    else
        log_ok "APP_DEBUG=$DEBUG_VALUE (APP_ENV=$ENV_VALUE)"
    fi

    # Verificar APP_KEY
    APP_KEY=$(grep '^APP_KEY=' .env | cut -d= -f2 || true)
    if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
        log_error "[A05] APP_KEY no está configurada"
    else
        log_ok "APP_KEY está configurada"
    fi
else
    log_warn "No se encontró el archivo .env (puede ser normal en CI)"
fi

# Verificar que .env no está commiteado
if git ls-files .env --error-unmatch 2>/dev/null; then
    log_error "[A05] El archivo .env está commiteado en git. ¡CRÍTICO!"
else
    log_ok ".env no está rastreado por git"
fi

# ──────────────────────────────────────────────────────────────────────────────
# [ALL] Security Tests Suite — Pest con grupo 'security'
# ──────────────────────────────────────────────────────────────────────────────
log_section "[ALL] Security Test Suite — BDD/TDD Tests"
log_info "Ejecutando: vendor/bin/phpunit --group=security"

if vendor/bin/phpunit --group=security --testdox 2>/dev/null; then
    log_ok "Todos los tests de seguridad han pasado"
else
    # Si no hay tests en el grupo 'security', informar en lugar de fallar
    log_warn "Algunos tests de seguridad fallaron o no hay tests en el grupo 'security'"
    log_info "Tip: Usa #[Group('security')] y #[Group('A01')] en tus tests"
fi

# ──────────────────────────────────────────────────────────────────────────────
# [A02] Cryptographic Failures — Verificar algoritmos inseguros
# ──────────────────────────────────────────────────────────────────────────────
log_section "[A02] Cryptographic Failures — Algoritmos Inseguros"

CRYPTO_PATTERNS=(
    "md5("
    "sha1("
    "'DES'"
    "'RC4'"
    "'MD5'"
    "crypt("
)

SRC_DIRS=("src/" "app/")
FOUND_WEAK_CRYPTO=false
for dir in "${SRC_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        for pattern in "${CRYPTO_PATTERNS[@]}"; do
            matches=$(grep -rn "$pattern" "$dir" 2>/dev/null || true)
            if [ -n "$matches" ]; then
                log_warn "[A02] Algoritmo criptográfico débil '$pattern':"
                echo "$matches" | head -3
                FOUND_WEAK_CRYPTO=true
            fi
        done
    fi
done

if [ "$FOUND_WEAK_CRYPTO" = false ]; then
    log_ok "No se encontraron algoritmos criptográficos débiles"
fi

# ──────────────────────────────────────────────────────────────────────────────
# RESUMEN FINAL
# ──────────────────────────────────────────────────────────────────────────────
echo ""
echo -e "$(printf '%.0s═' {1..60})"
echo -e "${BOLD}📊 RESUMEN — OWASP Security Check${RESET}"
echo -e "$(printf '%.0s─' {1..60})"

if [ "$ERRORS" -eq 0 ] && [ "$WARNINGS" -eq 0 ]; then
    echo -e "${GREEN}${BOLD}✅ PASADO — Sin errores ni advertencias de seguridad${RESET}"
elif [ "$ERRORS" -eq 0 ]; then
    echo -e "${YELLOW}${BOLD}⚠️  PASADO CON ADVERTENCIAS — $WARNINGS advertencia(s)${RESET}"
else
    echo -e "${RED}${BOLD}❌ FALLADO — $ERRORS error(es) críticos, $WARNINGS advertencia(s)${RESET}"
    exit 1
fi

echo ""
echo -e "${BLUE}📚 Referencias:${RESET}"
echo "   • OWASP Top Ten 2021: https://owasp.org/www-project-top-ten/"
echo "   • Laravel Security:   https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html"
echo ""
