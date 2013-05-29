# JSON Schema To ODM Legend

* Values which can be either a string (scalar) or a reference in the JSON schema
will be stored in ODM as a Hash.  ODM will automatically convert the value back
to a string when fetching, or return the hash.
* Values which can be a collection of either a concrete type
(instance, server, input, etc) or a reference to a concrete type, will be stored
in ODM as a Collection of Hashes.  Values which can be a single instance of either a
concrete type, or a reference to a concrete type will be stored in ODM as a Hash.
If the value is a reference to a concrete type,
the hash will be the "standard" hash with keys "ref" and "id".  If the value is a
concrete type the ODM definition will still store a reference, but the hash will
contain an extra key "nested" which indicates that when de-serializing the value
should be nested in the parent resource, rather than be a top level resource in
#/resources

# TODO

* elasticity_params/schedule/time, currently a string, rather than string_or_input.
Can it be a reference as well as having a regex pattern?