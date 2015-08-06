<?php

// This file is generated by Composer
require_once 'vendor/autoload.php';

@include_once('server.php');

include_once('dbconfig.php');
include_once('github.php');



$nick = $_REQUEST['user'];
if (! $nick) {
  $nick = 'deiu';
}


$uri = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

try {
  $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // sql to create table
  $sql = "select * from users where login = '$nick' ; ";

  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $user = $stmt->fetch();

}
catch(PDOException $e)
{
  //echo $sql . "<br>" . $e->getMessage();
}


try {
  $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // sql to create table
  $sql = "select * from webid where login = '$nick' ; ";

  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $webid = $stmt->fetch();

}
catch(PDOException $e)
{
  //echo $sql . "<br>" . $e->getMessage();
}



//print_r($row);

if (!$user) {

  try {
    $user = $client->api('user')->show($nick);

  }
  catch(Exception $e)
  {
    //error_log('api error for user : ' + $nick);
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600');//300 seconds
    exit;
        //echo $sql . "<br>" . $e->getMessage();
  }

  try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // sql to create table
    $sql = "insert into users values (NULL, '$nick', '$user[name]', '$user[email]', '$user[company]', '$user[location]', '$user[avatar_url]', '$user[blog]', NULL) ; ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

  }
  catch(Exception $e)
  {
    //echo $sql . "<br>" . $e->getMessage();
  }

} else {
}

try {
  $users = $client->api('user')->followers($nick);

}
catch(Exception $e)
{
  header('HTTP/1.1 503 Service Temporarily Unavailable');
  header('Status: 503 Service Temporarily Unavailable');
  header('Retry-After: 3600');//300 seconds
  exit;
  //echo $sql . "<br>" . $e->getMessage();
}



try {
  $keys = $client->api('user')->keys($nick);

}
catch(Exception $e)
{
  header('HTTP/1.1 503 Service Temporarily Unavailable');
  header('Status: 503 Service Temporarily Unavailable');
  header('Retry-After: 3600');//300 seconds
  exit;
  //echo $sql . "<br>" . $e->getMessage();
}





//echo "<h3>Profile</h3>";
//echo "<div>$user[login]</div>";
//echo "<div>$user[name]</div>";
//echo "<div>$user[blog]</div>";
//echo "<div><img src='$user[avatar_url]'/>";

//echo "<h3>Followers</h3>";
for($i=0; $i<sizeof($users); $i++) {
  $login = $users[$i]['login'];
  //echo "<div><a href='user.php?user=$login'>$login</a></div>";
}

$rank = sizeof($users) * 3;
if ($rank > 100) {
  $rank = 100;
}

$preferredURI;
if ($webid && $webid['preferredURI']) {
  $preferredURI = $webid['preferredURI'];

  try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // sql to create table
    $sql = "select l.*, w.codeRepository from ledger l inner join wallet w on w.uri = l.wallet where l.uri = '$preferredURI' ; ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $ledger = $stmt->fetch();

  }
  catch(PDOException $e)
  {
    echo $sql . "<br>" . $e->getMessage();
  }

}

if (isset($ledger) && $ledger['codeRepository']) {
  $arr = split('/', $ledger['codeRepository']);
  $len = sizeof($arr);
  $project = $arr[$len-2] . '/' . $arr[$len-1];
}


$bitcoin;
if ($webid && $webid['bitcoin']) {
  $bitcoin = $webid['bitcoin'];
}


$main = 'http://gitpay.org/' . $user['login'] . '#this';
$githubaccount = 'http://github.com/' . $user['login'];


$turtle = "<#this> a <http://xmlns.com/foaf/0.1/Person> ;\n";

if (isset($user['name'])) {
  $turtle .= "<http://xmlns.com/foaf/0.1/name> '$user[name]' ;\n";
}


if (isset($user['avatar_url'])) {
  $turtle .= "<http://xmlns.com/foaf/0.1/img> '$user[avatar_url]' ;\n";
}

