<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Driver\OCI8;

use Doctrine\DBAL\Platforms\OraclePlatform;

/**
 * OCI8 implementation of the Connection interface.
 *
 * @since 2.0
 */
class OCI8Connection implements \Doctrine\DBAL\Driver\Connection
{
    /**
     * @var resource
     */
    protected $dbh;

    /**
     * @var int
     */
    protected $executeMode = OCI_COMMIT_ON_SUCCESS;

    /**
     * Create a Connection to an Oracle Database using oci8 extension.
     *
     * @param string $username
     * @param string $password
     * @param string $db
     */
    public function __construct($username, $password, $db, $charset = null, $sessionMode = OCI_DEFAULT, $persistent = false)
    {
        if (!defined('OCI_NO_AUTO_COMMIT')) {
            define('OCI_NO_AUTO_COMMIT', 0);
        }

        $this->dbh = $persistent
            ? @oci_pconnect($username, $password, $db, $charset, $sessionMode)
            : @oci_connect($username, $password, $db, $charset, $sessionMode);

        if ( ! $this->dbh) {
            throw OCI8Exception::fromErrorInfo(oci_error());
        }
    }

    /**
     * Create a non-executed prepared statement.
     *
     * @param  string $prepareString
     * @return OCI8Statement
     */
    public function prepare($prepareString)
    {
        return new OCI8Statement($this->dbh, $prepareString, $this);
    }

    /**
     * @param string $sql
     * @return OCI8Statement
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args[0];
        //$fetchMode = $args[1];
        $stmt = $this->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Quote input value.
     *
     * @param mixed $input
     * @param int $type PDO::PARAM*
     * @return mixed
     */
    public function quote($value, $type=\PDO::PARAM_STR)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        $value = str_replace("'", "''", $value);
        return "'" . addcslashes($value, "\000\n\r\\\032") . "'";
    }

    /**
     *
     * @param  string $statement
     * @return int
     */
    public function exec($statement)
    {
        $stmt = $this->prepare($statement);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return false;
        }

        OraclePlatform::assertValidIdentifier($name);

        $sql    = 'SELECT ' . $name . '.CURRVAL FROM DUAL';
        $stmt   = $this->query($sql);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result === false || !isset($result['CURRVAL'])) {
            throw new OCI8Exception("lastInsertId failed: Query was executed but no result was returned.");
        }

        return (int) $result['CURRVAL'];
    }

    /**
     * Return the current execution mode.
     */
    public function getExecuteMode()
    {
        return $this->executeMode;
    }

    /**
     * Start a transactiom
     *
     * Oracle has to explicitly set the autocommit mode off. That means
     * after connection, a commit or rollback there is always automatically
     * opened a new transaction.
     *
     * @return bool
     */
    public function beginTransaction()
    {
        $this->executeMode = OCI_NO_AUTO_COMMIT;
        return true;
    }

    /**
     * @throws OCI8Exception
     * @return bool
     */
    public function commit()
    {
        if (!oci_commit($this->dbh)) {
            throw OCI8Exception::fromErrorInfo($this->errorInfo());
        }
        $this->executeMode = OCI_COMMIT_ON_SUCCESS;
        return true;
    }

    /**
     * @throws OCI8Exception
     * @return bool
     */
    public function rollBack()
    {
        if (!oci_rollback($this->dbh)) {
            throw OCI8Exception::fromErrorInfo($this->errorInfo());
        }
        $this->executeMode = OCI_COMMIT_ON_SUCCESS;
        return true;
    }

    public function errorCode()
    {
        $error = oci_error($this->dbh);
        if ($error !== false) {
            $error = $error['code'];
        }
        return $error;
    }

    public function errorInfo()
    {
        return oci_error($this->dbh);
    }
    
    /**
     * Utiliza o recurso do Oracle que permite vincular informações do usuário da aplicação à sua sessão no banco de dados.
     * @param type $userId
     * @param type $userIP
     * @param type $module
     * @param type $action
     */
    public function setUserInformation($userId, $userIP = null, $module = null, $action = null) {
        oci_set_client_identifier($this->dbh, $userId);
        
        if ($userIP) {
             oci_set_client_info($this->dbh, $userIP);
        }
        
        if ($module) {
            oci_set_module_name($this->dbh, $module);
        }
        
        if ($action) {
            oci_set_action($this->dbh, $action);
        }
    }
}
