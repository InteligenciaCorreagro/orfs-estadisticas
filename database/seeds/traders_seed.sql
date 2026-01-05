-- database/seeds/traders_seed.sql

-- Traders de ejemplo basados en tu código VBA

INSERT INTO traders (nombre, nit, porcentaje_comision, activo) VALUES
('LUIS FERNANDO VELEZ VELEZ', '71234567', 0.50, 1),
('LORENA PINTO', '52987654', 0.45, 1),
('EDUARDO VELEZ', '71345678', 0.50, 1),
('REGISTROS BUCARAMANGA I', '900123456', 0.40, 1),
('REGISTROS BUCARAMANGA II', '900234567', 0.40, 1),
('ARMANDO JOSE GUERRERO', '72456789', 0.45, 1),
('AUGUSTO JOSE MARTINEZ RAMIREZ', '73567890', 0.50, 1),
('CARMEN ELENA PEÑA', '52678901', 0.45, 1),
('JAVIER CORREA', '71789012', 0.50, 1),
('RAMIRO VILLAQUIRAN', '74890123', 0.45, 1),
('LILIANA MARIA LONDOÑO ARANGO', '52901234', 0.45, 1),
('MARIA ALEJANDRA PRIETO', '52012345', 0.45, 1),
('REGISTROS BOGOTA', '900345678', 0.40, 1),
('REGISTROS BOGOTA II', '900456789', 0.40, 1);

-- Adicionales para Luis Fernando Velez
INSERT INTO trader_adicionales (trader_id, nombre_adicional) VALUES
((SELECT id FROM traders WHERE nombre = 'LUIS FERNANDO VELEZ VELEZ'), 'INCENTIVO CACO MEDELLIN'),
((SELECT id FROM traders WHERE nombre = 'LUIS FERNANDO VELEZ VELEZ'), 'CARTERA MEDELLIN'),
((SELECT id FROM traders WHERE nombre = 'LUIS FERNANDO VELEZ VELEZ'), 'MEDELLIN 2'),
((SELECT id FROM traders WHERE nombre = 'LUIS FERNANDO VELEZ VELEZ'), 'SUBASTA CENCOGAN - LVELEZ'),
((SELECT id FROM traders WHERE nombre = 'LUIS FERNANDO VELEZ VELEZ'), 'SUBASTA CASANARE-LVELEZ'),
((SELECT id FROM traders WHERE nombre = 'LUIS FERNANDO VELEZ VELEZ'), 'SUBASTA GANADERA SINU'),
((SELECT id FROM traders WHERE nombre = 'LUIS FERNANDO VELEZ VELEZ'), 'SUBASTA SANTA CLARA - LVELEZ');

-- Adicionales para Lorena Pinto
INSERT INTO trader_adicionales (trader_id, nombre_adicional) VALUES
((SELECT id FROM traders WHERE nombre = 'LORENA PINTO'), 'SUBASTA VISION-LPINTO');

-- Adicionales para Registros Bucaramanga
INSERT INTO trader_adicionales (trader_id, nombre_adicional) VALUES
((SELECT id FROM traders WHERE nombre = 'REGISTROS BUCARAMANGA I'), 'AUGUSTO JOSE MARTINEZ RAMIREZ'),
((SELECT id FROM traders WHERE nombre = 'REGISTROS BUCARAMANGA II'), 'CENTRO INCENTIVO BUCARAMANGA'),
((SELECT id FROM traders WHERE nombre = 'REGISTROS BUCARAMANGA II'), 'AUGUSTO JOSE MARTINEZ RAMIREZ');

-- Adicionales para Carmen Elena Peña
INSERT INTO trader_adicionales (trader_id, nombre_adicional) VALUES
((SELECT id FROM traders WHERE nombre = 'CARMEN ELENA PEÑA'), 'SUBASTA GANADERA SINU'),
((SELECT id FROM traders WHERE nombre = 'CARMEN ELENA PEÑA'), 'SUBASTA SUGANORTE SA-CARMEN P');

-- Adicionales para Javier Correa
INSERT INTO trader_adicionales (trader_id, nombre_adicional) VALUES
((SELECT id FROM traders WHERE nombre = 'JAVIER CORREA'), 'AGRONEGOCIOS EL TRIGAL LTDA'),
((SELECT id FROM traders WHERE nombre = 'JAVIER CORREA'), 'JAVIER CORREA');

-- Adicionales para Maria Alejandra Prieto
INSERT INTO trader_adicionales (trader_id, nombre_adicional) VALUES
((SELECT id FROM traders WHERE nombre = 'MARIA ALEJANDRA PRIETO'), 'REGISTROS BOGOTA'),
((SELECT id FROM traders WHERE nombre = 'MARIA ALEJANDRA PRIETO'), 'REGISTROS BOGOTA II'),
((SELECT id FROM traders WHERE nombre = 'MARIA ALEJANDRA PRIETO'), 'SUBASTA VISION-LPINTO');