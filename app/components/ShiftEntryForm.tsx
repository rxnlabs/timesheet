import React, { Fragment, useRef } from 'react';
import { useShiftContext } from '../contexts/ShiftContext';
import type { Shift } from '../contexts/ShiftContext';

/**
 * Component designed for adding or updating a shift.
 *
 * Props:
 * - `id` (string | undefined): The unique identifier of the shift entry. Optional and mainly used for editing an existing shift.
 * - `day` (string | undefined): The day of the shift. Optional. Defaults to the current weekday if unspecified.
 * - `location` (string | undefined): The location for the shift. Optional. Can be left empty initially for new entries.
 * - `clockIn` (string | undefined): The clock-in time for the shift. Optional.
 * - `clockOut` (string | undefined): The clock-out time for the shift. Optional.
 */
const ShiftEntryForm: React.FC<Shift> = ({ id, day, location, clockIn, clockOut }) => {
  const { state, dispatch, fetchShifts } = useShiftContext();
  const formRef = useRef(null);
  const currentDay = day || new Date().toLocaleDateString('en-US', { weekday: 'long' });

  const handleSubmit = async (event: React.FormEvent):Promise<void> => {
    event.preventDefault(); // Prevent browser default form submission

    // Create FormData instance from the form
    const formData = new FormData(formRef.current);

    // Serialize FormData to URL-encoded format
    const queryString = new URLSearchParams(formData).toString();

    // Submit the serialized data via AJAX using fetch
    const response = await fetch('/api.php?endpoint=addtimesheetentry', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: queryString, // Send serialized data as the body
    });

    const data = await response.json();

    if (!response.ok) {
      dispatch({
        type: 'SET_NOTIFICATION',
        payload: { message: `Error adding shift: ${data.message}`, type: 'error' }
      });
    } else {
      let notificationMessage = 'Shift added successfully';

      if (state.editShiftId) {
        dispatch({
          type: 'EDIT_SHIFT',
          payload: null
        });

        notificationMessage = 'Shift updated successfully.';
      } else {
        formRef.current.reset();
      }

      dispatch({
        type: 'ADD_SHIFT',
        payload: {
          shifts: data,
          formNotification: { message: notificationMessage }
        }
      });

      fetchShifts();
    }
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