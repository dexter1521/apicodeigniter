<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Developement.- Eduardo Marvil | eduardo.dritec@gmail.com | 7341160224
 * Date.- 30-Mar-2024
 * Description.- Modelo general para realizar operaciones basicas en la base de datos
 */

class General_model extends CI_Model
{

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Función auxiliar para construir una consulta básica SELECT
	 * @param string $table La tabla a consultar
	 * @param array $positions Las columnas a seleccionar (opcional, por defecto todas con *)
	 * @return object La instancia del Query Builder de CodeIgniter
	 * @access private
	 * @note Método interno usado por otros métodos de la clase
	 */
	private function _build_query($table, $positions = [])
	{
		$this->db->select($positions);
		$this->db->from($table);
		return $this->db;
	}

	/**
	 * Función para insertar un nuevo registro en la tabla especificada
	 * @param string $table La tabla donde insertar el registro
	 * @param array $datos Array asociativo con los datos a insertar (columna => valor)
	 * @return mixed El ID auto-generado del registro insertado, o false si no se pudo insertar
	 * @example insert_data('usuarios', ['nombre' => 'Juan', 'email' => 'juan@email.com'])
	 * @note Retorna false si la tabla no tiene campo auto-increment o si hubo un error
	 */
	public function insert_data($table, $datos)
	{
		$this->db->insert($table, $datos);
		$id = $this->db->insert_id();
		return ($id > 0) ? $id : false;
	}

	/**
	 * Función para obtener datos de una tabla con comportamiento dinámico según resultados
	 * @param string $table La tabla a consultar
	 * @param array $data Array asociativo con las condiciones WHERE (columna => valor)
	 * @param array $positions Array con las columnas a seleccionar
	 * @return array|null Array con todos los registros si hay múltiples resultados,
	 *                    array con un solo registro si hay uno, o null si no hay resultados
	 * @example query_data('usuarios', ['activo' => 1], ['id', 'nombre', 'email'])
	 * @note Comportamiento dinámico: retorna result_array() si >1 registro, row_array() si =1 registro
	 */
	public function query_data($table, $data, $positions)
	{
		$this->db->select($positions);
		$this->db->from($table);
		foreach ($data as $key => $val)
			$this->db->where($key, $val);
		$query = $this->db->get();
		// Verifica si hay más de un resultado
		if ($query->num_rows() > 1) {
			return $query->result_array(); // Devuelve todos los resultados como un arreglo
		} else {
			return $query->row_array(); // Devuelve el primer resultado como un arreglo
		}
	}

	/**
	 * Función para actualizar datos de una tabla
	 * @param string $table La tabla a actualizar
	 * @param array $datos Los datos a actualizar
	 * @param array $key Las condiciones para el WHERE
	 * @return bool True si la actualización fue exitosa, false en caso contrario
	 */
	public function update_data($table, $datos, $key)
	{
		$this->db->where($key);
		$update = $this->db->update($table, $datos);
		return ($update == true) ? true : false;
	}

	/**
	 * Función para eliminar datos de una tabla
	 * @param string $table La tabla de la cual eliminar
	 * @param array $datos Las condiciones para el WHERE
	 * @return bool True si la eliminación fue exitosa, false en caso contrario
	 */
	public function delete_data($table, $datos)
	{
		foreach ($datos as $key => $val)
			$this->db->where($key, $val);
		return $this->db->delete($table);
	}

	/**
	 * Función para obtener todos los registros de una tabla sin filtros
	 * @param string $table La tabla a consultar
	 * @param array $positions Array con las columnas a seleccionar (opcional, por defecto todas)
	 * @return array Array con todos los registros de la tabla como arrays asociativos
	 * @example get_all_records('usuarios') // Obtiene todos los usuarios
	 * @example get_all_records('usuarios', ['id', 'nombre']) // Solo columnas específicas
	 * @warning Usar con precaución en tablas grandes, considerar paginación
	 */
	public function get_all_records($table, $positions = [])
	{
		$query = $this->_build_query($table, $positions);
		return $query->get()->result_array();
	}

