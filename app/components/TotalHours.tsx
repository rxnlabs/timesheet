import React, { useEffect, useState } from 'react';
import { useShiftContext } from '../contexts/ShiftContext';

/**
 * Component that displays the total hours worked in the week.
 *
 *
 * @param {Object} props - The properties passed to the component.
 * @param {number} [props.hours=0] - The default or initial hours value if not provided by the context.
 * @returns {React.Element} A React component that displays the total hours in a styled container.
 */
const TotalHours: React.ElementType = ({ hours = 0 }) => {
  const { state } = useShiftContext();
  const [localHours, setLocalHours] = useState(hours);

  useEffect(() => {
    setLocalHours(state.totalHours|hours);
  }, [state.totalHours, hours]);

  return (
    <div className="total-hours">
      <h1>My total hours are: {localHours}</h1>
    </div>
  );
};

export default TotalHours;