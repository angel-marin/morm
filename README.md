# morm
Mircro ORM
I usually make web porjects with Doctrine.
I like the sintax, but with small projects I don't need all the extra posibilities of Doctrine
So I decide create a Micro ORM so I can use a similar sintax but simplier.

INSTALATION
1. Just copy morm.php where you want in your project.
2. Yeah, just 1 step.

CONFIGURATION AND INITIALIZING
1. set the database params in an array
2. call static functio config with the name of the var you want use in your program and with de database params array.

complete EXAMPLE:
require_once 'your/lib/path/morm.php';
$dbParams = [
 'user' => 'database_user',
 'password' => 'database_pass',
 'dbname' => 'database_name'
];
Morm::config('morm_query',$dbParams);
//now you can use $morm_query like you entities manager var

EXTRA CONFIGURATIONS
Morm::config come with extra posibilities you can set with your $dbparams array:
host => default = localhost
charset => default = UTF8
driver => default = mysql //is based in PDO, so you can use it with other databases (not tested)
connect => default = true //if set false, you will need call conect() before creating the query

SINTAX
Select:
$morm_query->create()                     //set vars for a new query
->select('column_name')                   //OPTIONAL: by default setted at '*'
->from('table_name')                      //table of the database
->where('conditions')                     //OPTIONAL
->andWhere('conditions')                  //OPTIONAL: You need set where first if you want use and where
->orWhere('conditions')                   //OPTIONAL: You need set where first if you want use or where 
->groupBy('colum_name')                   //OPTIONAL
->having('conditions')                    //OPTIONAL
->orderBy('column_name ASC')              //OPTIONAL
->limit(number)                           //OPTIONAL: if only call ->limit() set limit to 1
->offset(number)                          //OPTIONAL
->procedure('conditions')                 //OPTIONAL (not finished)
->into('OUTFILE','filename'','charset')   //OPTIONAL (not tested)
->execute()                               //get query resutls as object
->getFirst()                              //OPTIONAL: after execute(), return the first row as object
->getLast()                               //OPTIONAL: after execute(), return the last row as object

Delete:
$morm_query->create()                     //set vars for a new query
->delete('table_name')                    //set table where you want delete rows
->where('conditions')                     //OPTIONAL
->andWhere('conditions')                  //OPTIONAL: You need set where first if you want use and where
->orWhere('conditions')                   //OPTIONAL: You need set where first if you want use or where 
->execute()                               //run query

Update:
$morm_query->create()                     //set vars for a new query
->update('table_name')                    //set table where you want update rows
->set('colum = value')                    //set what you want uptdate
->set('column1,column2[,columnN]', array('value1','value2' [,'valueN']))  //other form to call set()
->where('conditions')                     //OPTIONAL
->andWhere('conditions')                  //OPTIONAL: You need set where first if you want use and where
->orWhere('conditions')                   //OPTIONAL: You need set where first if you want use or where 
->execute()                               //run query

Insert:
