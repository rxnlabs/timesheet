import React, { Fragment, useEffect, useState, useRef } from 'react';
import { useAppDispatch, useAppSelector } from '../hooks';
import {
  setHistoricalLogYear,
  setHistoricalLogWeek, getShifts, selectLogHistoryStatus, selectLogHistory, getLogWeeks,
} from '../slices/shift';

const TimeHistoryForm = () => {
  const dispatch = useAppDispatch();
  const currentYear = new Date().getFullYear();
  const currentWeek = new Date().getDay();
  const formRef = useRef<HTMLFormElement|null>(null);
  const logHistoryStatus = useAppSelector(selectLogHistoryStatus);
  const logHistory = useAppSelector(selectLogHistory);
  const [yearWeeks, setYearWeeks] = useState<number[]>([]);

  let headingMessage = 'No historical timesheets found.';

  switch(logHistoryStatus) {
  case 'loading':
    headingMessage = 'Loading historical logged timesheets';
    break;
  case 'failed':
    headingMessage = 'Could not load historical logged timesheets. Check log files to make sure that there are timesheets';
    break;
  }

  useEffect(() => {
    dispatch(getLogWeeks()).unwrap()
      .then((response) => {
        if (Array.isArray(response) && response.length > 0) {
          sortSetWeeks(response[0].weeks);
        }
      });
  }, []);

  const sortSetWeeks = (weeksData: number[]) => {
    let weeks = [...weeksData];
    weeks.sort((a: number, b: number): number => a - b);
    let sortedWeeks: number[] = [...new Set<number>(weeks)];
    setYearWeeks(sortedWeeks);
  };

  const handleYearChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
    const year = parseInt(event.target.value);
    for (let i = 0; i < logHistory.length; i++) {
      if (logHistory[i].year == year) {
        let weeks = [...logHistory[i].weeks];
        sortSetWeeks(weeks);
        break;
      }
    }
  };

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (formRef === null) {
      return;
    }

    const formData = new FormData(formRef.current);
    const year = parseInt(formData.get('year') as string);
    const week = parseInt(formData.get('week') as string);
    dispatch(setHistoricalLogYear(year));
    dispatch(setHistoricalLogWeek(week));
    dispatch(getShifts({ year: year, week: week }));
  };

  const generateYearOptions: (history: Array<object>) => React.JSX.Element[] = (history: Array<object>): React.JSX.Element[] => {
    let years: Array<number> = [];
    let options: React.JSX.Element[];
    history.map((data: object) => {
      years.push(data.year);
    });

    years.sort();
    // make sure the years are unique
    years = [...new Set(years)];

    options = years.map((year: number) => {
      return (<option value={year} key={year}>{year}</option>);
    });

    return options;
  };

  const generateWeekOptions: (weeks: Array<number>) => React.JSX.Element[] = (weeks: Array<number>): React.JSX.Element[] => {
    let options = weeks.map((week) => {
      return (<option value={week} key={week}>{week}</option>);
    });

    return options;
  };

  return(
    <Fragment>
      {(!(logHistory) || logHistory.length === 0) && <h2>{headingMessage}</h2>}
      {logHistory && logHistory.length > 0 && (
        <form className="time-history-form" onSubmit={handleSubmit} ref={formRef}>
          <select name="year" id="year" defaultValue={currentYear} onChange={handleYearChange}>
            <option value="" selected disabled>
              Select a Year
            </option>
            {generateYearOptions(logHistory)}
          </select>

          <select name="week" id="week" defaultValue={currentWeek}>
            <option value="" selected disabled>
              Select a Week
            </option>
            {generateWeekOptions(yearWeeks)}
          </select>

          <button type="submit">Get Old Shifts</button>

        </form>
      )}
    </Fragment>
  );
};

export default TimeHistoryForm;