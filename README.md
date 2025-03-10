# Timesheet

I recently rediscovered Timesheet while searching through an old external hard drive. This is one of the first fully functional applications I wrote in PHP in 2010 (likely PHP 5.2). Despite its simplicity, it remains one of my favorite projects because it solved a real-world problem I faced in college. It was straightforward, scrappy, and, most importantly, it worked. This project taught me two lessons I’ve carried throughout my career: solving real problems is at the heart of good development, and sometimes the cleanest, most effective solutions are the ones driven by practicality—not perfection (and occasionally, less-than-perfect aesthetics).

While studying, I worked two on-campus jobs and needed an efficient way to track my total weekly work hours to stay within the 40-hour limit shared between both jobs. Exceeding this limit often caused budget complications for one of the departments, which would become responsible for overtime pay. To address the issue and avoid potential conflicts, I built Timesheet to help me monitor my hours and manage my shifts effectively.

## Features

- Quickly track clock-in and clock-out times.
- Dropdown menu to select the lab location.
- Automatically calculate total hours worked.
- Store shift info in a lightweight `hours.txt` text file.
- Display a dynamic summary of total logged hours.

### How It Works

Timesheet was designed to run locally on a portable version of XAMPP using a USB drive. Here’s how it worked:

1. Launch the program directly from the USB drive at the start of a shift.
2. Enter the lab name, clock-in time, and clock-out time.
3. The program logs the shift data to a plain text file (`hours.txt`) and calculates total hours worked.
4. Once I reached the 40-hour limit, I would avoid clocking in for additional shifts to ensure compliance with job policies.
5. At the beginning of each new week, I would manually rename the `hours.txt` file to something like `hours-YYYY-MM-DD.txt` to preserve the records for that week.

### Example of `hours.txt`

Here’s an example of how the data was stored in the `hours.txt` file:

| Date       | Lab Name      | Clock-In Time | Clock-Out Time | Total Time |  
|------------|---------------|---------------|----------------|------------|  
| 2011-03-16 | Helpdesk      | 09:00 AM      | 12:00 PM       | 3 hours    |  
| 2011-03-16 | Library       | 01:00 PM      | 05:00 PM       | 4 hours    |  
| 2011-03-17 | Student Union | 10:00 AM      | 02:00 PM       | 4 hours    |  

This simple tabular format made the file easy to parse for both the program and manual reviews.

### Reflection on Improvements

One improvement I wish I had implemented at the time was automatically creating a new `hours.txt` file at the start of each week and saving (renaming) the old file automatically. This feature would have reduced the manual work required and made the program more user-friendly. While the manual renaming process worked, automating it would have been a meaningful enhancement to the system’s overall functionality.

Looking back, I can now identify areas where the program fell short, such as lacking proper security measures (e.g., input validation, data sanitation) and adhering to formatting or organizational best practices. Despite these shortcomings, it served its purpose and stands as a testament to my self-taught journey in programming—piecing together knowledge from library books I borrowed and online tutorials. It represents the point where I progressed beyond simply following instructions and started creating solutions of my own.

### Inspiration Behind the Project

As a computer lab technician, my job required balancing two different roles while performing tasks like:
- Maintaining the cleanliness of the lab.
- Enforcing rules, like restricting food or drinks.
- Helping students resolve hardware, software, or network issues.
- Monitoring and troubleshooting printers while keeping them stocked.
- Staying updated on network-related issues through internal communication systems.

Timesheet allowed me to seamlessly balance my hours between jobs while ensuring accuracy and accountability, even when I picked up extra shifts for coworkers. It provided the structure I needed to track my workload and meet my commitments without creating unnecessary administrative difficulties.

## Redevelopment Plan

To continue learning and exploring new technologies, I plan to use Timesheet as a foundation for experimenting with modern programming languages and frameworks. 

## Versions
- [original](https://github.com/rxnlabs/timesheet/tree/archive): The first version, built using PHP with HTML templates.
- [php+react](https://github.com/rxnlabs/timesheet/tree/php%2Breact): The initial refactor, featuring a React frontend and a modernized PHP backend.