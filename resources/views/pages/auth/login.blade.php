<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Ingresa a tu cuenta')" :description="__('Ingresa tu correo y tu contraseña')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6" id="loginForm">
            @csrf

            <!-- Email Address -->
            <div class="form-field" data-field="email">
                <flux:input
                    name="email"
                    :label="__('Correo')"
                    :value="old('email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@ejemplo.com"
                />
            </div>

            <!-- Password -->
            <div class="form-field relative" data-field="password">
                <flux:input
                    name="password"
                    :label="__('Contraseña')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Contraseña')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Recuerdame')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="button" class="w-full" id="submitButton" data-test="login-button">
                    {{ __('Iniciar sesión') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __('¿No tienes cuenta?') }}</span>
                <flux:link :href="route('register')" wire:navigate>{{ __('Registrarse') }}</flux:link>
            </div>
        @endif
    </div>

    <!-- Incluir SweetAlert2 desde CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script para validaciones con SweetAlert2 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const submitButton = document.getElementById('submitButton');

            // Cambiar type del botón a "button" para evitar envío automático
            submitButton.type = 'button';

            // Configuración de SweetAlert2
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
                    .replace('password', 'Contraseña')
                    .replace('email', 'Correo electrónico');
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
                    case 'email':
                        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

                    case 'password':
                        return value.length >= 1; // Solo verifica que tenga algo, no longitud mínima

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

            // Mostrar alerta de credenciales incorrectas (si hay error del backend)
            function showInvalidCredentialsAlert() {
                clearAllHighlights();

                Swal.fire({
                    ...SwalConfig,
                    title: 'Credenciales incorrectas',
                    html: `
                        <div class="text-center">
                            <p class="text-lg font-medium mb-2">Correo o contraseña incorrectos</p>
                        </div>
                    `
                }).then(() => {
                    const emailField = document.querySelector('input[name="email"]');
                    if (emailField) {
                        emailField.focus();
                        emailField.select();
                    }
                });
            }

            // Validar todos los campos
            function validateAllFields() {
                // Quitar resaltados anteriores
                clearAllHighlights();

                // Campos requeridos
                const requiredFields = ['email', 'password'];

                // 1. Verificar campos vacíos
                for (const fieldName of requiredFields) {
                    if (isEmptyField(fieldName)) {
                        showFieldAlert(fieldName);
                        return false;
                    }
                }

                // 2. Verificar formatos específicos
                if (!validateFieldFormat('email')) {
                    showInvalidFormatAlert('email', 'Debe ser un correo electrónico válido<br><small class="text-gray-500">Ejemplo: usuario@dominio.com</small>');
                    return false;
                }

                // 3. Si todo está bien, ENVIAR FORMULARIO DIRECTAMENTE
                return true;
            }

            // NO MOSTRAR SweetAlert de éxito - solo validar y enviar
            // Eliminada la función showLoginSuccessAlert()

            // Manejar clic en el botón "Iniciar sesión"
            submitButton.addEventListener('click', function() {
                // Cambiar a estado de carga
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = `
                    <span class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Iniciando sesión...
                    </span>
                `;
                submitButton.disabled = true;

                // Pequeño delay para mostrar el spinner
                setTimeout(() => {
                    const isValid = validateAllFields();

                    if (isValid) {
                        // ✅ Si la validación de frontend pasa, ENVIAR FORMULARIO DIRECTAMENTE
                        // ✅ NO mostrar SweetAlert de éxito
                        form.submit();
                    } else {
                        // ❌ Si hay errores en frontend, restaurar botón
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

            // Verificar si hay errores del backend al cargar la página
            @if($errors->any())
                setTimeout(() => {
                    // Mostrar alerta solo si hay errores de credenciales
                    @if($errors->has('email') || $errors->has('password'))
                        showInvalidCredentialsAlert();
                    @endif
                }, 500);
            @endif
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
            0%, 100% {
                opacity: 0.5;
            }
            50% {
                opacity: 1;
            }
        }
    </style>
</x-layouts::auth>
