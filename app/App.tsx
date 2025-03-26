import React, { Fragment } from 'react';
import TimeTable from './components/TimeTable';
import ShiftEntryForm from './components/ShiftEntryForm';
import TotalHours from './components/TotalHours';
import Notification from './components/Notification';
import TimeHistoryForm from './components/TimeHistoryForm';
import { useAppSelector } from './hooks';
import { selectShiftWeek } from './slices/shift';

const App: React.FC = (): React.Element => {
  let shiftData:Array<object> = [];
  let shifts = [];
  let totalHours = 0;
  const selectedShiftWeek = useAppSelector(selectShiftWeek);

  // if there is no internet access, grab the shifts from localstorage
  /*if (!navigator.onLine && getShiftsFromLocalStorage()) {
    shiftData = getShiftsFromLocalStorage();
    shifts = shiftData.shifts;
    totalHours = shiftData.totalHours;
  }*/

  return (
    <Fragment>
      <h1>Timesheet</h1>
      <Notification/>
      <h2>See old timesheets</h2>
      <TimeHistoryForm/>
      {selectedShiftWeek === null &&
          <Fragment>
              <h2>Add new shifts</h2>
              <ShiftEntryForm/>
          </Fragment>}
      <TimeTable shifts={shifts}/>
      <TotalHours hours={totalHours}/>
    </Fragment>
  );
};

export default App;