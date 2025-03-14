import React, { Fragment, useEffect, useState } from 'react';
import ShiftEntryForm from './ShiftEntryForm';
import { useAppDispatch, useAppSelector } from '../hooks';
import { editShift, getShifts, selectEditShiftId, selectShifts, selectTimesheetStatus } from '../slices/shift';

/**
 * Component that displays a table of shifts.
 *
 * Props:
 * - shifts: Array of shift objects. Each shift contains details such as day, location,
 *   clock-in/clock-out times, and total hours/minutes. By default, an empty array is used.
 */
const TimeTable: React.Element = ({ shifts = [] }) => {
  const dispatch = useAppDispatch();
  const storeShifts = useAppSelector(selectShifts);
  const editShiftId = useAppSelector(selectEditShiftId);
  const timesheetStatus = useAppSelector(selectTimesheetStatus);
  const [localShifts, setLocalShifts] = useState(shifts);
  let headingMessage = 'No logged hours this week. Log some worked shifts.';

  switch (timesheetStatus) {
  case 'loading':
    headingMessage = 'Loading Hours Data...';
    break;
  case 'failed':
    headingMessage = 'Could not load logged shifts. Check log files to make sure a timesheet exists.';
    break;
  }


  useEffect(() => {
    if (!shifts || shifts.length === 0) {
      dispatch(getShifts());
    }
  }, [dispatch, shifts]);

  useEffect(() => {
    if (storeShifts && storeShifts.length > 0) {
      setLocalShifts(storeShifts);
    }
  }, [storeShifts]); // Dependency on the Redux store shifts


  const handleEditShiftClick = (id: string) => (event: React.MouseEvent<HTMLAnchorElement>): void => {
    event.preventDefault();
    dispatch(editShift(id));
  };

  const generateShiftRows: (shifts: Array<object>) => React.JSX.Element[] = (shifts: Array<object>) => {
    let shiftRows: React.JSX.Element[] = shifts.map((shift: object) => {
      let nonEditShiftRow = [shift.day, shift.location, shift.clockIn, shift.clockOut, shift.minutes, shift.hours].map((value) => {
        return <td key={value}>{value}</td>;
      });

      if (shift.id === editShiftId) {
        return (
          <tr key={shift.id} data-shift-id={shift.id}>
            <td colSpan="7">
              <ShiftEntryForm id={shift.id} day={shift.day} location={shift.location} clockIn={shift.clockIn} clockOut={shift.clockOut}/>
            </td>
          </tr>
        );
      } 
      return (
        <tr key={shift.id} data-shift-id={shift.id}>
          {nonEditShiftRow}
          <td>
            <a href="#" data-shift-id={shift.id} onClick={handleEditShiftClick(shift.id)}>Edit</a>
          </td>
        </tr>
      );
            
    });

    return shiftRows;
  };



  return (
    <Fragment>
      {(!(localShifts) || localShifts.length === 0) && <h2>{headingMessage}</h2>}
      {localShifts && localShifts.length > 0 && (
        <table border="1">
          <tbody>
            <tr>
              {['Day', 'Location', 'Clock-in', 'Clock-out', 'Total Minutes', 'Total Hours', 'Edit'].map((header) => {
                return <th key={header.toLowerCase()}>{header}</th>;
              })}
            </tr>
            { generateShiftRows(localShifts) }
          </tbody>
        </table>
      )}
    </Fragment>
  );
};

export default TimeTable;