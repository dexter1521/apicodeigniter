<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Controlador de Clientes - EJEMPLO COMPLETO DE CRUD
 * 
 * Este controlador sirve como ejemplo de implementación completa de un CRUD
 * usando MY_Controller como base. Demuestra las mejores prácticas para:
 * - Autenticación automática
 * - Validación de datos
 * - Manejo de errores
 * - Respuestas estandarizadas
 * - Operaciones CRUD completas
 * 
 * @package    ApiCodeIgniter
 * @subpackage Controllers
 * @category   API
 * @author     Eduardo Marvil <emtv2126@gmail.com>
 * @version    1.0.0
 */
class Clientes extends MY_Controller
{
	/**
	 * Constructor del controlador
	 * 
	 * Inicializa el controlador padre e inicializa el modelo de clientes.
	 * 
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Clientes_model', 'clientes');
	}

	/**
	 * Endpoint de prueba para peticiones POST
	 * 
	 * @return void
	 */
	public function index_post()
	{
		$this->sendSuccessResponse(null, 'API de Clientes conectada correctamente');
	}

	/**
	 * Endpoint de prueba para peticiones GET
	 * 
	 * @return void
	 */
	public function index_get()
	{
		$this->sendSuccessResponse(null, 'API de Clientes conectada correctamente');
	}

	/**
	 * GET /clientes - Obtiene la lista de todos los clientes
	 * 
	 * Parámetros opcionales:
	 * - limit: Límite de resultados
	 * - offset: Offset para paginación
	 * - buscar: Término de búsqueda
	 * 
	 * @return void
	 */
	public function lista_get()
	{
		// Validar autenticación automáticamente
		if (!$this->requireAuthentication()) {
			return;
		}

		try {
			// Obtener parámetros de la URL
			$limit = $this->input->get('limit');
			$offset = $this->input->get('offset') ?: 0;
			$buscar = $this->input->get('buscar');

			// Si hay término de búsqueda, usarlo
			if ($buscar) {
				$clientes = $this->clientes->buscar($buscar);
				$mensaje = count($clientes) > 0 ? 
					"Se encontraron " . count($clientes) . " clientes" : 
					"No se encontraron clientes con ese término";
			} else {
				// Obtener todos los clientes
				$clientes = $this->clientes->obtener_todos($limit, $offset);
				$mensaje = count($clientes) > 0 ? 
					"Clientes obtenidos correctamente" : 
					"No hay clientes registrados";
			}

			if (count($clientes) > 0) {
				$this->sendSuccessResponse($clientes, $mensaje);
			} else {
				$this->sendErrorResponse($mensaje, 404);
			}

		} catch (Exception $e) {
			$this->sendErrorResponse('Error al obtener clientes: ' . $e->getMessage(), 500);
		}
	}

	/**
	 * GET /clientes/{id} - Obtiene un cliente específico por ID
	 * 
	 * @param int $id ID del cliente
	 * @return void
	 */
	public function detalle_get($id = null)
	{
		if (!$this->requireAuthentication()) {
			return;
		}

		if (!$id || !is_numeric($id)) {
			$this->sendErrorResponse('ID de cliente requerido y debe ser numérico', 400);
			return;
		}

		try {
			$cliente = $this->clientes->obtener_por_id($id);

			if ($cliente) {
				$this->sendSuccessResponse($cliente, 'Cliente encontrado');
			} else {
				$this->sendErrorResponse('Cliente no encontrado', 404);
			}

		} catch (Exception $e) {
			$this->sendErrorResponse('Error al obtener cliente: ' . $e->getMessage(), 500);
		}
	}

	/**
	 * POST /clientes - Crea un nuevo cliente
	 * 
	 * Cuerpo de la petición (JSON):
	 * {
	 *   "nombre": "string (requerido)",
	 *   "email": "string (requerido)",
	 *   "telefono": "string (opcional)",
	 *   "direccion": "string (opcional)"
	 * }
	 * 
	 * @return void
	 */
	public function crear_post()
	{
		if (!$this->requireAuthentication()) {
			return;
		}

		try {
			// Obtener datos del cuerpo de la petición
			$datos = json_decode($this->input->raw_input_stream, true);

			if (!$datos) {
				$this->sendErrorResponse('Datos JSON inválidos', 400);
				return;
			}

			// Crear cliente usando el modelo
			$resultado = $this->clientes->crear($datos);

			if ($resultado['exito']) {
				$this->sendSuccessResponse($resultado['cliente'], $resultado['mensaje'], 201);
			} else {
				$status = isset($resultado['errores']) ? 422 : 400;
				$data = isset($resultado['errores']) ? $resultado['errores'] : null;
				$this->sendErrorResponse($resultado['mensaje'], $status, $data);
			}

		} catch (Exception $e) {
			$this->sendErrorResponse('Error al crear cliente: ' . $e->getMessage(), 500);
		}
	}

	/**
	 * PUT /clientes/{id} - Actualiza un cliente existente
	 * 
	 * @param int $id ID del cliente a actualizar
	 * @return void
	 */
	public function actualizar_put($id = null)
	{
		if (!$this->requireAuthentication()) {
			return;
		}

		if (!$id || !is_numeric($id)) {
			$this->sendErrorResponse('ID de cliente requerido y debe ser numérico', 400);
			return;
		}

		try {
			// Obtener datos del cuerpo de la petición
			$datos = json_decode($this->input->raw_input_stream, true);

			if (!$datos) {
				$this->sendErrorResponse('Datos JSON inválidos', 400);
				return;
			}

			// Actualizar cliente usando el modelo
			$resultado = $this->clientes->actualizar($id, $datos);

			if ($resultado['exito']) {
				$this->sendSuccessResponse($resultado['cliente'], $resultado['mensaje']);
			} else {
				$status = isset($resultado['errores']) ? 422 : 
					($resultado['mensaje'] === 'Cliente no encontrado' ? 404 : 400);
				$data = isset($resultado['errores']) ? $resultado['errores'] : null;
				$this->sendErrorResponse($resultado['mensaje'], $status, $data);
			}

		} catch (Exception $e) {
			$this->sendErrorResponse('Error al actualizar cliente: ' . $e->getMessage(), 500);
		}
	}

	/**
	 * DELETE /clientes/{id} - Elimina un cliente (soft delete)
	 * 
	 * @param int $id ID del cliente a eliminar
	 * @return void
	 */
	public function eliminar_delete($id = null)
	{
		if (!$this->requireAuthentication()) {
			return;
		}

		if (!$id || !is_numeric($id)) {
			$this->sendErrorResponse('ID de cliente requerido y debe ser numérico', 400);
			return;
		}

		try {
			$resultado = $this->clientes->eliminar($id);

			if ($resultado['exito']) {
				$this->sendSuccessResponse(null, $resultado['mensaje']);
			} else {
				$status = $resultado['mensaje'] === 'Cliente no encontrado' ? 404 : 400;
				$this->sendErrorResponse($resultado['mensaje'], $status);
			}

		} catch (Exception $e) {
			$this->sendErrorResponse('Error al eliminar cliente: ' . $e->getMessage(), 500);
		}
	}
}
