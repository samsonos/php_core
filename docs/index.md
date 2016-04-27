#SamsonPHP Core

##Generic Controller class requirements:
* I should be able to create my Controller class that will respond with action to router.
* To integrate my Controller class into system in some way I should extend ```\samsonphp\core\Module``` or ```\samsonphp\core\Service``` class.
* I should be able to write down all dependendencies that is needed for controller actions in constructor.
* Parent class dependencies should not avoid me creating custom constructor signature and inject them automatically through setters.
* I should be able to receive router call parameters throught action signature.
* I should be able to inject any dependency into controller action for using.
* System should follow middleware pattern, this will give ability for security and caching. 
* I should be able to configure my controller class, with support of requirmements.

