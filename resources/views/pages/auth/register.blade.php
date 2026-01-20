<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <!-- Encabezado de autenticación -->
        <x-auth-header :title="__('Crear una cuenta')" :description="__('Ingresa tus datos a continuación:')" />

        <!-- Estado de la sesión (para mensajes de éxito/error) -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <!-- Formulario de registro -->
        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6" id="registerForm">
            @csrf

            <!-- Campo: Nombre completo -->
            <div class="form-field" data-field="name">
                <flux:input
                    name="name"
                    :label="__('Nombre')"
                    :value="old('name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    :placeholder="__('Nombre completo')" />
            </div>

            <!-- Campo: Correo electrónico -->
            <div class="form-field" data-field="email">
                <flux:input
                    name="email"
                    :label="__('Correo electrónico')"
                    :value="old('email')"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="ejemplo@correo.com" />
            </div>

            <!-- Campo: Número telefónico (10 dígitos exactos) -->
            <div class="form-field" data-field="phone">
                <flux:input
                    name="phone"
                    :label="__('Número telefónico')"
                    :value="old('phone')"
                    type="tel"
                    required
                    autocomplete="tel"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    inputmode="numeric"
                    onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                    oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10)"
                    :placeholder="__('10 dígitos, ej: 5512345678')"
                    :helper="__('Solo números, exactamente 10 dígitos')" />
            </div>

            <!-- Campo: Contraseña -->
            <div class="form-field" data-field="password">
                <flux:input
                    name="password"
                    :label="__('Contraseña')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Mínimo 8 caracteres')"
                    viewable />
            </div>

            <!-- Campo: Confirmar contraseña -->
            <div class="form-field" data-field="password_confirmation">
                <flux:input
                    name="password_confirmation"
                    :label="__('Confirma tu contraseña')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Repite tu contraseña')"
                    viewable />
            </div>

            <!-- Botón de envío -->
            <div class="flex items-center justify-end">
                <flux:button type="button" variant="primary" class="w-full" id="submitButton" data-test="register-user-button">
                    {{ __('Crear cuenta') }}
                </flux:button>
            </div>
        </form>

        <!-- Enlace para usuarios que ya tienen cuenta -->
        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('¿Ya tienes cuenta?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Iniciar sesión') }}</flux:link>
        </div>
    </div>

    <!-- Incluir SweetAlert2 desde CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script para validaciones con SweetAlert2 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitButton = document.getElementById('submitButton');

            // Cambiar type del botón a "button" para evitar envío automático
            submitButton.type = 'button';

            // Configuración de SweetAlert2 - TODOS con ícono de warning
            const SwalConfig = {
                customClass: {
                    popup: 'rounded-xl shadow-2xl',
                    title: 'text-xl font-bold text-gray-800',
                    htmlContainer: 'text-gray-600',
                    confirmButton: 'swal-ok-button'
                },
                buttonsStyling: false,
                allowOutsideClick: false,
                allowEscapeKey: true,
                showConfirmButton: true,
                showCancelButton: false,
                showCloseButton: false,
                confirmButtonText: 'OK',
                confirmButtonColor: '#494949',
                background: '#ffffff',
                width: '420px',
                icon: 'warning'
            };

            // Obtener el label del campo desde el HTML
            function getFieldLabel(fieldName) {
                const input = document.querySelector(`[name="${fieldName}"]`);
                if (!input) return fieldName;

                // Buscar el label asociado al input
                const label = input.closest('.form-field')?.querySelector('label') ||
                    input.closest('div')?.previousElementSibling ||
                    input.previousElementSibling;

                if (label && label.tagName === 'LABEL') {
                    return label.textContent.trim();
                }

                // Si no encuentra label, usar el nombre del campo formateado
                return fieldName
                    .replace(/_/g, ' ')
                    .replace(/^\w/, c => c.toUpperCase())
                    .replace('password confirmation', 'Confirmar contraseña')
                    .replace('password', 'Contraseña')
                    .replace('phone', 'Teléfono')
                    .replace('email', 'Correo electrónico')
                    .replace('name', 'Nombre');
            }

            // Validar si un campo está vacío
            function isEmptyField(fieldName) {
                const input = document.querySelector(`[name="${fieldName}"]`);
                if (!input) return true;

                const value = input.value.trim();
                return !value;
            }

            // Validar formato específico de campos
            function validateFieldFormat(fieldName) {
                const input = document.querySelector(`[name="${fieldName}"]`);
                if (!input) return false;

                const value = input.value.trim();

                switch (fieldName) {
                    case 'name':
                        return /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,}$/.test(value);

                    case 'email':
                        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

                    case 'phone':
                        return /^\d{10}$/.test(value);

                    case 'password':
                        return value.length >= 8;

                    case 'password_confirmation':
                        const password = document.querySelector('[name="password"]').value;
                        return value === password;

                    default:
                        return true;
                }
            }

            // Resaltar campo con error
            function highlightErrorField(fieldName, showError = true) {
                const field = document.querySelector(`[data-field="${fieldName}"]`);
                const input = document.querySelector(`[name="${fieldName}"]`);

                if (!field || !input) return;

                if (showError) {
                    field.classList.add('field-error');
                    input.classList.add('border-red-500', 'bg-red-50', 'error-field');
                    input.classList.remove('border-gray-300');

                    // Scroll al campo
                    input.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    // Enfocar el campo
                    setTimeout(() => {
                        input.focus();
                        input.select();
                    }, 300);
                } else {
                    field.classList.remove('field-error');
                    input.classList.remove('border-red-500', 'bg-red-50', 'error-field');
                    input.classList.add('border-gray-300');
                }
            }

            // Quitar todos los resaltados de error
            function clearAllHighlights() {
                document.querySelectorAll('.form-field').forEach(field => {
                    field.classList.remove('field-error');
                });
                document.querySelectorAll('input').forEach(input => {
                    input.classList.remove('border-red-500', 'bg-red-50', 'error-field');
                    input.classList.add('border-gray-300');
                });
            }

            // Mostrar SweetAlert2 para campo vacío
            function showFieldAlert(fieldName) {
                const label = getFieldLabel(fieldName);

                // Primero resaltar el campo
                clearAllHighlights();
                highlightErrorField(fieldName, true);

                // Mostrar alerta después de un pequeño delay
                setTimeout(() => {
                    Swal.fire({
                        ...SwalConfig,
                        title: 'Campo requerido',
                        html: `
                            <div class="text-center">
                                <p class="text-gray-600">Por favor, completa este campo para continuar</p>
                            </div>
                        `
                    }).then(() => {
                        // Mantener el resaltado después de cerrar el alerta
                        const input = document.querySelector(`[name="${fieldName}"]`);
                        if (input) {
                            input.focus();
                            input.select();
                        }
                    });
                }, 100);
            }

            // Mostrar alerta para contraseñas que no coinciden
            function showPasswordMismatchAlert() {
                // Resaltar ambos campos de contraseña
                clearAllHighlights();
                highlightErrorField('password', true);
                highlightErrorField('password_confirmation', true);

                setTimeout(() => {
                    Swal.fire({
                        ...SwalConfig,
                        title: 'Error en las contraseñas',
                        html: `
                            <div class="text-center">
                                <p class="text-lg font-medium mb-2">Las contraseñas no coinciden</p>
                            </div>
                        `
                    }).then(() => {
                        const passwordField = document.querySelector('[name="password"]');
                        if (passwordField) {
                            passwordField.focus();
                            passwordField.select();
                        }
                    });
                }, 100);
            }

            // Mostrar alerta para formato inválido
            function showInvalidFormatAlert(fieldName, customMessage) {
                const label = getFieldLabel(fieldName);

                clearAllHighlights();
                highlightErrorField(fieldName, true);

                setTimeout(() => {
                    Swal.fire({
                        ...SwalConfig,
                        title: 'Formato incorrecto',
                        html: `
                            <div class="text-center">
                                <p class="text-gray-600">${customMessage}</p>
                            </div>
                        `
                    }).then(() => {
                        const input = document.querySelector(`[name="${fieldName}"]`);
                        if (input) {
                            input.focus();
                            input.select();
                        }
                    });
                }, 100);
            }

            // Validar todos los campos
            function validateAllFields() {
                // Quitar resaltados anteriores
                clearAllHighlights();

                // Campos requeridos
                const requiredFields = ['name', 'email', 'phone', 'password', 'password_confirmation'];

                // 1. Verificar campos vacíos
                for (const fieldName of requiredFields) {
                    if (isEmptyField(fieldName)) {
                        showFieldAlert(fieldName);
                        return false;
                    }
                }

                // 2. Verificar formatos específicos
                if (!validateFieldFormat('name')) {
                    showInvalidFormatAlert('name', 'Solo se permiten letras y espacios (mínimo 2 caracteres)');
                    return false;
                }

                if (!validateFieldFormat('email')) {
                    showInvalidFormatAlert('email', 'Debe ser un correo electrónico válido<br><small class="text-gray-500">Ejemplo: usuario@dominio.com</small>');
                    return false;
                }

                if (!validateFieldFormat('phone')) {
                    showInvalidFormatAlert('phone', 'Debe tener exactamente 10 dígitos numéricos<br><small class="text-gray-500">Ejemplo: 5512345678</small>');
                    return false;
                }

                if (!validateFieldFormat('password')) {
                    showInvalidFormatAlert('password', 'La contraseña debe tener al menos 8 caracteres');
                    return false;
                }

                if (!validateFieldFormat('password_confirmation')) {
                    showPasswordMismatchAlert();
                    return false;
                }

                // 3. Si todo está bien, mostrar confirmación
                return true;
            }

            // Mostrar alerta de confirmación con ícono de éxito
            function showConfirmationAlert() {
                return Swal.fire({
                    customClass: {
                        popup: 'rounded-xl shadow-2xl',
                        title: 'text-xl font-bold text-gray-800',
                        htmlContainer: 'text-gray-600',
                        confirmButton: 'swal-ok-button'
                    },
                    buttonsStyling: false,
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    showConfirmButton: true,
                    showCancelButton: false,
                    showCloseButton: false,
                    
                    background: '#ffffff',
                    width: '420px',
                    icon: 'success',
                    title: '¡Cuenta creada exitosamente!',
                    timer: 0,
                    timerProgressBar: false,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#494949',
                    allowEnterKey: true,
                    focusConfirm: true
                });
            }

            // Manejar clic en el botón "Crear cuenta"
            submitButton.addEventListener('click', function() {
                // Cambiar a estado de carga
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = `
                    <span class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Validando...
                    </span>
                `;
                submitButton.disabled = true;

                // Pequeño delay para mostrar el spinner
                setTimeout(() => {
                    const isValid = validateAllFields();

                    if (isValid) {
                        // Mostrar mensaje de confirmación
                        showConfirmationAlert().then(() => {
                            // Enviar formulario después de mostrar mensaje
                            form.submit();
                        });
                    } else {
                        // Restaurar botón si hay errores
                        submitButton.innerHTML = originalText;
                        submitButton.disabled = false;
                    }
                }, 500);
            });

            // Quitar resaltado al empezar a escribir en un campo
            document.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', function() {
                    const fieldName = this.name;
                    const field = this.closest('.form-field');

                    if (field && field.classList.contains('field-error')) {
                        field.classList.remove('field-error');
                        this.classList.remove('border-red-500', 'bg-red-50', 'error-field');
                        this.classList.add('border-gray-300');
                    }
                });
            });

            // Permitir envío con Enter (solo si todo está válido)
            form.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitButton.click();
                }
            });
        });
    </script>

    <!-- Estilos personalizados -->
    <style>
        /* Estilo para el botón OK de SweetAlert2 */
        .swal-ok-button {
            background-color: #494949 !important;
            color: white !important;
            font-weight: 500 !important;
            padding: 10px 24px !important;
            border-radius: 8px !important;
            border: none !important;
            transition: all 0.3s ease !important;
            width: 100% !important;
            margin-top: 16px !important;
        }

        .swal-ok-button:hover {
            background-color: #3a3a3a !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }

        .swal-ok-button:active {
            transform: translateY(0) !important;
        }

        /* Estilos para SweetAlert2 con ícono warning */
        .swal2-icon.swal2-warning {
            border-color: #f59e0b !important;
            color: #f59e0b !important;
        }

        .swal2-icon.swal2-warning .swal2-icon-content {
            color: #f59e0b !important;
        }

        /* Estilos para campos con error */
        .field-error input {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
            animation: fieldErrorPulse 0.5s ease-in-out;
        }

        .field-error label {
            color: #dc2626 !important;
            font-weight: 600;
        }

        /* Animación para el campo con error */
        @keyframes fieldErrorPulse {
            0% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-3px);
            }

            50% {
                transform: translateX(3px);
            }

            75% {
                transform: translateX(-3px);
            }

            100% {
                transform: translateX(0);
            }
        }

        /* Estilo para SweetAlert2 personalizado */
        .swal2-popup {
            border-radius: 16px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }

        .swal2-title {
            color: #1f2937 !important;
            font-size: 1.5rem !important;
            margin-bottom: 1rem !important;
        }

        .swal2-html-container {
            color: #6b7280 !important;
            font-size: 1rem !important;
            line-height: 1.5 !important;
        }

        .swal2-icon {
            margin: 1.5rem auto 1rem !important;
            transform: scale(1.2) !important;
        }

        /* Efecto de pulso suave para campos requeridos */
        .error-field {
            position: relative;
        }

        .error-field::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 8px;
            border: 2px solid rgba(239, 68, 68, 0.3);
            animation: pulseBorder 2s infinite;
            pointer-events: none;
        }

        @keyframes pulseBorder {

            0%,
            100% {
                opacity: 0.5;
            }

            50% {
                opacity: 1;
            }
        }
    </style>
</x-layouts::auth>