	/**
	 * Función para verificar si existe un registro en una tabla
	 * @param string $table La tabla a consultar
	 * @param array $data Las condiciones para el WHERE
	 * @return bool True si existe exactamente un registro, false en caso contrario
	 */
	public function validate_data($table, $data)
	{
		$this->db->where($data);
		$query = $this->db->get($table);
		return ($query->num_rows() === 1) ? true : false;
	}

	/**
	 * Función para contar los registros de una tabla
	 * @param string $table La tabla a consultar
	 * @return int El número total de registros en la tabla
	 */
	public function count_records($table)
	{
		return $this->db->count_all($table);
	}

	/**
	 * Función para contar los registros de una tabla con condiciones
	 * @param string $table La tabla a consultar
	 * @param array $data Las condiciones para el WHERE (opcional)
	 * @return int El número de registros que cumplen la condición
	 */
	public function count_records_where($table, $data)
	{
		if (!empty($data)) {
			$this->db->where($data);
		}
		return $this->db->count_all_results($table);
	}

	/**
	 * Función para obtener registros de una tabla con condiciones
	 * @param string $table La tabla
	 * @param mixed $conditions Las condiciones para el WHERE (array o string)
	 * @param array $positions Las columnas a seleccionar (opcional)
	 * @return array Los registros en formato array
	 */
	public function get_records_where($table, $conditions, $positions = [])
	{
		$query = $this->_build_query($table, $positions);
		if (is_array($conditions)) {
			$query->where($conditions);
		} else {
			$query->where($conditions);
		}
		return $query->get()->result_array();
	}

	/**
	 * Función para obtener registros con JOIN entre dos tablas
	 * @param string $select Las columnas a seleccionar (ej: 'tabla1.campo1, tabla2.campo2')
	 * @param string $table La tabla principal
	 * @param string $join_table La tabla a unir con JOIN
	 * @param string $join_condition La condición del JOIN (ej: 'tabla1.id = tabla2.tabla1_id')
	 * @param array|string|null $where Las condiciones WHERE (opcional)
	 * @return array Los registros resultado del JOIN como objetos
	 * @example get_with_join('u.nombre, p.titulo', 'usuarios u', 'posts p', 'u.id = p.usuario_id', ['u.activo' => 1])
	 */
	public function get_with_join($select, $table, $join_table, $join_condition, $where = NULL)
	{
		$this->db->select($select);
		$this->db->from($table);
		$this->db->join($join_table, $join_condition);

		if ($where != NULL) {
			$this->db->where($where);
		}

		$query = $this->db->get();
		return $query->result();
	}

	/**
	 * Función para buscar registros usando LIKE en las columnas especificadas
	 * @param string $table El nombre de la tabla a consultar
	 * @param array|string $conditions Un array asociativo de pares columna-valor para buscar con LIKE,
	 *                                 o una cadena con la condición completa (ej: "nombre LIKE '%juan%'")
	 * @param array $positions Las columnas a seleccionar (opcional, por defecto todas)
	 * @return array El conjunto de resultados como array de registros
	 * @example get_records_where_like('usuarios', ['nombre' => 'juan', 'email' => 'gmail'])
	 * @example get_records_where_like('usuarios', "nombre LIKE '%juan%' OR email LIKE '%gmail%'")
	 */
	public function get_records_where_like($table, $conditions, $positions = [])
	{
		$query = $this->_build_query($table, $positions);
		if (is_array($conditions)) {
			foreach ($conditions as $key => $value) {
				$query->like($key, $value);
			}
		} else {
			$query->like($conditions);
		}
		return $query->get()->result_array();
	}

