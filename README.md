# Dotclear REST API

Plugin to serve a Rest/JSON API on Dotclear.

This is a work in progress. API definitions and specifications are not stables. Its will be more exhaustive.

Real time code repository is https://bitbucket.org/gnieark/dc-rest-api

# Install:

Make a zip of this repository, rename it "rest.zip" and install it on your Dotclear Blog via the admin interface.

Or 

 hg clone https://bitbucket.org/gnieark/dc-rest-api /path/to/dotclear/plugins/rest

# Known bugs

If your dotclear use the query_strings URLS and there are some filters in query...
So URL is somthing like index.php?rest/{something}/{someting}?filter1=value1 
The integrated Swaggers API fails to generate the correct URL. However API Works.


# License

Dotclear rest/json plugin.

Copyright (C) [Gnieark](https://blog-du-grouik.tinad.fr/)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

# Third-party code

## Dotclear
The content management system Dotclear http://dotclear.org/ licensed under 
GNU GENERAL PUBLIC LICENSE Version 2, June 1991


## SwaggerUI
Documentation and the tool to test the API is a third party code integrated on this plugin:
Swagger-UI https://github.com/swagger-api/swagger-ui Licensed under the Apache License, Version 2.0
