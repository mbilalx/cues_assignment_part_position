## Part Reordering System in Laravel

This project implements part reordering/sorting within an episode using Laravel queue jobs and pessimistic locking to
ensure data consistency during concurrent updates.

### Key Features

- **Queue Jobs:** The SortEpisodeParts job runs in the background to adjust the positions of parts after an update,
  ensuring a responsive user experience.
- **Pessimistic Locking:** We use lockForUpdate() within a database transaction to prevent other operations from
  modifying affected parts during reordering.
- **Timeout Handling:** The innodb_lock_wait_timeout is temporarily reduced to 5 seconds during the transaction to avoid
  long lock waits, improving performance under heavy load.
- **Error Handling:** Deadlocks and exceptions are caught and logged for easier debugging, ensuring system stability.

### How It Works

- **Position Update:** When a part's position is updated, a background job is dispatched to reorder the other parts.
- **Locking & Transactions:** Position updates are executed inside transactions with pessimistic locks to ensure data
  consistency.
- **Graceful Failure:** If a deadlock occurs, the error is caught, logged, and the lock timeout is reset to its original
  value.

## Technologies Used

- **Laravel Queues** for background processing.
- **Pessimistic Locking** with lockForUpdate().
- **MySQL** for managing transactional consistency.
- **Logging & Error Handling** for stable operations.
