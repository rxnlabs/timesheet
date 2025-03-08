# Timesheet PHP+React Branch Updates
- Rebuilt the frontend using React, replacing the HTML template.
- Used React Context API for managing global state and ensuring dynamic updates to components.
- Added the ability to edit an existing shift.
- Added TypeScript for type safety and reducing runtime errors.
- Rewrote the backend as a PSR-4 compliant PHP class.
- Added data sanitation and validation when inputting a time.
- Improved data handling with automatic weekly log generation and unique IDs for shifts.
- Added PHP_CodeSniffer to enforce PSR coding standards and maintain code quality.
- Added ESLint for linting and enforcing coding standards for JavaScript files.
## What stayed the same
- The core functionality of tracking clock-in and clock-out times and calculating total hours remains intact.
- Shift data is still stored in a `.txt` file.
- The UI design is very simple, displaying the shifts in a table and no CSS has been added.