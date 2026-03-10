import { useEffect, useState } from 'react';
import axios from 'axios';

export interface AttendanceRecord {
  date: string;
  time_in: string;
  time_out: string;
  total_hours: number;
  status: string;
}

export const useAttendance = () => {
  const [data, setData] = useState<AttendanceRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const baseUrl = import.meta.env.VITE_API_URL;
        const response = await axios.get(`${baseUrl}/users/get_my_attendance.php`);
        setData(response.data);
      } catch (err) {
        setError('Failed to fetch attendance data');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  return { data, loading, error };
};
