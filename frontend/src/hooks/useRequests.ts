import axios from 'axios';

export const useRequests = () => {
  const baseUrl = import.meta.env.VITE_API_URL;

  const fileDispute = async (data: { attendance_id: number; proposed_time_in: string; proposed_time_out: string; reason: string }) => {
    return axios.post(`${baseUrl}/users/file_dispute.php`, data);
  };

  const fileOvertime = async (data: { date: string; start_time: string; end_time: string; reason: string }) => {
    return axios.post(`${baseUrl}/users/file_overtime.php`, data);
  };

  const fileLeave = async (data: { leave_type: string; start_date: string; end_date: string; reason: string }) => {
    return axios.post(`${baseUrl}/users/file_leave.php`, data);
  };

  return { fileDispute, fileOvertime, fileLeave };
};
