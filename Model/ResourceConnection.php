<?php
/*
 *
 */
namespace FishPig\WordPress\Model;

/* Constructor Args */
use Magento\Framework\App\ResourceConnection\ConnectionFactory;
use FishPig\WordPress\Model\WPConfig;
use FishPig\WordPress\Model\Network;
use Magento\Store\Model\StoreManagerInterface;

class ResourceConnection
{
	/*
	 *
	 *
	 */
	protected $connectionFactory;
	
	/*
	 * @var 
	 */
	protected $tablePrefix = [];
	
	/*
	 * @var 
	 */
	protected $connection = [];
	
	/*
	 * @var Network
	 */
	protected $network;
	
	/*
	 * @var 
	 */
	protected $_tables = [];
	
	/*
	 * @var 
	 */
	public function __construct(
		    ConnectionFactory $connectionFactory, 
		             WPConfig $wpConfig, 
		              Network $network, 
		StoreManagerInterface $storeManager
	)
	{
		$this->connectionFactory = $connectionFactory;
		$this->network           = $network;
		$this->wpConfig          = $wpConfig;
		$this->storeManager      = $storeManager;
	}
	
	/*
	 *
	 *
	 */
	protected function loadByStoreId($storeId)
	{		
		$storeId = (int)$storeId;

		if (isset($this->connection[$storeId])) {
			return $this;	
		}		

		$this->connection[$storeId]  = false;
		$this->tablePrefix[$storeId] = $this->wpConfig->getData('DB_TABLE_PREFIX');

		$this->applyMapping([
			'wordpress_menu'              => 'terms',
			'wordpress_menu_item'         => 'posts',
			'wordpress_post'              => 'posts',
			'wordpress_post_meta'         => 'postmeta',
			'wordpress_post_comment'      => 'comments',
			'wordpress_post_comment_meta' => 'commentmeta',
			'wordpress_option'            => 'options',
			'wordpress_term'              => 'terms',
			'wordpress_term_relationship' => 'term_relationships',
			'wordpress_term_taxonomy'     => 'term_taxonomy',
			'wordpress_user'              => 'users',
			'wordpress_user_meta'         => 'usermeta',
		]);

		$this->connection[$storeId] = $this->connectionFactory->create([
      'host'     => $this->wpConfig->getData('DB_HOST'),
      'dbname'   => $this->wpConfig->getData('DB_NAME'),
      'username' => $this->wpConfig->getData('DB_USER'),
      'password' => $this->wpConfig->getData('DB_PASSWORD'),
      'active' => '1',	
		]);
	
		$this->connection[$storeId]->query('SET NAMES UTF8');

		if ($networkTables = $this->network->getNetworkTables()) {
			$this->applyMapping($networkTables);
		}
	}

	/*
	 *
	 *
	 * @param  array
	 * @return $this
	 */
	protected function applyMapping($tables)
	{
		$storeId = $this->getStoreId();
		
		$this->loadByStoreId($storeId);

		foreach($tables as $alias => $table) {
			$this->tables[$storeId][$alias] = $this->getTablePrefix() . $table;
		}
		
		return $this;
	}
	
	/*
	 * Convert a table alias to a full table name
	 *
	 * @param string $alias
	 * @return string
	 */
	public function getTable($alias)
	{
		$storeId = $this->getStoreId();

		$this->loadByStoreId($storeId);
		
		if (($key = array_search($alias, $this->tables[$storeId])) !== false) {
			if (strpos($key, 'wordpress_') === 0) {
				return $alias;
			}
		}
		
		return isset($this->tables[$storeId][$alias]) ? $this->tables[$storeId][$alias] : $this->getTablePrefix() . $alias;
	}

	/*
	 *
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->getConnection() !== false;
	}
	
	/*
	 *
	 *
	 * @return 
	 */
	public function getConnection()
	{
		$storeId = $this->getStoreId();
		
		$this->loadByStoreId($storeId);
		
		return isset($this->connection[$storeId]) ? $this->connection[$storeId] : false;
	}
	
	/*
	 *
	 *
	 * @return 
	 */
	public function getTablePrefix()
	{
		$storeId = $this->getStoreId();
		
		$this->loadByStoreId($storeId);

		return $this->tablePrefix[$this->getStoreId()];
	}
	
	/*
	 *
	 *
	 * @return int
	 */
	protected function getStoreId()
	{
		return (int)$this->storeManager->getStore()->getId();
	}
}
