import api from "./api";

export const productosService = {
  listar(params = {}) {
    return api.get("/admin/productos", { params });
  },
  crear(data) {
    return api.post("/admin/productos", data);
  },
  actualizar(id, data) {
    return api.put(`/admin/productos/${id}`, data);
  },
  desactivar(id, data = {}) {
    return api.patch(`/admin/productos/${id}/desactivar`, data);
  },
  registrarLote(id, data) {
    return api.post(`/admin/productos/${id}/lotes`, data);
  },
};
