<?php
session_start();
class Model
{
	public $conn;
	public $template;
	public $message;
	public $question;
	public $question_number;
	public $answers;
	public $responses;
	public $graph_data;

	public function __construct(){
        $dsn = 'Driver={MySQL ODBC 5.3 ANSI Driver};Server=localhost;Database=psip';
        $this->conn = odbc_connect($dsn, "root", "Bluethunder6!");
        $this->template = "login.php";
        $this->message = "";
	}
}

class View
{
	private $model;
	private $controller;

	public function __construct($controller, $model) {
		$this->controller = $controller;
		$this->model = $model;
	}

	public function output(){
		if (isset($_SESSION['user'])) $user = $_SESSION['user'];
		$message = $this->model->message;
		$question = $this->model->question;
		$answers = $this->model->answers;
		$question_number = $this->model->question_number;
		$responses = $this->model->responses;
		$graph_data = $this->model->graph_data;
		require_once ($this->model->template);
	}
}

class Controller
{
	private $model;

	public function __construct($model) {
		$this->model = $model;
	}

	public function auth() {
		if (!isset($_POST['user']) || !isset($_POST['pass'])) return; // form not filled out
		$user = $_POST['user'];
		$pass = $_POST['pass'];
		if (isset($_POST['signup'])) $this->signup($user, $pass); // hit the signup submit button on form
		else $this->login($user, $pass); //hit the login submit button on the form
	}

	private function login($user, $pass) {
		$query = "SELECT password FROM users WHERE name='".$user."';";
		$result = odbc_exec($this->model->conn, $query);
		if (odbc_fetch_row($result))
		{
			if ($pass === odbc_result($result, "password"))
			{
				$_SESSION['user'] = $user;
				$this->forward();
			}
			else $this->model->message = "Invalid password!";
		}
		else $this->model->message = "Invalid username!";
	}

	private function signup($user, $pass) 
	{
		$query = "SELECT name FROM users WHERE name = '".$user."';";
		$result = odbc_exec($this->model->conn, $query);
		if (odbc_fetch_row($result)) $this->model->message = "This username has already been taken!";
		else
		{
			$query = 'INSERT INTO users(name, password) VALUES ("'.$user.'","'.$pass.'");';
			$result = odbc_exec($this->model->conn, $query);
			if ($result) $this->model->message = "Signed up successfully!";
			else $this->model->message = "Something went wrong. Please try again.";
		}
	}

	private function forward()
	{
		if (!isset($_SESSION['user'])) return; // invalid access of this page
		$query = "SELECT count(*) as number FROM responses WHERE user = '".$_SESSION['user']."' AND date = '".date("Y-m-d")."';";
		$result = odbc_exec($this->model->conn, $query);
		if (odbc_fetch_row($result))
			$this->question(odbc_result($result, 'number')+1);
		else
			$this->question(1);
	}

	public function next(){
		if (!isset($_POST['answer'])) $this->forward(); // must have been forwarded, pass along
		else
		{
			$error = false;
			$question_number = $_GET['question'];
			$query = "SELECT question from questions ORDER BY id LIMIT ".($question_number-1).",1;";
			$result = odbc_exec($this->model->conn, $query);
			if (odbc_fetch_row($result))
			{
				$question = odbc_result($result, "question");
				$query = 'INSERT INTO responses(date, user, question, answer) VALUES ("'.date("Y-m-d").'","'.$_SESSION['user'].'","'.$question.'","'.$_POST['answer'].'");';
				$result = odbc_exec($this->model->conn, $query);
				if (!$result) $error = true;
			}
			else $error = true;
			if ($error) $this->model->message = "An error has occurred, please try again.";
			else $this->question($question_number+1);
		}
	}

	private function question($n)
	{
		$query = "SELECT question,answers from questions ORDER BY id LIMIT ".($n-1).",1;";
		$result = odbc_exec($this->model->conn, $query);
		if (odbc_fetch_row($result))
		{
			$this->model->question = odbc_result($result, "question");
			$this->model->answers = explode("|", odbc_result($result, "answers"));
			$this->model->question_number = $n;
			$this->model->template = "question.php";
		}
		else 
		{
			$this->model->template="results.php";
			$query = 'SELECT question,answer FROM responses where user="'.$_SESSION['user'].'" AND date="'.date("Y-m-d").'" ORDER BY id;';
			$this->model->responses = odbc_exec($this->model->conn, $query);
		}
	}

	public function results()
	{
		if (!isset($_POST['date']) || !isset($_POST['question'])) return; //form not filled out
		$date = implode("-", explode("/",$_POST['date']));
		$query = "SELECT question from questions ORDER BY id LIMIT ".($_POST['question']-1).",1;";
		$result = odbc_exec($this->model->conn, $query);
		if($result)
		{
			$this->model->question = odbc_result($result, 'question');
			$query = 'SELECT answer,count(*) as number from responses where question="'.$this->model->question.'" AND ';
			if ($_POST['user'] !== "all") $query = $query.' user="'.$_SESSION['user'].'" AND ';
			$query = $query."date >= '".$date."' GROUP BY answer;";
			$this->model->graph_data = odbc_exec($this->model->conn, $query);
			$this->model->template = "results.php";	

			$query = 'SELECT question,answer FROM responses where user="'.$_SESSION['user'].'" AND date="'.date("Y-m-d").'" ORDER BY id;';
			$this->model->responses = odbc_exec($this->model->conn, $query);
		}
	}

	public function signout()
	{
		unset($_SESSION['user']);
	}
}

$model = new Model();
$controller = new Controller($model);
$view = new View($controller, $model);

if (isset($_GET['action']) && !empty($_GET['action'])) {

	$action = $_GET['action'];
	if($action === "next" || $action === "auth" || $action === "results" || $action === "signout")
    	$controller->{$_GET['action']}();
}


echo $view->output();
?>

