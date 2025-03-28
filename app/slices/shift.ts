import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import type { PayloadAction } from '@reduxjs/toolkit';
import type { RootState, AppThunk } from '../stores/ShiftStore';
import { useAppDispatch } from '../hooks';
import { act } from 'react';

/**
 * Represents a work shift with  properties for details ID, day, location, and clock-in/out times.
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
 * ShiftState is an interface representing the state structure for a shift management system.
 *
 * Properties:
 * - shifts: An array of Shift objects, representing the list of shifts.
 * - totalHours: A number indicating the total hours across all shifts.
 * - editShiftId: A string representing the ID of the shift currently being edited, or null if no shift is being edited.
 * - formNotification: An object containing information about notifications related to the form. It includes a message and a type, or null if there is no notification.
 * - timesheetStatus: A string that represents the current status of the timesheet. Possible values are 'idle', 'loading', 'loaded', or 'failed'.
 */
export interface ShiftState {
  shifts: Array<Shift>
  totalHours: number
  editShiftId: string | null
  formNotification: { message: string, type: string } | null,
  timesheetStatus: 'idle' | 'loading' | 'loaded' | 'failed',
  historicalLogYear: number | null,
  historicalLogWeek: number | null,
  logHistory: Array<object>,
  logHistoryStatus: 'idle' | 'loading' | 'loaded' | 'failed'
};

/**
 * Represents the initial state of a shift management system.
 *
 * @typedef {Object} ShiftState
 * @property {Array} shifts - An array that stores details of shifts.
 * @property {number} totalHours - The total hours calculated from all shifts.
 * @property {?string} editShiftId - The ID of the shift currently being edited, or null if no shift is selected for editing.
 * @property {?string|Object} formNotification - Notification message or object to represent the current state of the shift form.
 * @property {string} timesheetStatus - The current status of the timesheet, typically indicating the system's state (e.g., 'idle', 'loading', etc.).
 *
 * @type {ShiftState}
 */
const initialState: ShiftState = {
  shifts: [],
  totalHours: 0,
  editShiftId: null,
  formNotification: null,
  timesheetStatus: 'idle',
  historicalLogYear: null,
  historicalLogWeek: null,
  logHistory: [],
  logHistoryStatus: 'idle'
};

/**
 * Redux slice for managing shifts.
 */
export const shiftSlice = createSlice({
  name: 'shift',
  initialState,
  reducers: {
    setShifts: (state, action: PayloadAction<Array<Shift>>) => {
      state.shifts = action.payload;
    },
    editShift: (state, action: PayloadAction<string|null>) => {
      state.editShiftId = action.payload;
    },
    addFormNotification: (state, action: PayloadAction<object>) => {
      state.formNotification = { message: action.payload.message, type: action.payload.type };
    },
    setHistoricalLogYear: (state, action: PayloadAction<number|null>) => {
      state.historicalLogYear = action.payload;
    },
    setHistoricalLogWeek: (state, action: PayloadAction<number|null>) => {
      state.historicalLogWeek = action.payload;
    },
    setLogHistory: (state, action: PayloadAction<Array<object>>) => {
      state.logHistory = action.payload;
    }
  },
  extraReducers: builder => {
    builder
      .addCase(getShifts.pending, (state) => {
        state.timesheetStatus = 'loading';
      })
      .addCase(getShifts.fulfilled, (state, action) => {
        state.timesheetStatus = 'loaded';
        state.shifts = action.payload.timesheet;
        state.totalHours = action.payload.totalHours;
      })
      .addCase(getShifts.rejected, (state) => {
        state.timesheetStatus = 'failed';
      })
      .addCase(addShift.pending, (state) => {
        state.timesheetStatus = 'loading';
      })
      .addCase(addShift.fulfilled, (state, action) => {
        state.timesheetStatus = 'loaded';
      })
      .addCase(addShift.rejected, (state) => {
        state.timesheetStatus = 'failed';
        state.formNotification = { message: 'Error adding shift', type: 'error' };
      })
      .addCase(getLogWeeks.pending, (state) => {
        state.logHistoryStatus = 'loading';
      })
      .addCase(getLogWeeks.fulfilled, (state, action) => {
        state.logHistoryStatus = 'loaded';
        const payload = action.payload;
        payload.map((item: object) => {
          state.logHistory.push({ year: parseInt(item.year), weeks: item.weeks });
        });
      })
      .addCase(getLogWeeks.rejected, (state) => {
        state.logHistoryStatus = 'failed';
      });
  }
});

