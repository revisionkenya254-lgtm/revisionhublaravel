# Education taxonomy API

The API keeps the original flat education-level and product fields for existing clients. New clients should use education families and `education_context`.

## Families

- `lower_education`: Pre-Primary, Lower Primary, Upper Primary, Junior School, Senior School CBC, and High School.
- `upper_education`: TVET, CDACC, Certificate, Diploma, Undergraduate, and Professional.
- `supporting_resources`: taxonomy roots that are not student education levels, such as Teacher Resources.

`GET /api/bootstrap/education-levels` returns the legacy flat `data` list and the new `groups` object. Each level has `family`, `profile`, and `architecture`.

`GET /api/bootstrap/education-taxonomy` retains its existing fields and adds `data.families`.

`GET /api/catalog/taxonomy/families/{family}` returns one family. The aliases `lower`, `upper`, and `support` are accepted. Add `?include_tree=1` to project all available higher-education profile trees.

`GET /api/catalog/taxonomy/levels/{level}` returns the complete branch for one school level or higher-education profile.

The existing `GET /api/catalog/taxonomy/profiles/{profile}` endpoint remains supported. Taxonomy responses include `taxonomy_revision`, `X-Taxonomy-Revision`, and `ETag`.

## Products

Product list and detail responses now include `education_context`. It provides one stable shape with the family and level, plus either school `grade_or_exam`/`subject` or higher-education owner/department/programme/periods/unit group/unit data.

Supported product-list query parameters:

- `education_family=lower|upper|support`
- `taxonomy_profile=cdacc|tvet|certificate|diploma|undergraduate|professional`
- `department_id`, `programme_id`, `unit_id`, `unit_group`
- `study_year`, `semester`, `module`, `stage`, `section`
- `grade`, `subject`

Existing `category`, `category_path`, `year`, pagination, and product-type filters are unchanged.