	/**
	 * Función para obtener un registro específico por su ID
	 * @param string $table La tabla a consultar
	 * @param mixed $id El valor del ID del registro a buscar
	 * @param string $id_field El nombre del campo ID (por defecto 'id')
	 * @param array $positions Las columnas a seleccionar (opcional, por defecto todas)
	 * @return array|null El registro encontrado como array, o null si no existe
	 * @example get_by_id('usuarios', 123) // Busca por id = 123
	 * @example get_by_id('usuarios', 'juan@email.com', 'email') // Busca por email
	 */
	public function get_by_id($table, $id, $id_field = 'id', $positions = [])
	{
		$query = $this->_build_query($table, $positions);
		$query->where($id_field, $id);
		$result = $query->get();
		return $result->num_rows() > 0 ? $result->row_array() : null;
	}

	/**
	 * Función para obtener registros con paginación, filtros y ordenamiento
	 * @param string $table La tabla a consultar
	 * @param array $conditions Las condiciones WHERE como array asociativo (opcional)
	 * @param array $positions Las columnas a seleccionar (opcional, por defecto todas)
	 * @param int $limit El número máximo de registros a retornar (por defecto 10)
	 * @param int $offset El número de registros a omitir desde el inicio (por defecto 0)
	 * @param string|null $order_by El campo por el cual ordenar (opcional)
	 * @param string $order_direction La dirección del ordenamiento: 'ASC' o 'DESC' (por defecto 'ASC')
	 * @return array Los registros encontrados como array
	 * @example get_records_with_limit('usuarios', ['activo' => 1], [], 20, 40, 'fecha_creacion', 'DESC')
	 */
	public function get_records_with_limit($table, $conditions = [], $positions = [], $limit = 10, $offset = 0, $order_by = null, $order_direction = 'ASC')
	{
		$query = $this->_build_query($table, $positions);

		if (!empty($conditions)) {
			$query->where($conditions);
		}

		if ($order_by) {
			$query->order_by($order_by, $order_direction);
		}

		$query->limit($limit, $offset);
		return $query->get()->result_array();
	}

	/**
	 * Función para verificar si existe al menos un registro que cumpla las condiciones
	 * @param string $table La tabla a consultar
	 * @param array $data Las condiciones WHERE como array asociativo
	 * @return bool True si existe al menos un registro, false si no existe ninguno
	 * @example record_exists('usuarios', ['email' => 'juan@email.com', 'activo' => 1])
	 * @note Esta función es más útil que validate_data() para verificar existencia
	 */
	public function record_exists($table, $data)
	{
		$this->db->where($data);
		$query = $this->db->get($table);
		return $query->num_rows() > 0;
	}

	/**
	 * Función para insertar múltiples registros en una sola operación (más eficiente)
	 * @param string $table La tabla donde insertar los datos
	 * @param array $datos Array de arrays, donde cada sub-array contiene los datos de un registro
	 * @return bool|int True si la inserción fue exitosa, false en caso de error, 
	 *                  o el número de filas insertadas dependiendo de la configuración de CodeIgniter
	 * @example insert_batch('usuarios', [['nombre' => 'Juan'], ['nombre' => 'María'], ['nombre' => 'Pedro']])
	 * @note Más eficiente que múltiples llamadas a insert_data() para insertar varios registros
	 */
	public function insert_batch($table, $datos)
	{
		return $this->db->insert_batch($table, $datos);
	}

	/**
	 * Función para actualizar múltiples registros en una sola operación (más eficiente)
	 * @param string $table La tabla donde actualizar los datos
	 * @param array $datos Array de arrays, donde cada sub-array contiene los datos a actualizar
	 * @param string $where_key El nombre del campo que se usará como clave para identificar cada registro
	 * @return int El número de filas afectadas por la actualización
	 * @example update_batch('usuarios', [['id' => 1, 'nombre' => 'Juan'], ['id' => 2, 'nombre' => 'María']], 'id')
	 * @note Más eficiente que múltiples llamadas a update_data() para actualizar varios registros
	 * @note El campo especificado en $where_key debe estar presente en cada sub-array de $datos
	 */
	public function update_batch($table, $datos, $where_key)
	{
		return $this->db->update_batch($table, $datos, $where_key);
	}
}
