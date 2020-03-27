# SameSiteCookieSetter
This PHP class enables samesite supported cookies by modifying header created by setcookie() function.  
As of php version 7.3.0, new signature of setcookie() function exists.  
To support all php versions, this class use same parameters of new setcookie() signature.
The browser agent is also checked against incompatible list of browsers.