$turtle .= "<http://xmlns.com/foaf/0.1/account> <https://github.com/$user[login]> .\n";

if (isset($preferredURI)) {
  $turtle .= "<#this> <http://www.w3.org/2002/07/owl#sameAs> <$preferredURI> .\n";
}

if (isset($user['blog'])) {
  $turtle .= "<#this> <http://www.w3.org/2000/01/rdf-schema#seeAlso> <$user[blog]> .\n";
}

if (isset($bitcoin)) {
  $turtle .= "<#this> <https://w3id.org/cc#bitcoin> <$bitcoin> .\n";
}

for($i=0; $i<sizeof($users); $i++) {
  $follows = $users[$i]['login'];
  $turtle .= "<http://gitpay.org/$follows#this> <http://rdfs.org/sioc/ns#follows>  <#this> .\n";
}

for($i=0; $i<sizeof($keys); $i++) {
  $key = $keys[$i]['key'];
  $id = $keys[$i]['id'];

  $command = "./convert.sh '$key'";
  $modulus = shell_exec ( $command );


  $turtle .= "<#this> <http://www.w3.org/ns/auth/cert#key> <#$id> .\n";
  $turtle .= "<#$id> a <http://www.w3.org/ns/auth/cert#RSAPublicKey> ; <http://www.w3.org/ns/auth/cert#modulus> '$modulus'^^<http://www.w3.org/2001/XMLSchema#hexBinary> ; <http://www.w3.org/ns/auth/cert#exponent> '65537'^^<http://www.w3.org/2001/XMLSchema#integer> .\n";

}


header('Access-Control-Allow-Origin : *');
header("Vary: Accept");
if (stristr($_SERVER["HTTP_ACCEPT"], "application/turtle")) {
  header("Content-Type: application/turtle");
  echo $turtle;
  exit;
}
if (stristr($_SERVER["HTTP_ACCEPT"], "text/turtle")) {
  header("Content-Type: text/turtle");
  echo $turtle;
  exit;
}



?>


