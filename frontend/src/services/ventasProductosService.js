import api from "./api";

export const ventasProductosService = {
  catalogo() {
    return api.get("/barbero/ventas-productos/catalogo");
  },
  registrar(data) {
    return api.post("/barbero/ventas-productos", data);
  },
};
