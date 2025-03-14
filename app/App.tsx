import React, { Fragment } from 'react';
import TimeTable from './components/TimeTable';
import ShiftEntryForm from './components/ShiftEntryForm';
import TotalHours from './components/TotalHours';
import Notification from './components/Notification';

const App: React.FC = (): React.Element => {
  let shiftData:Array<object> = [];
  let shifts = [];
  let totalHours = 0;

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
      <ShiftEntryForm/>
      <TimeTable shifts={shifts}/>
      <TotalHours hours={totalHours}/>
    </Fragment>
  );
};

export default App;