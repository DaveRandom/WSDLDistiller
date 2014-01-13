WSDLDistiller
=============

Generates PHP code from WSDL documents

---

Look, OK, SOAP sucks. Don't use it. Seriously.
----------------------------------------------

Go make a better API.

If you *really* have to use it, this tool takes a WSDL document and generates some concrete classes to consume it. This
doesn't gain you much other than sensible auto-complete in your IDE, but at least that's a nugget of sanity in the
dung-heap of SOAP.

Note also that this is *very* incomplete, it's basically a feature sub-set of the things that I needed for a specific
project. However, whether by happy accident or because it's a common way for web services to be implemented, only very
minor tweaks were required in order to make it work with another project, so I threw it up on Github in case someone
else may find it useful. YMMV.
