#reConnect

Author: _Devimplode_

License: [CreativeCommons by-nc-sa](http://creativecommons.org/licenses/by-nc-sa/3.0/)

Description: 

This project contains some php classes to access several database systems with one object oriented interface

reConnect allows access to classic RDBMS and NoSQL-databases with one object oriented interface

Please look in index.php for a quick example.

##SQL to reConnect interface

```sql
INSERT INTO USERS VALUES(1,1)
```
```php
	$db->users->insert(array(1,1));
```

```sql
SELECT a,b FROM users
```
```php
	$db->users->select(array('a','b'));
```
```sql
SELECT a as 'key',b FROM users
```
```php
	$db->users->select(array('a'=>'key','b'));
```
```sql
SELECT * FROM users WHERE age=33
```
```php
	$db->users->where(array("age" => 33));
```
```sql
SELECT a,b FROM users WHERE age=33
```
```php
	$db->users->select(array('a','b'))->where(array("age" => 33));
```
```sql
SELECT a,b FROM users WHERE age=33 ORDER BY name
```
```php
	$db->users->select(array('a','b'))->where(array("age" => 33))->sort(array("name"));
```
```sql
SELECT * FROM users WHERE age>33
```
```php
	$db->users->where(array("age" => array('%gt' => 33)));
```
```sql
SELECT * FROM users WHERE age<33
```
```php
	$db->users->where(array("age" => array('%lt' => 33)));
```
```sql
SELECT * FROM users WHERE age <> 33
```
```php
	$db->users->where(array("age" => array('%ne' => 33)));
```
```sql
SELECT * FROM users WHERE name LIKE "%Joe%"
```
```php
	$db->users->where(array("name" => array('%match' => "/Joe/")));
```
```sql
SELECT * FROM users WHERE name LIKE "Joe%"
```
```php
	$db->users->where(array("name" => array('%match' => "/^Joe/")));
```
```sql
SELECT * FROM users WHERE age>33 AND age<=40
```
```php
	$db->users->where(array("age" => array('%gt' => 33, '%lte' => 40)));
```
```sql
SELECT * FROM users ORDER BY name DESC
```
```php
	$db->users->sort(array("name" => $db::DESC));
```
```sql
SELECT * FROM users WHERE a=1 and b='q'
```
```php
	$db->users->where(array("a" => 1, "b" => "q"));
```
```sql
SELECT * FROM users LIMIT 1
```
```php
	$db->users->where()->limit(1);
```
```sql
SELECT * FROM users LIMIT 10 OFFSET 20
SELECT * FROM users LIMIT 20, 10
```
```php
	$db->users->where()->limit(10)->offset(20);
```
```sql
SELECT * FROM users WHERE a=1 or b=2
```
```php
	$db->users->where(array('%or' => array(array("a" => 1), array("b" => 2))));
```
```sql
SELECT * FROM users WHERE a=1 or ( b=2 and a>=1)
```
```php
	$db->users->where(array('%or' => array(array("a" => 1), array(array("b" => 2),array("a"=>array("$gte"=>1))))));
```

```sql
EXPLAIN SELECT * FROM users WHERE z=3
```
```php
	$db->users->where(array("z" => 3))->explain()
```
```sql
SELECT DISTINCT last_name FROM users
```
```php
	$db->users->select('last_name')->distinct();
```
```sql
SELECT COUNT(*) FROM users
```
```php
	$db->users->count();
```
```sql
SELECT COUNT(*) FROM users where AGE > 30
```
```php
	$db->users->where(array("age" => array('%gt' => 30)))->count();
```
```sql
SELECT COUNT(AGE) from users
```
```php
	$db->users->where(array("age" => array('%exists' => true)))->count();
```

```sql
UPDATE users SET a=1,c='foo' WHERE b='q'
```
```php
	$db->users->update(array(array("a" => 1),array('c'=>'foo')))->where(array("b" => "q"));
```
```sql
UPDATE users SET a=a+2 WHERE b='q'
```
```php
	$db->users->update(array("a" => array('%inc' => 2)))->where(array("b" => "q"));
```

```sql
DELETE FROM users WHERE z="abc"
```
```php
	$db->users->remove()->where(array("z" => "abc"));
```
```sql
DELETE FROM users WHERE z>=10 LIMIT 20
```
```php
	$db->users->remove()->where(array("z" => array('%gte'=>10)))->limit(20);
```
