import React from 'react';
import { useShiftContext } from '../contexts/ShiftContext';

const TotalHours: React.ElementType = ({ defaultHours = 0 }) => {
  const { totalHours } = useShiftContext();
  const displayHours = totalHours ? totalHours : defaultHours;

  return (
    <div className="total-hours">
      <h1>My total hours are: {displayHours}</h1>
    </div>
  );
};

export default TotalHours;