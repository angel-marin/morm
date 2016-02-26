morm
-

### Micro ORM

I usually make web projects with Doctrine.
I like the sintax, but with small projects I don't need all the extra posibilities of Doctrine
So I decide create a Micro ORM so I can use a similar sintax but simpler.

### INSTALATION

1. Just copy morm.php where you want in your project.
2. Yeah, just 1 step.

### CONFIGURATION AND INITIALIZING
1. Set the database params in an array
2. Call static function config with the name of the var you want use in your program and with the database params array.

complete EXAMPLE:

    require_once 'your/lib/path/morm.php';

    $dbParams = [
      'user' => 'database_user',
      'password' => 'database_pass',
      'dbname' => 'database_name'
    ];

    Morm::config('morm_query',$dbParams);
    //now you can use $morm_query like you entities manager var


### EXTRA CONFIGURATIONS

`Morm::config` come with extra posibilities you can set with your `$dbparams` array:

    host => default = localhost
    charset => default = UTF8
    driver => default = mysql //is based in PDO, so you can use it with other databases (not tested)
    connect => default = true //if set false, you will need call conect() before creating the query

### SINTAX

#### Select:

    $morm_query->create()                       // set vars for a new query
      ->select('column_name[, column_nameN]')                   //OPTIONAL: by default setted at '*'
      ->from('table_name')                      // table of the database
      ->where('conditions')                     //OPTIONAL
      ->andWhere('conditions')                  //OPTIONAL: You need set where first if you want use and where. You can set this method several times
      ->orWhere('conditions')                   //OPTIONAL: You need set where first if you want use or where. You can set this method several times 
      ->groupBy('colum_name')                   //OPTIONAL
      ->having('conditions')                    //OPTIONAL
      ->orderBy('column_name ASC')              //OPTIONAL
      ->limit(number)                           //OPTIONAL: if only call ->limit() set limit to 1
      ->offset(number)                          //OPTIONAL
      ->procedure('conditions')                 //OPTIONAL (not finished)
      ->into('OUTFILE','filename'','charset')   //OPTIONAL (not tested)
      ->execute()                               //set end for optionals parameters
      ->getAlls()                               //get query results as object
      ->getFirst()                              //OPTIONAL: after execute(), return the first row as object
      ->getLast()                               //OPTIONAL: after execute(), return the last row as object
    
    NOTE:
    You can use $morm_query->create() with optinal ->select('column_name') or you can use $morm_query->select('column_name') without create()
    
#### Altenative Select methods

    $reg = $morm_query->find('id')->from('table');      // search 'id' in 'table' by primary key
    $reg = $morm_query->getTable('table')->find('id');  // search 'id' in 'table' by primary key (slower than previous method)

#### Delete:

    $morm_query->create()                       //OPTIONAL: set vars for a new query (just because Doctrine nostalgia)
      ->delete('table_name')                    //set table where you want delete rows
      ->where('conditions')                     //OPTIONAL
      ->andWhere('conditions')                  //OPTIONAL: You need set where first if you want use and where
      ->orWhere('conditions')                   //OPTIONAL: You need set where first if you want use or where 
      ->execute()                               //run query
      
    NOTE:
    You can use $morm_query->create() with optinal ->delete('table_name') or you can use $morm_query->delete('table_name') without create()

#### Update:

    $morm_query->create()                       //OPTIONAL: set vars for a new query (just because Doctrine nostalgia)
      ->update('table_name')                    //set table where you want update rows
      ->set('column = value')                   //set what you want uptdate
      ->set('column1,column2[,columnN]', array('value1','value2' [,'valueN']))  //other form to call set()
      ->where('conditions')                     //OPTIONAL
      ->andWhere('conditions')                  //OPTIONAL: You need set where first if you want use and where
      ->orWhere('conditions')                   //OPTIONAL: You need set where first if you want use or where 
      ->execute()                               //run query
      
    NOTE:
    You can use $morm_query->create() with optinal ->update('table_name') or you can use $morm_query->update('table_name') without create()

#### Insert:

    $reg=$morm_query->newItem('table_name');  //set the creation of a new item inside a table named 'table_name'
    $reg->column='value';                     //set value to a column
    $reg->save();                             //save the new item

    NOTE:
    after $reg->save() you can get $reg->primary_key_column, this return the autoincremental value of the primary_key
