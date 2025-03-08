import React from 'react';
import type { ReactNode } from 'react';
import { createContext, useContext, useState } from 'react';

export type Shift = {
    id?:string,
    day?: string,
    location?: string,
    clockIn?: string,
    clockOut?: string
}

type ShiftContextType = {
  shifts: Shift[];
  refreshShifts: () => Promise<boolean>; // Reload shifts from a endpoint
  totalHours: number; // Total hours worked across all shifts
  editShiftId: string|null // Current shift id being edited
  setEditShiftId: (id: string|null) => void; // Update the current shift being edited
  setHours: (hours: number) => void;
  formNotification: object;
  setFormNotification: (formNotification: object) => void;
}

const ShiftContext = createContext<ShiftContextType | undefined>(undefined);

export const ShiftProvider = ({ children }: {children: ReactNode}): React.Element => {
  const [shifts, setShifts] = useState<Shift[]>([]);
  const [totalHours, setHours] = useState<number>(0);
  const [editShiftId, setEditShiftId] = useState<string | null>(null);
  const [formNotification, setFormNotification] = useState<object>({});

  // Get data from endpoint
  const refreshShifts: Promise<boolean> = async () => {
    try {
      const response = await fetch('/api.php?endpoint=gettimesheet');
      const data = await response.json();
      setShifts(data.timesheet);
      setHours(data.totalHours);
      return true;
    } catch (error) {
      setFormNotification({ message: 'Error getting shifts. Please try again later.', type: 'error' });
      return false;
    }
  };

  return (
    <ShiftContext.Provider value={{ shifts, refreshShifts, editShiftId, setEditShiftId, totalHours, formNotification, setFormNotification }}>
      {children}
    </ShiftContext.Provider>
  );
};

// Create a custom hook to use the ShiftContext
export const useShiftContext = (): ShiftContextType => {
  const context = useContext(ShiftContext);
  if (context === undefined) {
    throw new Error('useShiftContext must be used within a ShiftProvider');
  }

  return context;
};