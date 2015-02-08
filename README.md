# PhpCurlExtended
HTTP/HTTPS Webrequests

The webrequest class is for sending HTTP/HTTPS requests. It supports multiple sessions and cookies. 


```php
//Example GET request use:

$request = new request();
$request->url = "http://www.example.com";
$request->get();


//Example Post request use:

$request = new request();
$request->url = "http://www.example.com";
$request->param = array(
    "Login" => "username",
    "Password" => "password12"
);
$request->post();


//Example Post With cookies use:

$request = new request();
$request->url = "http://www.example.com";
$request->cookie = true;
$request->cookieName = "myfirstcookie"
$request->param = array(
    "Login" => "username",
    "Password" => "password12"
);
$request->post();
```