/**
 * Asynchronous Redux thunk action to fetch shift data from the server.
 *
 * This function sends a GET request to the specified endpoint to retrieve
 * timesheet data. If the request is successful, it returns the JSON-parsed
 * response. If an error occurs, it returns the error encountered during the
 * request.
 *
 */
export const getShifts = createAsyncThunk(
  'shift/getShiftsFetch',
  async (args: { year: number; week: number } | null, thunkAPI) => {
    try {
      let endpoint:string = '/api.php?endpoint=gettimesheet';

      if (args && Number.isInteger(args.year) && Number.isInteger(args.week)) {
        endpoint += `&year=${args.year}&week=${args.week}`;
      }

      const response = await fetch(endpoint, {
        method: 'GET',
      });

      if (thunkAPI.signal.aborted) {
        thunkAPI.dispatch(shiftSlice.actions.setShifts([]));
      }

      return await response.json();
    } catch (error) {
      throw error;
    }
  }
);

/**
 * Asynchronous thunk action to add a new shift entry by sending a POST request to the specified API endpoint.
 *
 * @function addShift
 * @param {Shift} shift - The shift object containing details of the shift to be added.
 * @returns {Promise<Object>} - The API response object in JSON format or an error object if the request fails.
 */
export const addShift = createAsyncThunk(
  'shift/addShiftFetch',
  async (shift: Shift) => {
    try {
      const serializedData = new URLSearchParams(shift).toString();
      const response = await fetch('/api.php?endpoint=addtimesheetentry', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: serializedData
      });

      return await response.json();
    } catch (error) {
      console.log(error);
      return error;
    }
  }
);

/**
 * getLogWeeks is an asynchronous Redux thunk action created using createAsyncThunk.
 * It is responsible for fetching the log weeks data from the server.
 * The function sends a GET request to the endpoint '/api.php?endpoint=getlogweeks'
 * and processes the JSON response.
 *
 * This thunk provides full lifecycle actions: pending, fulfilled, and rejected,
 * which can be used to handle the state of the API call in the Redux store.
 *
 * @constant
 * @type {AsyncThunk}
 */
export const getLogWeeks = createAsyncThunk(
  'shift/getLogWeeksFetch',
  async (arg, thunkAPI) => {
    try {
      //console.log(thunkAPI.signal);
      const response = await fetch('/api.php?endpoint=getlogweeks');

      if (thunkAPI.signal.aborted) {
        thunkAPI.dispatch(shiftSlice.actions.setLogHistory([]));
      }

      return await response.json();
    } catch (error) {
      console.log(error);
      return error;
    }
  }
);

// Selectors
export const selectShifts = (state: RootState) => state.shift.shifts;
export const selectTotalHours = (state: RootState) => state.shift.totalHours;
export const selectEditShiftId = (state: RootState) => state.shift.editShiftId;
export const selectTimesheetStatus = (state: RootState) => state.shift.timesheetStatus;
export const selectFormNotification = (state: RootState) => state.shift.formNotification;
export const selectShiftYear = (state: RootState) => state.shift.historicalLogYear;
export const selectShiftWeek = (state: RootState) => state.shift.historicalLogWeek;
export const selectLogHistory = (state: RootState) => state.shift.logHistory;
export const selectLogHistoryStatus = (state: RootState) => state.shift.logHistoryStatus;

export const { editShift, addFormNotification, setHistoricalLogYear, setHistoricalLogWeek } = shiftSlice.actions;
export default  shiftSlice.reducer;