[production]
resources.cloudCredentials.rightscale.email       = ""
resources.cloudCredentials.rightscale.password    = ""
resources.cloudCredentials.rightscale.account_id  = 12345

resources.cloudCredentials.aws.access_key_id      = ""
resources.cloudCredentials.aws.secret_key         = ""

; These are the user account IDs for these clouds.  You can find them by going to the security group found at;
; Clouds -> (cloud of your choice) -> Security Groups -> default
; Then under the add rules section, there should be an "Owner" input.  The default value in that input is
; the correct value for this config file.  You can also add clouds not specified here
resources.cloudCredentials.owners[1875]           = "" ; Your account ID for Datapipe Hong Kong
resources.cloudCredentials.owners[1998]           = "" ; Your account ID for Datapipe London
resources.cloudCredentials.owners[1874]           = "" ; Your account ID for Datapipe New York Metro
resources.cloudCredentials.owners[1999]           = "" ; Your account ID for Datapipe Silicon Valley
resources.cloudCredentials.owners[2175]           = "" ; Your account ID for Google (which actually doesn't exist)