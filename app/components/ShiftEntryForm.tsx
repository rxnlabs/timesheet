import React, { Fragment, useRef } from 'react';
import { useShiftContext } from '../contexts/ShiftContext';
import type { Shift } from '../contexts/ShiftContext';

const ShiftEntryForm: React.FC<Shift> = ({ id, day, location, clockIn, clockOut }) => {
  const { refreshShifts, setEditShiftId, setFormNotification } = useShiftContext();
  const formRef = useRef(null);
  const currentDay = day || new Date().toLocaleDateString('en-US', { weekday: 'long' });

  const handleSubmit = (event: React.FormEvent):void => {
    event.preventDefault(); // Prevent browser default form submission

    // Create FormData instance from the form
    const formData = new FormData(formRef.current);

    // Serialize FormData to URL-encoded format
    const queryString = new URLSearchParams(formData).toString();

    // Submit the serialized data via AJAX using fetch
    fetch('/api.php?endpoint=addtimesheetentry', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded', // PHP-compatible format
      },
      body: queryString, // Send serialized data as the body
    })
      .then((response) => response.json())
      .then((data) => {
        refreshShifts();
        setEditShiftId(null);
        setFormNotification({ 'message': data.message, 'type': 'success' });
      })
      .catch((error) => {
        setFormNotification({ 'message': error.message, 'type': 'error' });
      });
  };

  return (
    <Fragment>
      <div className="form-errors"></div>
      <form ref={formRef} onSubmit={handleSubmit}>
        <input type="hidden" name="id" id="id" value={id}/>
        <label htmlFor="day">Day</label>
        <select name="day" id="day" required defaultValue={currentDay}>
          <option value="" selected disabled>
                        Select a Day
          </option>
          {['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'].map((value) => {
            return <option value={value} selected={value === day} key={value}>{value}</option>;
          })}
        </select>
        <label htmlFor="location">Location</label>
        <select name="location" id="location" required>
          <option value="" selected disabled>
                        Select a Location
          </option>
          {['Helpdesk', 'TETC', 'Blackwell', 'Henson', 'UC', 'Fulton', 'Devilbiss', 'Parking Office'].sort().map((value) => {
            return <option value={value} selected={value === location} key={value}>{value}</option>;
          })}
        </select>
        <label htmlFor="clock-in">Clock-In</label>
        <input type="text" name="clock-in" id="clock-in" required defaultValue={clockIn}/>
        <label htmlFor="clock-out">Clock-Out</label>
        <input type="text" name="clock-out" id="clock-out" required defaultValue={clockOut}/>
        <button type="submit">Submit</button>
      </form>
    </Fragment>
  );
};

export default ShiftEntryForm;