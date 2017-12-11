<?php
session_start();
date_default_timezone_set('America/New_York');

/* class that stores the data */
class Model
{
	public $conn; 		// database connection
	public $template;	// HTML template
	public $message;	// message: used for reporting errors and successes
	public $question;	// question being asked
	public $question_number;	// question number
	public $answers;	//possible answers to a question
	public $responses;	// response data in an array. Formatted question|answer
	public $graph_data;	// graph data in an array. Formatted answer|number

	public function __construct(){
		// get database data from Heroku
        $url = parse_url(getenv("CLEARDB_DATABASE_URL")); 

        // establish database connection
		$server = $url["host"];
		$username = $url["user"];
		$password = $url["pass"];
		$db = substr($url["path"], 1);
		$this->conn = new mysqli($server, $username, $password, $db);

		// initialize necessary variables
        $this->template = "login.php";
        $this->message = "";
        $this->graph_data = array();
	}
}

/* class that creates the UI depending on data stored in Model */
class View
{
	private $model; 	 // the model
	private $controller; // the controller

	public function __construct($controller, $model) {
		$this->controller = $controller;
		$this->model = $model;
	}

	// create the output the user sees
	public function output(){
		/* set variables to be used inside templates */
		if (isset($_SESSION['user'])) $user = $_SESSION['user'];
		$message = $this->model->message;
		$question = $this->model->question;
		$answers = $this->model->answers;
		$question_number = $this->model->question_number;
		$responses = $this->model->responses;	
		$graph_data = $this->model->graph_data;
		$show_graph = $this->model->show_graph;
		require_once ($this->model->template);
	}
}

/* class that reacts to user's actions with the view */
class Controller
{
	private $model; // the model

	public function __construct($model) {
		$this->model = $model;
	}

	// authenticate the user when either logging in or signing up
	public function auth() {
		if (!isset($_POST['user']) || !isset($_POST['pass'])) return; // form not filled out
		$user = $_POST['user'];
		$pass = $_POST['pass'];
		if (isset($_POST['signup'])) $this->signup($user, $pass); // hit the signup button on form
		else $this->login($user, $pass); //hit the login button on the form
	}

	// log the user in using username, $user, and password, $pass.
	private function login($user, $pass) {
		$query = "SELECT password FROM users WHERE name='".$user."';";
		$result = $this->model->conn->query($query);
		if ($row = $result->fetch_assoc())
		{
			// successful login 
			if ($pass === $row["password"])
			{
				$_SESSION['user'] = $user;
				$this->forward(); // send the user forward if they already completed the questions for today
			}
			else $this->model->message = "Invalid password!";
		}
		// no results
		else $this->model->message = "Invalid username!";
	}

	// create a new user with username, $user, and password, $pass.
	private function signup($user, $pass) 
	{
		// check constraints
		if (strlen($user) > 20 || strlen($pass) > 20) 
		{
			$this->model->message = "Please keep inputs below 20 characters";
			return;
		}
		// make sure user inputted data
		if ($user === "")
		{
			$this->model->message = "Please fill out the username and password to sign up";
			return;	
		} 
		$query = "SELECT name FROM users WHERE name = '".$user."';";
		$result = $this->model->conn->query($query);
		if ($result->num_rows === 0)  // no results
		{
			$query = 'INSERT INTO users(name, password) VALUES ("'.$user.'","'.$pass.'");';
			$result = $this->model->conn->query($query);
			if ($result) $this->model->message = "Signed up successfully!";
			else $this->model->message = "An error has occurred. Please try again.";
		}
		else $this->model->message = "This username has already been taken!";
	}

	// if the user has already answered questions today, send them to the first unanswered question or the results page.
	private function forward()
	{
		if (!isset($_SESSION['user'])) return; // invalid access of this page

		// find number of questions answered and load the next one
		$query = "SELECT count(*) as number FROM responses WHERE user = '".$_SESSION['user']."' AND date = '".date("Y-m-d")."';";
		$result = $this->model->conn->query($query);
		if ($row = $result->fetch_assoc()) $this->question($row['number']+1);
		else
		{
			$this->model->message = "An error has occurred. Please try again.";
			$this->signout();
		}
	}

