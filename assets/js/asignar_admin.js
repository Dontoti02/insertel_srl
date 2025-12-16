/**
 * Gestión de asignación de administradores a sedes
 */

function abrirModalAsignar(sedeId, nombreSede, responsableActual) {
    document.getElementById('sedeIdAsignar').value = sedeId;
    document.getElementById('nombreSedeAsignar').textContent = nombreSede;

    // Seleccionar el admin actual si existe
    const selectAdmin = document.getElementById('adminSelectModal');
    if (responsableActual && responsableActual !== 'null') {
        selectAdmin.value = responsableActual;
    } else {
        selectAdmin.value = '';
    }

    const modal = new bootstrap.Modal(document.getElementById('modalAsignarAdmin'));
    modal.show();
}

// Manejar el envío del formulario de asignación
document.addEventListener('DOMContentLoaded', function () {
    const formAsignar = document.getElementById('formAsignarAdmin');

    if (formAsignar) {
        formAsignar.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // Deshabilitar botón y mostrar loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Asignando...';

            fetch('../../ajax/asignar_admin_sede.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Cerrar modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAsignarAdmin'));
                        modal.hide();

                        // Mostrar mensaje de éxito con SweetAlert2 si está disponible
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: data.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Recargar la página para ver los cambios
                                window.location.reload();
                            });
                        } else {
                            alert(data.message);
                            window.location.reload();
                        }
                    } else {
                        // Mostrar error
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                                confirmButtonText: 'OK'
                            });
                        } else {
                            alert(data.message);
                        }

                        // Restaurar botón
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al procesar la solicitud',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Error al procesar la solicitud');
                    }

                    // Restaurar botón
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });
    }
});
