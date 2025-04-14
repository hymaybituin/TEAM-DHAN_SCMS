import { create } from "zustand";

const useDataStore = create((set) => ({
  statuses: {
    1: "Active",
    2: "Inactive",
    3: "New",
    4: "Pending",
    5: "Approved",
    6: "Fulfilled",
    7: "Paid",
    8: "Cancelled",
    9: "On Hold",
    10: "Processing",
    11: "In Transit",
    12: "Delivered",
    13: "Returned",
  },
  productUnits: {
    1: "Piece",
    2: "Box",
    3: "Liter",
  },
  setData: (data) => set((state) => ({ ...state, ...data })),
}));

export default useDataStore;
