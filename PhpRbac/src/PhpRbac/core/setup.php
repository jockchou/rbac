<?php
#TODO: test on sqlite

if ($adapter=="pdo_mysql")
{
	try {
        $dbOptions = array(
            PDO::ATTR_AUTOCOMMIT => 1,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET @@SESSION.sql_mode='NO_AUTO_VALUE_ON_ZERO,STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'",
        );
        Jf::$Db=new PDO("mysql:host={$host};dbname={$dbname};port={$port}",$user,$pass,$dbOptions);
	}
	catch (PDOException $e)
	{
		if ($e->getCode()==1049) //database not found
			installPdoMysql($host,$user,$pass,$dbname);
		else
			throw $e;
	}
}
elseif ($adapter=="pdo_sqlite")
{
	if (!file_exists($dbname))
		installPdoSqlite($host,$user,$pass,$dbname);
	else
		Jf::$Db=new PDO("sqlite:{$dbname}",$user,$pass,array(PDO::ATTR_AUTOCOMMIT => 1));
// 		Jf::$Db=new PDO("sqlite::memory:",$user,$pass);
}
else # default to mysqli
{
	@Jf::$Db=new mysqli($host,$user,$pass,$dbname);
    @Jf::$Db->autocommit(TRUE);
	if(jf::$Db->connect_errno==1049)
		installMysqli($host,$user,$pass,$dbname);
}
function getSqls($dbms)
{
	$sql=file_get_contents(dirname(dirname(dirname(__DIR__))) . "/database/{$dbms}.sql");
	$sql=str_replace("PREFIX_",Jf::tablePrefix(),$sql);
	return explode(";",$sql);
}
function installPdoMysql($host,$user,$pass,$dbname)
{
	$sqls=getSqls("mysql");
	$db=new PDO("mysql:host={$host};",$user,$pass);
	$db->query("CREATE DATABASE {$dbname}");
	$db->query("USE {$dbname}");
	if (is_array($sqls))
		foreach ($sqls as $query)
		$db->query($query);
	Jf::$Db=new PDO("mysql:host={$host};dbname={$dbname}",$user,$pass);
	Jf::$Rbac->reset(true);
}
function installPdoSqlite($host,$user,$pass,$dbname)
{
	Jf::$Db=new PDO("sqlite:{$dbname}",$user,$pass);
	$sqls=getSqls("sqlite");
	if (is_array($sqls))
		foreach ($sqls as $query)
		Jf::$Db->query($query);
	Jf::$Rbac->reset(true);
}
function installMysqli($host,$user,$pass,$dbname)
{
	$sqls=getSqls("mysql");
	$db=new mysqli($host,$user,$pass);
	$db->query("CREATE DATABASE {$dbname}");
	$db->select_db($dbname);
	if (is_array($sqls))
		foreach ($sqls as $query)
		$db->query($query);
	Jf::$Db=new mysqli($host,$user,$pass,$dbname);
	Jf::$Rbac->reset(true);
}
