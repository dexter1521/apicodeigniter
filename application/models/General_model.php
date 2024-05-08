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
     * Función auxiliar para construir una consulta básica
     * @param string $table La tabla a consultar
     * @param array $positions Las columnas a seleccionar (opcional)
     * @return object La instancia de la consulta
     */
    private function _build_query($table, $positions = [])
    {
        $this->db->select($positions);
        $this->db->from($table);
        return $this->db;
    }

    /**
     * Función para insertar datos en una tabla
     * @param string $table La tabla
     * @param array $data Los datos a insertar
     * @return mixed El ID del registro insertado o false en caso de error
     */
    public function insert_data($table, $datos)
    {
        $this->db->insert($table, $datos);
        $id = $this->db->insert_id();
        return ($id > 0) ? $id : false;
    }

    /**
     * Funcion para obtener datos de una tabla
     * @param string $table
     * @param array $data
     * @param array $positions
     */
    public function query_data($table, $data, $positions)
    {
        $this->db->select($positions);
        $this->db->from($table);
        foreach ($data as $key => $val)
            $this->db->where($key, $val);
        $regresaDato = $this->db->get();
        return $regresaDato->result_array();
    }

    /**
     * Funcion para actualizar datos de una tabla
     * @param string $table
     * @param array $datos
     * @param array $key
     */
    public function update_data($table, $datos, $key)
    {
        $this->db->where($key);
        $update = $this->db->update($table, $datos);
        return ($update == true) ? true : false;
    }

    /**
     * Funcion para eliminar datos de una tabla
     * @param string $table
     * @param array $datos 
     */
    public function delete_data($table, $datos)
    {
        foreach ($datos as $key => $val)
            $this->db->where($key, $val);
        return $this->db->delete($table);
    }

    /**
     * Función para obtener todos los registros de una tabla
     * @param string $table La tabla
     * @param array $positions Las columnas a seleccionar (opcional)
     * @return array Los registros en formato array
     */
    public function get_all_records($table, $positions = [])
    {
        $query = $this->_build_query($table, $positions);
        return $query->get()->result_array();
    }

    /**
     * Funcion para verificar si existe un registro en una tabla
     * @param string $table
     * @param array $data
     */
    public function validate_data($table, $data)
    {
        $this->db->where($data);
        $query = $this->db->get($table);
        return ($query->num_rows() === 1) ? true : false;
    }

    /**
     * Funcion para contar los registros de una tabla
     * @param string $table
     */
    public function count_records($table)
    {
        return $this->db->count_all($table);
    }

    /**
     * Funcion para contar los registros de una tabla con condicion
     * @param string $table
     * @param array $data
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
     * Función para obtener registros mezclando dos tablas con condiciones
     * ('campo1, campo2', 'tabla1', 'tabla2', 'tabla1.id = tabla2.id', array('campo1' => 'valor1'));
     * @param string $table La tabla
     * @param mixed $conditions Las condiciones para el WHERE (array o string)
     * @param array $positions Las columnas a seleccionar (opcional)
     * @return array El registro en formato array
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
}
