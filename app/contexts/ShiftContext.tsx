import type { ReactNode } from 'react';
import React, { createContext, useContext, useReducer } from 'react';

/**
 * Represents a work shift with optional properties for details like ID, day, location, and clock-in/out times.
 *
 * @typedef {Object} Shift
 * @property {string} [id] - The unique identifier for the shift.
 * @property {string} [day] - The day of the shift, typically represented as a string (e.g., "Monday").
 * @property {string} [location] - The location where the shift takes place.
 * @property {string} [clockIn] - The clock-in time for the shift, formatted as a string.
 * @property {string} [clockOut] - The clock-out time for the shift, formatted as a string.
 */
export type Shift = {
    id?:string,
    day?: string,
    location?: string,
    clockIn?: string,
    clockOut?: string
}

/**
 * Context type for managing shift-related state and actions.
 *
 * This type includes the current state of shifts, a dispatch function for handling actions,
 * and a method for fetching shift data asynchronously.
 */
type ShiftContextType = {
  state: ShiftState;
  dispatch: React.Dispatch<Action>;
  fetchShifts: () => Promise<void>
}

/**
 * Represents the state of a shift management system.
 *
 * @typedef {Object} ShiftState
 * @property {Shift[]} shifts An array of Shift objects representing individual work shifts.
 * @property {number} totalHours The total number of hours accumulated across all shifts.
 * @property {string|null} editShiftId The identifier of the shift currently being edited, or null if no shift is being edited.
 * @property {{ message: string, type: string }|null} formNotification An object representing a notification message and its type, or null if no message is set.
 * @property {string} status The current status of the shift management system, typically indicating if it's idle, processing, or encountering an error.
 */
export type ShiftState = {
  shifts: Shift[];
  totalHours: number;
  editShiftId: string | null;
  formNotification: { message: string, type: string } | null;
  status: string;
}

/**
 * Actions that can be dispatched to manage application state.
 *
 */
type Action =
  | { type: 'LOADING' }
  | { type: 'FAILED', payload: string }
  | { type: 'GET_SHIFTS', payload: { shifts: Shift[], totalHours: number } }
  | { type: 'ADD_SHIFT', payload: Shift }
  | { type: 'GET_HOURS', payload: number }
  | { type: 'EDIT_SHIFT', payload: string | null }
  | { type: 'SET_NOTIFICATION', payload: { message: string, type: string } };

/**
 * Represents the initial state for managing shift data.
 *
 * @typedef {Object} ShiftState
 * @property {Array} shifts - An array to hold the list of shifts.
 * @property {number} totalHours - The total number of hours calculated from the shifts.
 * @property {string|null} editShiftId - The ID of the shift currently being edited, or null if no shift is being edited.
 * @property {string|null} formNotification - A notification message related to the form, or null if no notification exists.
 * @property {string} status - The current status of the shift state, typically indicating processing or initialization state.
 *
 * @type {ShiftState}
 */
const shiftInitialState: ShiftState = {
  shifts: [],
  totalHours: 0,
  editShiftId: null,
  formNotification: null,
  status: 'INITIALIZE'
};

/**
 * Reducer function that manages the state of shifts in the application.
 *
 * @param {ShiftState} state - The current state of shifts.
 * @param {Action} action - Action object containing type and optional payload to update the state.
 * @returns {ShiftState} The updated state based on the action type and its payload.
 */
