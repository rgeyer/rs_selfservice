#!/usr/bin/env python

import sys
import json
import jsonschema

if len(sys.argv) != 3:
  print "usage: schema_validate.py /path/to/schema.json /path/to/validate.json"
  exit(1)

schema_file = open(sys.argv[1], 'r')
schema = json.load(schema_file)

to_validate_file = open(sys.argv[2], 'r')
to_validate = json.load(to_validate_file)

validator = jsonschema.Draft4Validator(schema)
errors = sorted(validator.iter_errors(to_validate), key=lambda e: e.path)
for error in errors:
  print(error.message)

if len(errors) > 0:
  exit(1)