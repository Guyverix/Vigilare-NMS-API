window.onload = function () {
  window.ui = SwaggerUIBundle({
    url: "/openapi",  // relative path works if served from same domain/port
    dom_id: "#swagger-ui",
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIStandalonePreset
    ],
    layout: "BaseLayout"
  });
};