const shiftReducer = (state: ShiftState, action: Action): ShiftState => {
  const defaultMessage = 'Shift added successfully.';
  const defaultType = 'success';

  switch (action.type) {
  case 'LOADING':
    return {
      ...state,
      status: 'LOADING'
    };
  case 'FAILED':
    return {
      ...state,
      status: 'FAILED'
    };
  case 'GET_SHIFTS':

    saveShiftsToLocalStorage({ shifts: action.payload.shifts, totalHours: action.payload.totalHours });

    return {
      ...state,
      status: 'SUCCESS',
      shifts: action.payload.shifts,
      totalHours: action.payload.totalHours
    };
  case 'ADD_SHIFT':
    return {
      ...state,
      status: 'SUCCESS',
      shifts: [...state.shifts, action.payload.shifts],
      formNotification: {
        message: typeof action.payload.formNotification.message === 'string' && action.payload.formNotification.message.length ? action.payload.formNotification.message : defaultMessage,
        type: typeof action.payload.formNotification.type === 'string' && action.payload.formNotification.type.length ? action.payload.formNotification.type : defaultType,
      }
    };
  case 'EDIT_SHIFT':
    return {
      ...state,
      editShiftId: action.payload
    };
  case 'GET_HOURS':
    return {
      ...state,
      status: 'SUCCESS',
      totalHours: action.payload
    };
  case 'SET_NOTIFICATION':
    return {
      ...state,
      formNotification: action.payload
    };
  default:
    return state;
  }
};

const ShiftContext = createContext<ShiftContextType |  undefined>(undefined);

/**
 * React context provider component that manages state and actions related to shifts.
 *
 * @param {Object} props - The props passed to the component.
 * @param {ReactNode} props.children - The child components that require access to the shift context.
 * @returns {React.Element} A ShiftContext provider that wraps the children components.
 */
export const ShiftProvider = ({ children }: {children: ReactNode}): React.JSX.Element => {
  const [state, dispatch] = useReducer(shiftReducer, shiftInitialState);

  const fetchShifts: Promise<void> = async () => {
    const response = await fetch('/api.php?endpoint=gettimesheet');
    const allowedResponseStatus = [200, 400];
    let result = { message: '', error: false };
    let data = {};
    if (allowedResponseStatus.includes(response.status)) {
      data = await response.json();
    }

    if (!response.ok) {
      if (Object.hasOwn(result, 'message')) {
        result = { message: data.message, error: true };
      } else {
        result = { message: response.statusText, error: true };
      }

      dispatch({
        type: 'FAILED',
        payload: `Error getting shifts: ${result}`
      });
      dispatch({
        type: 'SET_NOTIFICATION',
        payload: { message: `Error getting shifts: ${result}`, type: 'error' }
      });

    } else {
      dispatch({
        type: 'GET_SHIFTS',
        payload: {
          shifts: data.timesheet,
          totalHours: data.totalHours
        }
      });
    }
  };

  return (
    <ShiftContext.Provider value={{ state, dispatch, fetchShifts }}>
      {children}
    </ShiftContext.Provider>
  );
};

/**
 * A custom hook that provides access to the ShiftContext.
 *
 * This hook ensures that it is only used within a ShiftProvider,
 * throwing an error if it is accessed outside the appropriate context.
 *
 * @throws {Error} If used outside of a ShiftProvider.
 * @returns {ShiftContextType} The current value of the ShiftContext.
 */
export const useShiftContext: ShiftContextType = () => {
  const context = useContext(ShiftContext);
  if (context === undefined) {
    throw new Error('useShiftContext must be used within a ShiftProvider');
  }

  return context;
};

/**
 * Saves shift data to the local storage.
 *
 * @function saveShiftsToLocalStorage
 * @param {object} shiftData - An object representing the shift data to be saved.
 * @returns {void}
 */
export const saveShiftsToLocalStorage = (shiftData: object): void => {
  localStorage.setItem('this-week-shifts', JSON.stringify(shiftData));
};

/**
 * Get shift data stored in the local storage under the key.
 *
 * @returns {object|boolean} The shifts object if valid data is found, otherwise false.
 */
export const getShiftsFromLocalStorage = (): object => {
  let shifts: string|object|null = localStorage.getItem('this-week-shifts');

  if (shifts) {
    shifts = JSON.parse(shifts);

    if (Object.hasOwn(shifts, 'shifts')) {
      return shifts;
    }
  }

  return false;
};

/**
 * Asynchronous function to retrieve the current work week information.
 *
 * @type {Promise<string>}
 * @throws {Error} Throws an error if the fetch operation fails or the API response is invalid.
 */
export const getThisWorkWeek: Promise<string> = async() => {
  const response = await fetch('/api.php?endpoint=gethisworkweek');
  return await response.json();
};
