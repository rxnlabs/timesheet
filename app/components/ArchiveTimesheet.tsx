import React, { Fragment, useEffect, useState } from 'react';
import { useParams, useLocation } from 'react-router';
import TimeTable from './TimeTable';
import TotalHours from './TotalHours';
import { useAppDispatch, useAppSelector } from '../hooks';
import { getLogWeeks, getShifts, selectLogHistory, setHistoricalLogWeek, setHistoricalLogYear } from '../slices/shift';
import Pagination from './Pagination';
const ArchiveTimesheet = () => {
  let { year, week } = useParams();
  const dispatch = useAppDispatch();
  const logHistory = useAppSelector(selectLogHistory);
  const [previousWeek, setPreviousWeek] = useState('');
  const [nextWeek, setNextWeek] = useState('');

  if (!year || !week || isNaN(Number(year)) || isNaN(Number(week))) {
    return null;
  }

  useEffect(() => {
    const dispatchPromiseHistoryLogs = dispatch(getLogWeeks());
    const dispatchPromiseShifts = dispatch(getShifts({ year: parseInt(year), week: parseInt(week) }));

    return () => {
      dispatchPromiseHistoryLogs.abort();
      dispatchPromiseShifts.abort();
    };
  }, []);

  useEffect(() => {
    getNextWeekInHistory(logHistory);
  }, [logHistory]);

  const getNextWeekInHistory = (logHistory: Array<object>) => {
    let foundNextWeek = '';
    let foundPreviousWeek = '';

    for (let i = 0; i < logHistory.length; i++) {
      if (logHistory[i].year == year) {
        for (let j = 0; j < logHistory[i].weeks.length; j++) {
          if (logHistory[i].weeks[j] == week) {
            if (typeof logHistory[i].weeks[j+1] !== 'undefined') {
              foundNextWeek = logHistory[i].weeks[j+1];
            }

            if (typeof logHistory[i].weeks[j-1] !== 'undefined') {
              foundPreviousWeek = logHistory[i].weeks[j-1];
            }

            break;
          }
        }

        break;
      }
    }

    setPreviousWeek(foundPreviousWeek);
    setNextWeek(foundNextWeek);
  };

  return (
    <Fragment>
      <h1>Timesheet for {year}, Week {week}</h1>
      <TimeTable skipDefaultLoad={true}/>
      <TotalHours/>
      <ul className="pagination">
        {previousWeek && <li><Pagination link={`/archive/${year}/${previousWeek}`} type="previous">Week {previousWeek}</Pagination></li>}
        {nextWeek && <li><Pagination link={`/archive/${year}/${nextWeek}`} type="previous">Week {nextWeek}</Pagination></li>}
      </ul>
    </Fragment>
  );
};

export default ArchiveTimesheet;