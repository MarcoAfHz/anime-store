-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: shop_peruvian
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id_banner` int unsigned NOT NULL AUTO_INCREMENT,
  `imagen` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subtitulo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enlace` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '#',
  `orden` tinyint unsigned NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_banner`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banners`
--

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` VALUES (1,'banner1-web.jpg','Ropa anime exclusiva','Polos y hoodies importados','/catalogo?categoria=1',1,1),(2,'banner2-web.jpg','Figuras de colección','Demon Slayer, Naruto y más','/catalogo?categoria=2',2,1),(3,'banner3-web.jpg','Ofertas especiales','Hasta 40% de descuento','/catalogo?oferta=1',3,1);
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id_categoria` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `imagen` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Ropa Anime','Polos, hoodies y ropa urbana con diseños de anime exclusivos',NULL,1),(2,'Figuras','Figuras de colección y estatuillas de anime importadas',NULL,1),(3,'Accesorios','Llaveros, mochilas, gorros y accesorios anime',NULL,1),(4,'Calzado','Zapatillas y sandalias con diseños urbanos anime',NULL,1);
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comprobantes`
--

DROP TABLE IF EXISTS `comprobantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comprobantes` (
  `id_comprobante` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pedido` int unsigned NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_emision` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pdf_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_comprobante`),
  UNIQUE KEY `id_pedido` (`id_pedido`),
  UNIQUE KEY `codigo` (`codigo`),
  CONSTRAINT `fk_comp_ped` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comprobantes`
--

LOCK TABLES `comprobantes` WRITE;
/*!40000 ALTER TABLE `comprobantes` DISABLE KEYS */;
/*!40000 ALTER TABLE `comprobantes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cupones`
--

DROP TABLE IF EXISTS `cupones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cupones` (
  `id_cupon` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `descuento` decimal(8,2) NOT NULL,
  `tipo` enum('porcentaje','soles') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'porcentaje',
  `limite_uso` int unsigned DEFAULT NULL,
  `usos_actuales` int unsigned NOT NULL DEFAULT '0',
  `fecha_expira` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_cupon`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cupones`
--

LOCK TABLES `cupones` WRITE;
/*!40000 ALTER TABLE `cupones` DISABLE KEYS */;
INSERT INTO `cupones` VALUES (1,'OTAKU10',10.00,'porcentaje',100,0,'2027-12-31',1);
/*!40000 ALTER TABLE `cupones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departamentos`
--

DROP TABLE IF EXISTS `departamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departamentos` (
  `id_departamento` int unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `costo_envio` decimal(8,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_departamento`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departamentos`
--

LOCK TABLES `departamentos` WRITE;
/*!40000 ALTER TABLE `departamentos` DISABLE KEYS */;
INSERT INTO `departamentos` VALUES (1,'Lima',0.00),(2,'Arequipa',15.00),(3,'Cusco',20.00),(4,'La Libertad',15.00),(5,'Piura',18.00),(6,'Lambayeque',15.00),(7,'Junín',18.00),(8,'Ica',12.00),(9,'Áncash',18.00),(10,'Puno',22.00);
/*!40000 ALTER TABLE `departamentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_pedido`
--

DROP TABLE IF EXISTS `detalle_pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_pedido` (
  `id_detalle` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pedido` int unsigned NOT NULL,
  `id_producto` int unsigned NOT NULL,
  `cantidad` int unsigned NOT NULL DEFAULT '1',
  `precio_unit` decimal(10,2) NOT NULL,
  `talla` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `fk_det_prod` (`id_producto`),
  KEY `idx_pedido` (`id_pedido`),
  CONSTRAINT `fk_det_ped` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `fk_det_prod` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedido`
--

LOCK TABLES `detalle_pedido` WRITE;
/*!40000 ALTER TABLE `detalle_pedido` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalle_pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `imagenes_producto`
--

DROP TABLE IF EXISTS `imagenes_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `imagenes_producto` (
  `id_imagen` int unsigned NOT NULL AUTO_INCREMENT,
  `id_producto` int unsigned NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `orden` tinyint unsigned NOT NULL DEFAULT '1',
  `principal` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_imagen`),
  KEY `idx_producto` (`id_producto`),
  CONSTRAINT `fk_img_prod` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `imagenes_producto`
--

LOCK TABLES `imagenes_producto` WRITE;
/*!40000 ALTER TABLE `imagenes_producto` DISABLE KEYS */;
INSERT INTO `imagenes_producto` VALUES (1,1,'polo-naruto.png',1,1),(2,2,'prod_demo_02.png',1,1),(3,3,'prod_demo_03.png',1,1),(4,4,'prod_demo_04.png',1,1),(5,5,'prod_demo_05.png',1,1),(6,6,'prod_demo_06.png',1,1),(7,7,'prod_demo_07.png',1,1),(8,8,'prod_demo_08.png',1,1),(9,9,'prod_demo_09.png',1,1),(10,10,'prod_demo_10.png',1,1),(11,11,'prod_demo_11.png',1,1),(12,12,'prod_demo_12.png',1,1),(13,13,'prod_demo_13.png',1,1),(14,14,'prod_demo_14.png',1,1);
/*!40000 ALTER TABLE `imagenes_producto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos` (
  `id_pago` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pedido` int unsigned NOT NULL,
  `tipo` enum('efectivo','yape') COLLATE utf8mb4_general_ci NOT NULL,
  `estado` enum('pendiente','confirmado','rechazado') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `yape_qr` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `confirmado_por` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id_pago`),
  UNIQUE KEY `id_pedido` (`id_pedido`),
  CONSTRAINT `fk_pago_ped` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id_pedido` int unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned NOT NULL,
  `id_cupon` int unsigned DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo_envio` decimal(8,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` enum('pendiente','confirmado','enviado','entregado','cancelado') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pendiente',
  `dir_envio` text COLLATE utf8mb4_general_ci NOT NULL,
  `metodo_pago` enum('efectivo','yape') COLLATE utf8mb4_general_ci NOT NULL,
  `notas` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_pedido`),
  KEY `fk_ped_cup` (`id_cupon`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_ped_cup` FOREIGN KEY (`id_cupon`) REFERENCES `cupones` (`id_cupon`) ON DELETE SET NULL,
  CONSTRAINT `fk_ped_usu` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_tallas`
--

DROP TABLE IF EXISTS `producto_tallas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_tallas` (
  `id_talla` int unsigned NOT NULL AUTO_INCREMENT,
  `id_producto` int unsigned NOT NULL,
  `talla` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `stock` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_talla`),
  UNIQUE KEY `uq_prod_talla` (`id_producto`,`talla`),
  KEY `idx_talla_prod` (`id_producto`),
  CONSTRAINT `fk_talla_prod` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_tallas`
--

LOCK TABLES `producto_tallas` WRITE;
/*!40000 ALTER TABLE `producto_tallas` DISABLE KEYS */;
INSERT INTO `producto_tallas` VALUES (1,1,'S',10),(2,1,'M',15),(3,1,'L',13),(4,1,'XL',10),(5,2,'S',5),(6,2,'M',10),(7,2,'L',12),(8,2,'XL',8),(9,3,'S',10),(10,3,'M',15),(11,3,'L',15),(12,4,'M',20),(13,4,'L',20),(14,4,'XL',20),(15,5,'S',5),(16,5,'M',8),(17,5,'L',7),(18,5,'XL',5),(19,13,'38',5),(20,13,'39',8),(21,13,'40',7),(22,13,'41',5),(23,13,'42',3),(24,14,'38',4),(25,14,'39',6),(26,14,'40',6),(27,14,'41',4),(28,14,'42',2);
/*!40000 ALTER TABLE `producto_tallas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id_producto` int unsigned NOT NULL AUTO_INCREMENT,
  `id_categoria` int unsigned NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `precio` decimal(10,2) NOT NULL,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `destacado` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_producto`),
  KEY `idx_categoria` (`id_categoria`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,1,'Polo Naruto Shippuden','Polo urbano con diseño exclusivo de Naruto Shippuden. 100% algodón, serigrafía de alta calidad.',59.90,NULL,48,1,1,'2026-05-18 09:44:53'),(2,1,'Hoodie Attack on Titan','Sudadera con capucha del Survey Corps. Diseño bordado en el pecho. Importada de Japón.',129.90,NULL,35,1,1,'2026-05-18 09:44:53'),(3,1,'Polo Demon Slayer Tanjiro','Polo con estampado de Tanjiro Kamado. Diseño exclusivo, tela premium.',64.90,89.90,40,0,1,'2026-05-18 09:44:53'),(4,1,'Polo One Piece Luffy','Polo con diseño de Monkey D. Luffy. Colores vivos, no destiñe.',54.90,NULL,60,0,1,'2026-05-18 09:44:53'),(5,1,'Hoodie Dragon Ball Goku','Sudadera con diseño de Goku Super Saiyan. Tela gruesa para el frío.',139.90,179.90,25,1,1,'2026-05-18 09:44:53'),(6,2,'Figura Demon Slayer Tanjiro 20cm','Figura de colección de Tanjiro Kamado. 20cm de alto, base decorativa incluida.',129.90,149.90,8,1,1,'2026-05-18 09:44:53'),(7,2,'Figura Naruto Uzumaki 15cm','Figura articulada de Naruto en modo Sage. 15cm, pintura detallada a mano.',89.90,NULL,12,0,1,'2026-05-18 09:44:53'),(8,2,'Figura Goku Ultra Instinct 25cm','Figura premium de Goku Ultra Instinct. 25cm, efectos de luz LED incluidos.',199.90,NULL,5,1,1,'2026-05-18 09:44:53'),(9,2,'Figura Nezuko Kamado 18cm','Figura de Nezuko con caja de bambú. 18cm, edición limitada.',149.90,NULL,6,0,1,'2026-05-18 09:44:53'),(10,3,'Mochila Attack on Titan','Mochila resistente con diseño del Survey Corps. Capacidad 25L.',89.90,NULL,20,1,1,'2026-05-18 09:44:53'),(11,3,'Llavero Naruto Set x5','Set de 5 llaveros metálicos de Naruto. Personajes: Naruto, Sasuke, Sakura, Kakashi, Itachi.',29.90,49.90,50,0,1,'2026-05-18 09:44:53'),(12,3,'Gorra Dragon Ball','Gorra con logo de Dragon Ball. Talla única ajustable. Importada.',39.90,NULL,25,0,1,'2026-05-18 09:44:53'),(13,4,'Zapatillas Dragon Ball Z','Zapatillas urbanas con diseño de Dragon Ball Z. Suela antideslizante.',149.90,NULL,28,1,1,'2026-05-18 09:44:53'),(14,4,'Zapatillas Naruto Akatsuki','Zapatillas con diseño de las nubes Akatsuki. Edición limitada.',159.90,199.90,22,0,1,'2026-05-18 09:44:53');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `provincias`