<!doctype html>
<!--
  Material Design Lite
  Copyright 2015 Google Inc. All rights reserved.

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      https://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License
-->
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Decentralized payments for github projects">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gitpay - <?php echo $user['login'] ?></title>

    <!-- Add to homescreen for Chrome on Android -->
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="icon" sizes="192x192" href="images/touch/chrome-touch-icon-192x192.png">

    <!-- Add to homescreen for Safari on iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Material Design Lite">
    <link rel="apple-touch-icon-precomposed" href="apple-touch-icon-precomposed.png">

    <!-- Tile icon for Win8 (144x144 + tile color) -->
    <meta name="msapplication-TileImage" content="images/touch/ms-touch-icon-144x144-precomposed.png">
    <meta name="msapplication-TileColor" content="#3372DF">

    <!-- SEO: If your mobile URL is different from the desktop URL, add a canonical link to the desktop page https://developers.google.com/webmasters/smartphone-sites/feature-phones -->
    <!--
    <link rel="canonical" href="http://www.example.com/">
    -->

    <link href="https://fonts.googleapis.com/css?family=Roboto:regular,bold,italic,thin,light,bolditalic,black,medium&amp;lang=en" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="material.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
    #view-source {
      position: fixed;
      display: block;
      right: 0;
      bottom: 0;
      margin-right: 40px;
      margin-bottom: 40px;
      z-index: 900;
    }
    </style>
  </head>
  <body>
    <div class="demo-layout mdl-layout mdl-js-layout mdl-layout--fixed-drawer mdl-layout--fixed-header">
      <header class="demo-header mdl-layout__header mdl-color--white mdl-color--grey-100 mdl-color-text--grey-600">
        <div class="mdl-layout__header-row">
          <span class="mdl-layout-title"><?php echo $user['login'] ?></span>
          <div class="mdl-layout-spacer"></div>
          <div class="mdl-textfield mdl-js-textfield mdl-textfield--expandable">
            <label class="mdl-button mdl-js-button mdl-button--icon" for="search">
              <i class="material-icons">search</i>
            </label>
            <div class="mdl-textfield__expandable-holder">
              <input class="mdl-textfield__input" type="text" id="search" />
              <label class="mdl-textfield__label" for="search">Enter your query...</label>
            </div>
          </div>
          <button class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-button--icon" id="hdrbtn">
            <i class="material-icons">more_vert</i>
          </button>
          <ul class="mdl-menu mdl-js-menu mdl-js-ripple-effect mdl-menu--bottom-right" for="hdrbtn">
            <li class="mdl-menu__item">About</li>
            <li class="mdl-menu__item">Contact</li>
            <li class="mdl-menu__item">Legal information</li>
          </ul>
        </div>
      </header>
      <div class="demo-drawer mdl-layout__drawer mdl-color--blue-grey-900 mdl-color-text--blue-grey-50">
        <header class="demo-drawer-header">
          <img src="<?php echo $user['avatar_url'] ?>" class="demo-avatar">
          <div class="demo-avatar-dropdown">
            <span><?php if(isset($user['name'])) {echo $user['name'];} ?></span>
            <div class="mdl-layout-spacer"></div>
            <button id="accbtn" class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-button--icon">
              <i class="material-icons">arrow_drop_down</i>
            </button>
            <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect" for="accbtn">
              <li class="mdl-menu__item">hello@example.com</li>
              <li class="mdl-menu__item"><i class="material-icons">add</i>Add another account...</li>
            </ul>
          </div>
        </header>
        <nav class="demo-navigation mdl-navigation mdl-color--blue-grey-800">
          <a class="mdl-navigation__link" href=""><i class="mdl-color-text--blue-grey-400 material-icons">home</i>Home</a>
          <a class="mdl-navigation__link" href="<?php echo $user['login'] ?>/activity/"><i class="mdl-color-text--blue-grey-400 material-icons">people</i>Social</a>
          <div class="mdl-layout-spacer"></div>
          <a class="mdl-navigation__link" href=""><i class="mdl-color-text--blue-grey-400 material-icons">help_outline</i></a>
        </nav>
      </div>
      <main class="mdl-layout__content mdl-color--grey-100">
        <div class="mdl-grid demo-content">
          <div class="demo-charts mdl-color--white mdl-shadow--2dp mdl-cell mdl-cell--12-col mdl-grid">
            <svg fill="currentColor" width="200px" height="200px" viewBox="0 0 1 1" class="demo-chart mdl-cell mdl-cell--4-col mdl-cell--3-col-desktop">
              <use xlink:href="#piechart" mask="url(#piemask)" />
              <text x="0.5" y="0.5" font-family="Roboto" font-size="0.3" fill="#888" text-anchor="middle" dy="0.1"><?php echo $rank ?><tspan font-size="0.2" dy="-0.07">%</tspan></text>
            </svg>
            <h3>Gitpay Ranking <?php  if (isset($ledger) && $ledger['balance']) echo "<br><a class='mdl-color-text--blue-800' target='_blank' href='w/?walletURI=https:%2F%2Fgitpay.databox.me%2FPublic%2F.wallet%2Fgithub.com%2Flinkeddata%2FSoLiD%2Fwallet%23this&user=". urlencode($preferredURI) ."'>$ledger[balance] bits</a> - <a href='$project'>Project</a>" ; ?></h3>
          </div>
          <div class="demo-graphs mdl-shadow--2dp mdl-color--white mdl-cell mdl-cell--8-col">
            <h3>Followers</h3>

