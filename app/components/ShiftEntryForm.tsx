import React, { Fragment, useRef } from 'react';
import type {
  Shift } from '../slices/shift';
import {
  addShift,
  addFormNotification,
  getShifts,
  editShift,
  selectTimesheetStatus,
  selectEditShiftId
} from '../slices/shift';
import { useAppDispatch, useAppSelector } from '../hooks';

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
  const dispatch = useAppDispatch();
  const timesheetStatus = useAppSelector(selectTimesheetStatus);
  const editShiftId = useAppSelector(selectEditShiftId);
  const formRef = useRef<HTMLFormElement|null>(null);
  const currentDay = day || new Date().toLocaleDateString('en-US', { weekday: 'long' });

  const handleSubmit = async (event: React.FormEvent):Promise<void> => {
    event.preventDefault(); // Prevent browser default form submission
    if (formRef == null) {
      return;
    }
    // Create FormData instance from the form
    const formData = new FormData(formRef.current);
    const shiftData = Object.fromEntries(formData.entries());
    let shiftNotificationMessage = 'Shift added successfully';
    let shiftNotificationType = 'success';
    const addShiftResult = await dispatch(addShift(shiftData)).unwrap()
      .then((response) => {
        if (Object.hasOwn(response, 'message')) {
          shiftNotificationMessage = response.message;
        }

        if (Object.hasOwn(response, 'error')) {
          shiftNotificationType = 'error';
        } else {
          dispatch(getShifts());
        }

        if (editShiftId !== null) {
          shiftNotificationMessage = 'Shift updated successfully';
          dispatch(editShift(null));
        }

        dispatch(addFormNotification({
          message: shiftNotificationMessage,
          type: shiftNotificationType
        }));
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