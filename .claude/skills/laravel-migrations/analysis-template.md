# Migration Analysis Template

Use this template at the top of every data migration file to document your analysis.

## Basic Template

```php
/**
 * Migration: [Brief description of what this migration does]
 *
 * Current state (production):
 * - [Table/column that exists now]
 * - [Relationship that exists now]
 * - [Data constraint that exists now]
 *
 * Target state (after migration):
 * - [New table/column]
 * - [New relationship]
 * - [New data constraint]
 *
 * Data to preserve:
 * - [source] → [destination]
 * - [source] → [destination]
 *
 * Data transformations:
 * - [Explain any data format changes]
 *
 * Reasoning:
 * - [Why this change is needed]
 * - [What problem it solves]
 */
```

## Example 1: Single-Photo to Multi-Photo Posts

```php
/**
 * Migration: Convert single-photo structure to multi-photo posts
 *
 * Current state (production):
 * - photos table has: id, user_id, caption, is_completed, taken_at
 * - One photo = one post (1:1 relationship)
 * - URLs: /photo/{photo_id}
 *
 * Target state (after migration):
 * - posts table has: id, user_id, caption, is_completed, display_date
 * - photos table has: id, user_id, post_id, position, taken_at
 * - One post can have many photos (1:many relationship)
 * - URLs: /photo/{post_id} (same URL structure)
 *
 * Data to preserve:
 * - photos.id → posts.id (URL compatibility!)
 * - photos.user_id → posts.user_id
 * - photos.caption → posts.caption
 * - photos.is_completed → posts.is_completed
 * - photos.taken_at → posts.display_date
 * - photos.created_at → posts.created_at
 * - photos.updated_at → posts.updated_at
 *
 * Data transformations:
 * - taken_at → display_date (semantic rename)
 * - Add post_id = id (self-reference for first conversion)
 * - Add position = 0 (first photo in collection)
 *
 * Data to drop:
 * - photos.caption (moved to posts)
 * - photos.is_completed (moved to posts)
 *
 * Reasoning:
 * - Enable users to create multi-photo posts (like Instagram)
 * - Preserve existing URLs by keeping same IDs
 * - Maintain data integrity during restructuring
 */
```

## Example 2: Combining Name Fields

```php
/**
 * Migration: Combine first_name and last_name into full_name
 *
 * Current state (production):
 * - users table has: first_name, last_name (separate columns)
 * - ~500 users with names in database
 *
 * Target state (after migration):
 * - users table has: full_name (single column)
 * - Combined format: "First Last"
 *
 * Data to preserve:
 * - users.first_name + users.last_name → users.full_name
 *
 * Data transformations:
 * - CONCAT(first_name, ' ', last_name) → full_name
 * - Handle NULLs: COALESCE(first_name, '') for safety
 *
 * Data edge cases:
 * - Users with NULL first_name or last_name
 * - Users with middle names (currently in last_name)
 * - Users with prefixes/suffixes
 *
 * Reasoning:
 * - Simplify user name display logic
 * - Match third-party API expectations (full_name field)
 * - Reduce database columns
 *
 * Rollback considerations:
 * - Splitting full_name back to first/last is lossy
 * - Assumes format is "First Last" (may not work for all names)
 * - Document in down() method
 */
```

## Example 3: Adding Relationship Column

```php
/**
 * Migration: Add category_id foreign key to products
 *
 * Current state (production):
 * - products table has: id, name, category_name (string)
 * - ~1,000 products with text category names
 * - No relationship, just text matching
 *
 * Target state (after migration):
 * - categories table created with: id, name
 * - products table has: id, name, category_id (foreign key)
 * - Proper foreign key relationship
 *
 * Data to preserve:
 * - All product data
 * - Category associations (category_name → category_id)
 *
 * Data transformations:
 * - Create category records from unique category_name values
 * - Map products.category_name → products.category_id via lookup
 *
 * Migration steps:
 * 1. Create categories table
 * 2. Insert unique categories from products.category_name
 * 3. Add category_id column to products (nullable)
 * 4. Update products.category_id from category lookup
 * 5. Verify all products have category_id
 * 6. Drop products.category_name column
 * 7. Make category_id NOT NULL (after verification)
 *
 * Edge cases:
 * - Products with NULL category_name (assign to "Uncategorized")
 * - Products with invalid category names (log and assign default)
 * - Case sensitivity in category names (normalize to lowercase)
 *
 * Reasoning:
 * - Enable category management without updating all products
 * - Enforce referential integrity
 * - Improve query performance with indexed foreign key
 */
```

## Analysis Checklist

Before implementing migration, answer these questions:

### Data Mapping
- [ ] What columns/tables exist in production?
- [ ] Where does each piece of data need to move?
- [ ] Are there any data transformations needed?
- [ ] What is the format/type of source vs. destination?

### Constraints & Relationships
- [ ] Are there foreign keys involved?
- [ ] What happens to orphaned records?
- [ ] Should any columns be nullable during transition?
- [ ] Are there unique constraints to consider?

### Edge Cases
- [ ] What if source column has NULL values?
- [ ] What if destination already has data?
- [ ] How to handle duplicate data?
- [ ] What about invalid data formats?

### URL & API Compatibility
- [ ] Will this break existing URLs?
- [ ] Do we need to preserve IDs?
- [ ] Will API responses change format?
- [ ] Do external systems depend on this data structure?

### Rollback Considerations
- [ ] Can this migration be fully reversed?
- [ ] Will rollback lose any data?
- [ ] Are transformations reversible?
- [ ] Should we warn about rollback limitations?

### Production Impact
- [ ] How much data will be migrated?
- [ ] Will this migration take long to run?
- [ ] Should we run this during low-traffic hours?
- [ ] Do we need to put app in maintenance mode?

## Red Flags in Analysis

⚠️ **Warning signs that indicate high-risk migration:**

1. **Irreversible transformations**
   - Example: Storing only year from full date
   - Solution: Keep original data or document data loss

2. **Ambiguous data mapping**
   - Example: Not sure which column to use for display_date
   - Solution: Clarify requirements before coding

3. **Missing NULL handling**
   - Example: CONCAT without NULL checks
   - Solution: Use COALESCE or handle NULLs explicitly

4. **Large data volume**
   - Example: 1M+ rows to migrate
   - Solution: Consider batch processing or chunking

5. **Complex transformations**
   - Example: Parsing JSON, extracting substrings, regexp
   - Solution: Test thoroughly with production-like data

6. **Breaking changes**
   - Example: Changing column names that API uses
   - Solution: Coordinate with frontend/API consumers

## Documentation Checklist

Ensure your migration analysis includes:

- [ ] **What**: Clear description of the change
- [ ] **Why**: Reasoning for the change
- [ ] **Current state**: Accurate production schema
- [ ] **Target state**: Desired schema after migration
- [ ] **Data mapping**: Where each piece of data goes
- [ ] **Transformations**: Any data format changes
- [ ] **Edge cases**: NULL handling, invalid data
- [ ] **Constraints**: Foreign keys, unique constraints
- [ ] **Rollback notes**: Warnings about data loss
- [ ] **Testing plan**: How to verify success

## When to Get Additional Review

Require another developer to review migration analysis if:

- **High risk**: Migration affects critical data
- **Irreversible**: Rollback will lose data
- **Complex**: Multiple tables and relationships involved
- **Large scale**: Affects 10,000+ records
- **Production breaking**: Could cause downtime
- **First time**: You haven't done this type of migration before