<?php
            for($i=0; $i<sizeof($users); $i++) {
              $login = $users[$i]['login'];
              echo "<div><a href='$login'>$login</a></div>";
            }
            ?>


            <svg fill="currentColor" viewBox="0 0 500 250" class="demo-graph">
              <use xlink:href="#chart"/>
            </svg>
            <svg fill="currentColor" viewBox="0 0 500 250" class="demo-graph">
              <use xlink:href="#chart"/>
            </svg>
          </div>
          <div class="demo-cards mdl-cell mdl-cell--4-col mdl-cell--8-col-tablet mdl-grid mdl-grid--no-spacing">
            <div class="demo-updates mdl-card mdl-shadow--2dp mdl-cell mdl-cell--4-col mdl-cell--4-col-tablet mdl-cell--12-col-desktop">
              <div class="mdl-card__title mdl-card--expand mdl-color--teal-300">
                <h2 class="mdl-card__title-text"><a class="mdl-color-text--blue-800" href="http://graphite.ecs.soton.ac.uk/browser/?uri=<?php echo $uri ?>">Linked Data</a></h2>
              </div>
              <div class="mdl-card__supporting-text mdl-color-text--grey-600">
                Webid <a href="<?php echo $main ?>"><?php echo $main ?></a>
              </div>
              <div class="mdl-card__supporting-text mdl-color-text--grey-600">
                Github <a rel="me" href="<?php echo $githubaccount ?>"><?php echo $githubaccount ?></a>
              </div>
              <div class="mdl-card__supporting-text mdl-color-text--grey-600">
                sameAs <a rel="me" href="<?php if (isset($preferredURI)) echo $preferredURI ?>"><?php if (isset($preferredURI))  echo $preferredURI ?></a>
              </div>
              <div class="mdl-card__supporting-text mdl-color-text--grey-600">
                seeAlso <a rel="me" href="<?php if (isset($user['blog'])) { echo $user['blog']; } ?>"><?php  if (isset($user['blog'])) { echo $user['blog']; } ?></a>
              </div>
              <div class="mdl-card__supporting-text mdl-color-text--grey-600">
                bitcoin <a rel="me" href="<?php if (isset($bitcoin)) echo $bitcoin ?>"><?php if (isset($bitcoin)) echo $bitcoin ?></a>
              </div>
              <div class="mdl-card__actions mdl-card--border">
                <a href="#" class="mdl-button mdl-js-button mdl-js-ripple-effect">Read More</a>
              </div>
            </div>
            <div class="demo-separator mdl-cell--1-col"></div>
            <div class="demo-options mdl-card mdl-color--deep-purple-500 mdl-shadow--2dp mdl-cell mdl-cell--4-col mdl-cell--3-col-tablet mdl-cell--12-col-desktop">
              <div class="mdl-card__supporting-text mdl-color-text--blue-grey-50">
                <h3>Location</h3>
                <ul>
                  <li>
                      <span><a class="mdl-color-text--white" target="_blank" href="http://www.geonames.org/search.html?q=<?php if (isset($user['location'])) { echo $user['location']; } ?>"><?php if (isset($user['location'])) { echo $user['location']; } ?></span>
                  </li>
                </ul>
              </div>
              <div class="mdl-card__actions mdl-card--border">
                <a href="#" class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-color-text--blue-grey-50">Change location</a>
                <div class="mdl-layout-spacer"></div>
                <i class="material-icons">location_on</i>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
      <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" style="position: fixed; left: -1000px; height: -1000px;">
        <defs>
          <mask id="piemask" maskContentUnits="objectBoundingBox">
            <circle cx=0.5 cy=0.5 r=0.49 fill="white" />
            <circle cx=0.5 cy=0.5 r=0.40 fill="black" />
          </mask>
          <g id="piechart">
            <circle cx=0.5 cy=0.5 r=0.5 />
            <path d="M 0.5 0.5 0.5 0 A 0.5 0.5 0 0 1 0.95 0.28 z" stroke="none" fill="rgba(255, 255, 255, 0.75)" />
          </g>
        </defs>
      </svg>
    <script src="material.min.js"></script>
  </body>
</html>
