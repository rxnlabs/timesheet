import React, { useEffect } from 'react';
import { Link } from 'react-router';
import ArchiveLayout from '../layouts/ArchiveLayout';
import { useAppDispatch, useAppSelector } from '../hooks';
import { getLogWeeks, selectLogHistory, selectLogHistoryStatus } from '../slices/shift';

const ArchiveHome = () => {
  const dispatch = useAppDispatch();
  const logHistory = useAppSelector(selectLogHistory);
  const logHistoryStatus = useAppSelector(selectLogHistoryStatus);

  useEffect(() => {
    // properly handle cleanup in Redux by aborting since React 18+ mounts and unmounts a component on load. This causes the ajax request to load twice, which caused the
    // years and weeks data to appear twice on the archive page data to appear twice. This prevents the data from loading twice by handling
    // cleanup when the component is unmounted. This does not prevent the Ajax request from running twice but it does cleanup the data that is altered due to the API request.
    // see https://redux-toolkit.js.org/api/createAsyncThunk#canceling-while-running
    // see https://stackoverflow.com/questions/72238175/why-useeffect-running-twice-and-how-to-handle-it-well-in-react
    // see https://dev.to/maltoze/comment/19k5p
    const dispatchPromise = dispatch(getLogWeeks());
    return () => {
      dispatchPromise.abort();
    };
  }, []);

  const generateWeeksLayout = (year:number, weeksData: number[]) => {
    // sort weeks
    let weeks = [...weeksData];
    weeks.sort((a: number, b: number): number => a - b);
    // make sure weeks are unique
    let sortedWeeks: number[] = [...new Set<number>(weeks)];
    return sortedWeeks.map((week) => {
      return <li key={`${year}-${week}`}><Link to={`/archive/${year}/${week}`}>Week {week}</Link></li>;
    });
  };

  return (
    <ArchiveLayout>
      {(!(logHistory) || logHistory.length === 0) && <h2>No historical timesheets found.</h2>}
      {logHistory && logHistory.map((history) => {
        return (
          <section className="log-history" key={history.year}>
            <h2 className="log-history__heading">{history.year}</h2>
            <ol className="log-history__list">
              {generateWeeksLayout(history.year, history.weeks)}
            </ol>
          </section>
        );
      })}
    </ArchiveLayout>
  );
};

export default ArchiveHome;