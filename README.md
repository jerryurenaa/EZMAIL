# EZMAIL (PHP Email Protocol) 

This SMTP module is still in its early development process.

report bugs or request implementation within this reponsitory.

## INSTALLATION

Configure the config file with your Mail server.

## Further Documentation

[Link to RFC0821](https://www.ietf.org/rfc/rfc0821.txt)

[Link to RFC0822](https://tools.ietf.org/html/rfc822)

[Link to RFC1869](https://tools.ietf.org/html/rfc1869)

[Link to RFC2045](https://tools.ietf.org/html/rfc2045)

[Link to RFC2821](https://www.ietf.org/rfc/rfc2821.txt)


## TODO

1-PIPELINE implementation\
2-Basic HTML Email template integration\
3-Auth using Token

## What will not be implemented!

1-SSL support. It has been replaced with the TLS protocol.\
2- Port 25 || 2525 support. not secure and many email clients are not supporting it either.


## Example of a success transaction using SMTP protocol

250 SERVERURL Hello [IPV6]\
220 2.0.0 SMTP server ready\
250 SERVERURL Hello [IPV6]\
334 Encrypted USERNAME\
334 Encrypted password\
235 2.7.0 Authentication successful\
250 2.1.0 Sender OK\
250 2.1.5 Recipient OK\
354 Start mail input; end with .\
250 2.0.0 OK 

Email sent successfully
