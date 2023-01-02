<?php
    // get poll data from db
    $servername = $_ENV["SERVERNAME"];
    $username = $_ENV["USERNAME"];
    $password = $_ENV["DB_PASSWORD"];
    $dbname = $_ENV["DB_NAME"];

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // get id url param
    $id = $_GET["id"] || "1";

    $poll = $conn->query("SELECT * FROM polls WHERE id = $id");
    $poll = $poll->fetch_assoc();

    $options = $conn->query("SELECT * FROM options WHERE poll_id = $id");

    if (!$poll) {
        echo "Poll not found";
        // return 404
        http_response_code(404);
        return;
    }

    // get all data from https://webmention.io/api/mentions.jf2?target=https://jamesg.blog/
    $json = file_get_contents('https://webmention.io/api/mentions.jf2?target=' . $poll['url']);

    // decode the json
    $data = json_decode($json, true);

    $valid_options = $options->fetch_all(MYSQLI_ASSOC);
    $valid_options = array_column($valid_options, 'option_text');
    // autopopulate with 0 values
    $valid_option_counter = array_fill_keys($valid_options, 0);

    for ($i = 0; $i < count($data['children']); $i++) {
        if (!array_key_exists('content', $data['children'][$i])) {
            continue;
        }
        $option = $data['children'][$i]['content']['text'];
        // check if any $valid_options is in $option
        for ($j = 0; $j < count($valid_options); $j++) {
            if (strpos($option, $valid_options[$j]) !== false) {
                $valid_option_counter[$valid_options[$j]]++;
            }
        }
    }
?>

<main>
    <h1><?php echo $poll['title']; ?></h1>
    <h2>Results</h2>
    <style>
        meter {
            margin-right: 50px;
        }
    </style>
    <div id="results">
        <p>Number of votes: <?php echo count($data['children']); ?></p>
        <?php
            for ($i = 0; $i < count($valid_options); $i++) {
                echo '<meter value="' . $valid_option_counter[$valid_options[$i]] . '" min="0" max="' . count($data['children']) . '">' . $valid_option_counter[$valid_options[$i]] . '</meter><p style="display: inline">' . $valid_options[$i] . '</p>';
            }
        ?>
    </div>
    <h2>Vote</h2>
    <style>
        input {
            min-width: initial;
        }
    </style>
    <form action="app.php" method="post">
        <?php
            for ($i = 0; $i < count($valid_options); $i++) {
                echo '<input type="radio" name="option" value="' . $valid_options[$i] . '">' . $valid_options[$i] . '<br>';
            }
        ?>
        <input type="submit" value="Submit">
    </form>
    <details>
        <summary>
            Show Embed Code
        </summary>
        <pre>
        </pre>
    </details>
    <footer>
        <p>Powered by <a href="https://polls.jamesg.blog">IndieWebPoll</a>. Code <a href="https://github.com/capjamesg/indiewebpoll">open source on GitHub</a>.</p>
    </footer>
</main>