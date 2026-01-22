<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH . 'models/General_model.php');

/**
 * Modelo de Clientes
 * 
 * Maneja las operaciones CRUD esenciales para la tabla de clientes.
 * Extiende General_model para heredar operaciones básicas de base de datos optimizadas.
 * 
 * Funcionalidades implementadas:
 * - CRUD completo (crear, leer, actualizar, eliminar)
 * - Búsqueda por término en múltiples campos
 * - Validaciones de integridad (emails únicos)
 * - Paginación para listados
 * - Soft delete para eliminaciones
 * 
 * @package    ApiCodeIgniter
 * @subpackage Models
 * @category   Database
 * @author     Eduardo Marvil <emtv2126@gmail.com>
 * @version    2.1.0
 * @since      1.0.0
 * @updated    2025-07-05 - Limpieza y unificación con controlador
 */
class Clientes_model extends General_model
{
	/**
	 * Nombre de la tabla principal
	 * @var string
	 */
	protected $table = 'clientes';

	/**
	 * Campos permitidos para inserción/actualización
	 * @var array
	 */
	protected $allowed_fields = ['nombre', 'email', 'telefono', 'direccion', 'activo'];

	/**
	 * Campos requeridos para validación
	 * @var array
	 */
	protected $required_fields = ['nombre', 'email'];

	/**
	 * Constructor del modelo
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Obtiene todos los clientes activos
	 * 
	 * @param int|null $limit Límite de resultados (opcional)
	 * @param int $offset Offset para paginación (opcional)
	 * @return array Lista de clientes activos
	 */
	public function obtener_todos($limit = null, $offset = 0)
	{
		$conditions = ['activo' => 1];
		$fields = ['id', 'nombre', 'email', 'telefono', 'direccion', 'fecha_creacion'];

		if ($limit) {
			return $this->get_records_with_limit($this->table, $conditions, $fields, $limit, $offset, 'nombre', 'ASC');
		}

		return $this->get_records_where($this->table, $conditions, $fields);
	}

	/**
	 * Obtiene un cliente por ID
	 * 
	 * @param int $id ID del cliente a buscar
	 * @return array|null Datos del cliente o null si no existe o está inactivo
	 */
	public function obtener_por_id($id)
	{
		$conditions = ['id' => $id, 'activo' => 1];
		$fields = ['id', 'nombre', 'email', 'telefono', 'direccion', 'fecha_creacion', 'fecha_actualizacion'];

		return $this->get_records_where($this->table, $conditions, $fields)[0] ?? null;
	}

	/**
	 * Busca clientes por término en nombre, email o teléfono
	 * 
	 * @param string $termino Término de búsqueda (nombre, email o teléfono)
	 * @return array Lista de clientes que coinciden con el término
	 */
	public function buscar($termino)
	{
		// Usar el método heredado de General_model para búsquedas más simples
		if (empty(trim($termino))) {
			return [];
		}

		$this->db->select('id, nombre, email, telefono, direccion, fecha_creacion');
		$this->db->from($this->table);
		$this->db->where('activo', 1);
		$this->db->group_start();
		$this->db->like('nombre', $termino);
		$this->db->or_like('email', $termino);
		$this->db->or_like('telefono', $termino);
		$this->db->group_end();
		$this->db->order_by('nombre', 'ASC');

		$query = $this->db->get();
		return $query->result_array();
	}

	/**
	 * Crea un nuevo cliente
	 * 
	 * @param array $datos Datos del cliente
	 * @return array Resultado de la operación
	 */
	public function crear($datos)
	{
		// Validar campos requeridos
		$validacion = $this->validar_datos($datos);
		if (!$validacion['valido']) {
			return [
				'exito' => false,
				'mensaje' => 'Datos inválidos',
				'errores' => $validacion['errores']
			];
		}

		// Verificar que el email no esté duplicado
		if ($this->email_existe($datos['email'])) {
			return [
				'exito' => false,
				'mensaje' => 'El email ya está registrado'
			];
		}

		// Preparar datos para inserción
		$datos_insercion = $this->preparar_datos_insercion($datos);

		$id = $this->insert_data($this->table, $datos_insercion);

		if ($id) {
			return [
				'exito' => true,
				'mensaje' => 'Cliente creado exitosamente',
				'id' => $id,
				'cliente' => $this->obtener_por_id($id)
			];
		}

		return [
			'exito' => false,
			'mensaje' => 'Error al crear el cliente'
		];
	}

