import React, { Fragment } from 'react';
import Notification from '../components/Notification';
import ShiftEntryForm from '../components/ShiftEntryForm';
import TimeTable from '../components/TimeTable';
import TotalHours from '../components/TotalHours';
import { useAppSelector } from '../hooks';
import { selectShiftWeek } from '../slices/shift';
import PageLayout from '../layouts/PageLayout';

const Home = () => {
  let shiftData:Array<object> = [];
  let shifts: Array<object> = [];
  let totalHours = 0;
  const selectedShiftWeek = useAppSelector(selectShiftWeek);

  // if there is no internet access, grab the shifts from localstorage
  /*if (!navigator.onLine && getShiftsFromLocalStorage()) {
    shiftData = getShiftsFromLocalStorage();
    shifts = shiftData.shifts;
    totalHours = shiftData.totalHours;
  }*/

  return (
    <PageLayout>
      <h1>Timesheet</h1>
      <Notification />
      <ShiftEntryForm />
      <TimeTable shifts={shifts}/>
      <TotalHours hours={totalHours}/>
    </PageLayout>
  );
};

export default Home;