--

DROP TABLE IF EXISTS `provincias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provincias` (
  `id_provincia` int unsigned NOT NULL AUTO_INCREMENT,
  `id_departamento` int unsigned NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_provincia`),
  KEY `fk_prov_depto` (`id_departamento`),
  CONSTRAINT `fk_prov_depto` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `provincias`
--

LOCK TABLES `provincias` WRITE;
/*!40000 ALTER TABLE `provincias` DISABLE KEYS */;
INSERT INTO `provincias` VALUES (1,1,'Lima'),(2,1,'Callao'),(3,1,'Huaral'),(4,1,'Cañete'),(5,1,'Barranca'),(6,2,'Arequipa'),(7,2,'Camaná'),(8,2,'Islay'),(9,2,'Caylloma'),(10,3,'Cusco'),(11,3,'Urubamba'),(12,3,'Calca'),(13,3,'Canchis'),(14,4,'Trujillo'),(15,4,'Pacasmayo'),(16,4,'Chepén'),(17,4,'Ascope'),(18,5,'Piura'),(19,5,'Sullana'),(20,5,'Talara'),(21,5,'Paita'),(22,6,'Chiclayo'),(23,6,'Lambayeque'),(24,6,'Ferreñafe'),(25,7,'Huancayo'),(26,7,'Tarma'),(27,7,'Jauja'),(28,7,'Chanchamayo'),(29,8,'Ica'),(30,8,'Chincha'),(31,8,'Pisco'),(32,8,'Nasca'),(33,9,'Huaraz'),(34,9,'Santa'),(35,9,'Huari'),(36,9,'Casma'),(37,10,'Puno'),(38,10,'San Román'),(39,10,'Azángaro'),(40,10,'Chucuito');
/*!40000 ALTER TABLE `provincias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resenas`
--

DROP TABLE IF EXISTS `resenas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resenas` (
  `id_resena` int unsigned NOT NULL AUTO_INCREMENT,
  `id_producto` int unsigned NOT NULL,
  `id_usuario` int unsigned NOT NULL,
  `puntuacion` tinyint unsigned NOT NULL,
  `comentario` text COLLATE utf8mb4_general_ci,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_resena`),
  UNIQUE KEY `uq_resena` (`id_producto`,`id_usuario`),
  KEY `fk_res_usu` (`id_usuario`),
  KEY `idx_prod_res` (`id_producto`),
  CONSTRAINT `fk_res_prod` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `fk_res_usu` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `resenas_chk_1` CHECK ((`puntuacion` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resenas`
--

LOCK TABLES `resenas` WRITE;
/*!40000 ALTER TABLE `resenas` DISABLE KEYS */;
/*!40000 ALTER TABLE `resenas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int unsigned NOT NULL AUTO_INCREMENT,
  `id_provincia` int unsigned DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_general_ci,
  `rol` enum('admin','cliente') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cliente',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usu_prov` (`id_provincia`),
  CONSTRAINT `fk_usu_prov` FOREIGN KEY (`id_provincia`) REFERENCES `provincias` (`id_provincia`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,NULL,'Administrador','admin@shopperuvian.com','$2a$12$i5Haj9PVlBPVt6tWyxE95eK8096f1JvPmMqI2BilEYjN2B2BgbVi6',NULL,NULL,'admin',1,'2026-05-18 09:44:52');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id_wishlist` int unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned NOT NULL,
  `id_producto` int unsigned NOT NULL,
  `fecha_agregado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_wishlist`),
  UNIQUE KEY `uq_wish` (`id_usuario`,`id_producto`),
  KEY `fk_wish_prod` (`id_producto`),
  CONSTRAINT `fk_wish_prod` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE,
  CONSTRAINT `fk_wish_usu` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (1,1,1,'2026-05-28 01:57:30');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'shop_peruvian'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