	/**
	 * Actualiza un cliente existente
	 * 
	 * @param int $id ID del cliente
	 * @param array $datos Datos a actualizar
	 * @return array Resultado de la operación
	 */
	public function actualizar($id, $datos)
	{
		// Verificar que el cliente existe
		$cliente_existente = $this->obtener_por_id($id);
		if (!$cliente_existente) {
			return [
				'exito' => false,
				'mensaje' => 'Cliente no encontrado'
			];
		}

		// Validar datos
		$validacion = $this->validar_datos($datos, $id);
		if (!$validacion['valido']) {
			return [
				'exito' => false,
				'mensaje' => 'Datos inválidos',
				'errores' => $validacion['errores']
			];
		}

		// Verificar email duplicado (excluyendo el actual)
		if (isset($datos['email']) && $this->email_existe($datos['email'], $id)) {
			return [
				'exito' => false,
				'mensaje' => 'El email ya está registrado'
			];
		}

		// Preparar datos para actualización
		$datos_actualizacion = $this->preparar_datos_actualizacion($datos);

		$actualizado = $this->update_data($this->table, $datos_actualizacion, ['id' => $id]);

		if ($actualizado) {
			return [
				'exito' => true,
				'mensaje' => 'Cliente actualizado exitosamente',
				'cliente' => $this->obtener_por_id($id)
			];
		}

		return [
			'exito' => false,
			'mensaje' => 'Error al actualizar el cliente'
		];
	}

	/**
	 * Elimina un cliente (soft delete)
	 * 
	 * @param int $id ID del cliente
	 * @return array Resultado de la operación
	 */
	public function eliminar($id)
	{
		$cliente = $this->obtener_por_id($id);
		if (!$cliente) {
			return [
				'exito' => false,
				'mensaje' => 'Cliente no encontrado'
			];
		}

		$eliminado = $this->update_data($this->table, [
			'activo' => 0,
			'fecha_eliminacion' => date('Y-m-d H:i:s')
		], ['id' => $id]);

		if ($eliminado) {
			return [
				'exito' => true,
				'mensaje' => 'Cliente eliminado exitosamente'
			];
		}

		return [
			'exito' => false,
			'mensaje' => 'Error al eliminar el cliente'
		];
	}

	/**
	 * Valida los datos del cliente
	 * 
	 * @param array $datos Datos a validar
	 * @param int|null $id_excluir ID a excluir de la validación (para actualizaciones)
	 * @return array Resultado de la validación
	 */
	private function validar_datos($datos, $id_excluir = null)
	{
		$errores = [];

		// Validar campos requeridos
		foreach ($this->required_fields as $campo) {
			if (!isset($datos[$campo]) || empty(trim($datos[$campo]))) {
				$errores[$campo] = "El campo {$campo} es requerido";
			}
		}

		// Validar email
		if (isset($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
			$errores['email'] = 'El formato del email no es válido';
		}

		// Validar longitud de campos
		if (isset($datos['nombre']) && strlen($datos['nombre']) > 100) {
			$errores['nombre'] = 'El nombre no puede exceder 100 caracteres';
		}

		if (isset($datos['telefono']) && strlen($datos['telefono']) > 20) {
			$errores['telefono'] = 'El teléfono no puede exceder 20 caracteres';
		}

		return [
			'valido' => empty($errores),
			'errores' => $errores
		];
	}

	/**
	 * Verifica si un email ya existe en la base de datos
	 * 
	 * @param string $email Email a verificar
	 * @param int|null $id_excluir ID a excluir de la búsqueda (para actualizaciones)
	 * @return bool True si el email existe, false en caso contrario
	 */
	private function email_existe($email, $id_excluir = null)
	{
		$conditions = ['email' => $email, 'activo' => 1];
		
		if ($id_excluir) {
			// Para exclusión usamos consulta manual ya que General_model no tiene operador !=
			$this->db->where('email', $email);
			$this->db->where('activo', 1);
			$this->db->where('id !=', $id_excluir);
			$query = $this->db->get($this->table);
			return $query->num_rows() > 0;
		}

		return $this->record_exists($this->table, $conditions);
	}

	/**
	 * Prepara datos para inserción
	 * 
	 * @param array $datos Datos originales
	 * @return array Datos preparados
	 */
	private function preparar_datos_insercion($datos)
	{
		$datos_preparados = [];

		foreach ($this->allowed_fields as $campo) {
			if (isset($datos[$campo])) {
				$datos_preparados[$campo] = trim($datos[$campo]);
			}
		}

		$datos_preparados['fecha_creacion'] = date('Y-m-d H:i:s');
		$datos_preparados['activo'] = 1;

		return $datos_preparados;
	}

	/**
	 * Prepara datos para actualización
	 * 
	 * @param array $datos Datos originales
	 * @return array Datos preparados
	 */
	private function preparar_datos_actualizacion($datos)
	{
		$datos_preparados = [];

		foreach ($this->allowed_fields as $campo) {
			if (isset($datos[$campo])) {
				$datos_preparados[$campo] = trim($datos[$campo]);
			}
		}

		$datos_preparados['fecha_actualizacion'] = date('Y-m-d H:i:s');

		return $datos_preparados;
	}
}
