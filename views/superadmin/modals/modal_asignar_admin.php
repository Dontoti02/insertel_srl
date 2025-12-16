<!-- Modal Asignar Administrador -->
<div class="modal fade" id="modalAsignarAdmin" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-person-check me-2"></i>Asignar Administrador
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formAsignarAdmin">
                <div class="modal-body">
                    <input type="hidden" name="sede_id" id="sedeIdAsignar">

                    <div class="alert alert-info">
                        <strong>Sede:</strong> <span id="nombreSedeAsignar"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Seleccionar Administrador *</label>
                        <select class="form-select" name="admin_id" id="adminSelectModal" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($administradores as $admin): ?>
                                <option value="<?php echo $admin['id']; ?>">
                                    <?php echo htmlspecialchars($admin['nombre_completo']); ?>
                                    <?php if ($admin['sede_actual']): ?>
                                        - (Actual: <?php echo htmlspecialchars($admin['sede_actual']); ?>)
                                    <?php else: ?>
                                        - ✓ Disponible
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">El administrador será responsable de esta sede</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>