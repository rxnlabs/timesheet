import React, { Fragment, useEffect, useState } from 'react';
import ShiftEntryForm from './ShiftEntryForm';
import { useShiftContext } from '../contexts/ShiftContext';


/**
 * Component that displays a table of shifts.
 *
 * Props:
 * - shifts: Array of shift objects. Each shift contains details such as day, location,
 *   clock-in/clock-out times, and total hours/minutes. By default, an empty array is used.
 */
const TimeTable: React.Element = ({ shifts = [] }) => {
  const { state, dispatch, fetchShifts } = useShiftContext();
  const [localShifts, setLocalShifts] = useState(shifts);

  useEffect(() => {
    if (!localShifts.length) {
      fetchShifts();
      setLocalShifts(state.shifts);
    }

  }, [state.shifts]);


  const handleEditShiftClick = (id: string) => (event: React.MouseEvent<HTMLAnchorElement>): void => {
    event.preventDefault();
    dispatch({ type: 'EDIT_SHIFT', payload: id });
  };

  const generateShiftRows: (shifts: Array<object>) => React.JSX.Element[] = (shifts: Array<object>) => {
    let shiftRows: React.JSX.Element[] = shifts.map((shift: object) => {
      let nonEditShiftRow = [shift.day, shift.location, shift.clockIn, shift.clockOut, shift.minutes, shift.hours].map((value) => {
        return <td key={value}>{value}</td>;
      });

      if (shift.id === state.editShiftId) {
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
      {!(localShifts) && <h1>Loading Hours Data...</h1>}
      {localShifts && (
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