import api from "./api";

export const serviciosService = {
  listarCategorias(params = {}) {
    return api.get("/admin/categorias", { params });
  },
  crearCategoria(data) {
    return api.post("/admin/categorias", data);
  },
  actualizarCategoria(id, data) {
    return api.put(`/admin/categorias/${id}`, data);
  },
  desactivarCategoria(id, data = {}) {
    return api.patch(`/admin/categorias/${id}/desactivar`, data);
  },
  listarServicios(params = {}) {
    return api.get("/admin/servicios", { params });
  },
  crearServicio(data) {
    return api.post("/admin/servicios", data);
  },
  actualizarServicio(id, data) {
    return api.put(`/admin/servicios/${id}`, data);
  },
  desactivarServicio(id, data = {}) {
    return api.patch(`/admin/servicios/${id}/desactivar`, data);
  },
};
