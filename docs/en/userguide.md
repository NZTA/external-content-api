## Overview
The main menu on the left has an item called External Content.

URL: `/admin/external-content/`

In there, there are multiple tabs across the top right for different levels of information. Alternatively, you can start at Application, click into Tolling, and drill down to Areas, Pages, and then Content.

The import and export functionality appears on the Content tab. You can import from a CSV. The replace data checkbox will delete everything before doing the import. Export will return the data in the same format.

## API
The API can be accessed at `/api/v1/externalcontent/ExternalContent`

You will be prompted to enter your CMS username and password.

The response format is XML by default but can be changed to JSON by specifying the ?format=json parameter.

There are three filter parameters available:
- applicationName
- areaName
- pageName
Each can be used to filter down to a subset of the data, for example ?pageName=tolling.content

## Groups
- Access to the API should be set up with an API user. This user should be in the External content API readers group, and have a long randomly generated password.
- Staff who are allowed to edit the data in the CMS must be in the External content API readers > External content API editors group.
