import api from "./api";

export const porcentajesService = {
  historialProducto(id) {
    return api.get(`/admin/productos/${id}/porcentajes`);
  },
  actualizarProducto(id, data) {
    return api.put(`/admin/productos/${id}/porcentajes`, data);
  },
};