	// load the next questions or the results page if there are no more questions
	public function next(){
		if (!isset($_POST['answer']) || !isset($_GET['question'])) $this->forward(); // must have been forwarded, pass along
		else
		{
			// insert answer of previous question
			$question_number = $_GET['question'];
			$query = "SELECT question from questions ORDER BY id LIMIT ".($question_number-1).",1;";
			$result = $this->model->conn->query($query);
			if ($row = $result->fetch_assoc())
			{
				$question = $row['question']; 
				$query = 'INSERT INTO responses(date, user, question, answer) VALUES ("'.date("Y-m-d").'","'.$_SESSION['user'].'","'.$question.'","'.$_POST['answer'].'");';

				if(!$this->model->conn->query($query)) $question_number--; // repeat the question on failure
				
				// load the next question
				$this->question($question_number+1);
			}
			else 
			{
				$this->model->message = "An error has occurred. Please try again!";
				$this->signout();
			}
		}
	}

	// load the $nth question (starting from 1)
	private function question($n)
	{
		$query = "SELECT question,answers from questions ORDER BY id LIMIT ".($n-1).",1;";
		$result = $this->model->conn->query($query);
		// if nth question exists
		if ($row = $result->fetch_assoc())
		{
			// load nth question and answers
			$this->model->question = $row["question"];
			$this->model->answers = explode("|", $row["answers"]);
			$this->model->question_number = $n;
			$this->model->template = "question.php";
		}
		// else view results
		else 
		{
			// put responses in an array
			$query = 'SELECT question,answer FROM responses where user="'.$_SESSION['user'].'" AND date="'.date("Y-m-d").'" ORDER BY id;';
			$result = $this->model->conn->query($query);
			$this->model->responses = array();
			for ($i=0; $row = $result->fetch_assoc(); $i++)
				$this->model->responses[$i] = $row['question']."|".$row['answer'];

			//view results
			$this->model->template="results.php";
		}
	}

	// show the user's responses for today and a graph showing the data the user requested
	public function results()
	{
		if (!isset($_POST['date']) || !isset($_POST['question'])) return; //form not filled out

		// get user's response data for today and put in an array
		$query = 'SELECT question,answer FROM responses where user="'.$_SESSION['user'].'" AND date="'.date("Y-m-d").'" ORDER BY id;';
		$result = $this->model->conn->query($query);
		for ($i=0; $row = $result->fetch_assoc(); $i++)
			$this->model->responses[$i] = $row['question']."|".$row['answer'];

		$date = implode("-", explode("/",$_POST['date']));
		$query = "SELECT count(*) as number from questions;";
		$result = $this->model->conn->query($query);
		if ($row = $result->fetch_assoc())
		{
			// check if the question number is valid
			$max_question = $row['number'];
			$question_number = $_POST['question'];
			if ($question_number > 0 && $question_number <= $max_question)
			{
				$query = "SELECT question from questions ORDER BY id LIMIT ".($_POST['question']-1).",1;";
				$result = $this->model->conn->query($query);
				// graph which question?
				if($row = $result->fetch_assoc())
				{
					$this->model->question = $row['question'];

					// get graph data and put in an array
					$query = 'SELECT answer,count(*) as number from responses where question="'.$this->model->question.'" AND ';
					if ($_POST['user'] !== "all") $query = $query.' user="'.$_SESSION['user'].'" AND ';
					$query = $query."date >= '".$date."' GROUP BY answer;";
					$result = $this->model->conn->query($query);
					$this->model->graph_data = array();
					for ($i=0; $row = $result->fetch_assoc(); $i++) { 
						$this->model->graph_data[$i] = $row['answer']."|".$row['number'];
					}

					// show results
					$this->model->template = "results.php";	
				}
				else
				{
					$this->model->message = "An error has occurred. Please try again!";
					$this->signout();
				}
			}
			else
				$this->model->template = "results.php";	
		} 
		else
		{
			$this->model->message = "An error has occurred. Please try again!";
			$this->signout();
		}
	}

	// sign the user out
	public function signout()
	{
		unset($_SESSION['user']);
	}
}

$model = new Model();
$controller = new Controller($model);
$view = new View($controller, $model);

// call the corresponding controller function for each action
if (isset($_GET['action']) && !empty($_GET['action'])) {

	$action = $_GET['action'];
	if($action === "next" || $action === "auth" || $action === "results" || $action === "signout")
    	$controller->{$_GET['action']}();
}

// display the output
echo $view->output();
?>

