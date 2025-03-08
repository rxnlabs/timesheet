import React from 'react';
import { ShiftProvider } from './contexts/ShiftContext';
import TimeTable from './components/TimeTable';
import ShiftEntryForm from './components/ShiftEntryForm';
import TotalHours from './components/TotalHours';
import Notification from './components/Notification';

const App: React.FC = (): React.Element => {
  return (
    <ShiftProvider>
      <h1>Timesheet</h1>
      <Notification/>
      <ShiftEntryForm/>
      <TimeTable/>
      <TotalHours/>
    </ShiftProvider>
  );
};

export default App;