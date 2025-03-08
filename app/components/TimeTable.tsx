import React, { Fragment, useEffect } from 'react';
import ShiftEntryForm from './ShiftEntryForm';
import { useShiftContext } from '../contexts/ShiftContext';

const TimeTable: React.Element = () => {
  const { shifts, refreshShifts, editShiftId, setEditShiftId } = useShiftContext();

  useEffect(() => {
    refreshShifts();
  }, []);

  const handleEditShiftClick = (id: string) => (event: React.MouseEvent<HTMLAnchorElement>): void => {
    event.preventDefault();
    setEditShiftId(id);
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
      {!(shifts) && <h1>Loading Hours Data...</h1>}
      {shifts && (
        <table border="1">
          <tbody>
            <tr>
              {['Day', 'Location', 'Clock-in', 'Clock-out', 'Total Minutes', 'Total Hours', 'Edit'].map((header) => {
                return <th key={header.toLowerCase()}>{header}</th>;
              })}
            </tr>
            {generateShiftRows(shifts)}
          </tbody>
        </table>
      )}
    </Fragment>
  );
};

export default TimeTable;